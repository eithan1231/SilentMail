<?php

class search
{
	public static function doSearch($user_id, $keywords)
	{
		$skip_keyword_min_check = false;
		$query_where_outbox = "";
		$query_where_inbox = "";
		$query_where_vinbox = "";

		if($instruct_sender = extract_instruction('sender', $keywords, true)) {

			$sender_exploded = array_unique(explode(' ', $instruct_sender[0]));
			$sender_exploded_count = count($sender_exploded);
			foreach ($sender_exploded as $key => $value) {
				$query_where_inbox .= "inbox.sender_name LIKE ". sql::quote("%". sql::wildcardEscape($value) ."%") ." OR ";
				$query_where_inbox .= "inbox.sender_address LIKE ". sql::quote(sql::wildcardEscape($value) ."%");
				if($key < $sender_exploded_count - 1) {
					$query_where_inbox .= ' OR ';
				}
			}

			$skip_keyword_min_check = true;
		}

		if($instruct_subject = extract_instruction('subject', $keywords, true)) {
			$subject_exploded = array_unique(explode(' ', $instruct_subject[0]));
			$subject_exploded_count = count($subject_exploded);
			foreach ($subject_exploded as $key => $value) {
				$query_where_inbox .= "inbox.subject LIKE ". sql::quote("%". sql::wildcardEscape($value) ."%");
				$query_where_outbox .= "outbox.subject LIKE ". sql::quote("%". sql::wildcardEscape($value) ."%");
				$query_where_vinbox .= "vinbox.subject LIKE ". sql::quote("%". sql::wildcardEscape($value) ."%");
				if($key < $subject_exploded_count - 1) {
					$query_where_inbox .= ' OR ';
					$query_where_outbox .= ' OR ';
					$query_where_vinbox .= ' OR ';
				}
			}

			$skip_keyword_min_check = true;
		}

		if($instruct_read = extract_instruction('read', $keywords, true)) {
			$skip_keyword_min_check = true;
		}

		if ($instruct_mode = extract_instruction('mode', $keywords, true)) {
			switch (strtolower($instruct_mode[0])) {
				case 'inbox':
				case 'in':
				case 'received': {
					$query_where_outbox = " 0 = 1 ";
					$query_where_vinbox = " 0 = 1 ";
					break;
				}

				case 'outbox':
				case 'out':
				case 'sent': {
					$query_where_inbox = " 0 = 1 ";
					$query_where_vinbox = " 0 = 1 ";
					break;
				}

				case 'vinbox':
				case 'vin':
				case 'virtual': {
					$query_where_outbox = " 0 = 1 ";
					$query_where_inbox = " 0 = 1 ";
					break;
				}
			}

			$skip_keyword_min_check = true;
		}

		// Processing keywords... ignore the mess please
		$keywords_exploded_pre_processed = array_unique(explode(' ', $keywords), SORT_STRING);
		$keywords_exploded = [];
		$keywords_exploded_count = 0;

		// Processing keywords
		foreach($keywords_exploded_pre_processed as $word) {
			$word = filters::cleanKeyword($word);
			if(!filters::isValidKeyword($word)) {
				continue;
			}

			$keywords_exploded[] = $word;
			$keywords_exploded_count++;
		}

		// Keyword limit
		if($keywords_exploded_count > config['searchKeywordLimit']) {
			return function_response(false, [
				'message' => 'Exceeded keyword limit ('. config['searchKeywordLimit'] .')'
			]);
		}
		if(!$skip_keyword_min_check && $keywords_exploded_count <= 0) {
			return function_response(false, [
				'message' => 'No valid keywords found'
			]);
		}

		// Gets all possible conditions from keywords for a specific column
		$getCondition = function($column) use($keywords_exploded, $keywords_exploded_count) {
			if($keywords_exploded_count === 0) {
				// No keywords, lets return empty
				return '';
			}

			$ret = '(';

			for($i = 0; $i < $keywords_exploded_count; $i++) {
				$ret .= " {$column} LIKE ". sql::quote(sql::wildcardEscape($keywords_exploded[$i]) ."%");
				if($i < $keywords_exploded_count - 1) {
					$ret .= " OR";
				}
			}

			$ret .= ")";
			return $ret;
		};

		$getMetaphoneCondition = function($column) use($keywords_exploded, $keywords_exploded_count)  {
			if($keywords_exploded_count === 0) {
				// No keywords, lets return empty
				return '';
			}

			$ret = "(";

			for($i = 0; $i < $keywords_exploded_count; $i++) {
				$ret .= " {$column} = ". sql::quote(metaphone($keywords_exploded[$i]));
				if($i < $keywords_exploded_count - 1) {
					$ret .= " OR";
				}
			}

			$ret .= ")";
			return $ret;
		};

		$prepend = function($string, $prepend = '', $empty = '') {
			if(mb_strlen($string) > 0) {
				return $prepend . $string;
			}
			else if(mb_strlen($empty) > 0) {
				return $prepend . $empty;
			}
			else {
				return '';
			}
		};

		// Executing the query, this might be a bit resource heavy if user has a lot
		// of emails...
		$result = sql::query_fetch_all("
			SELECT
				outbox.id as p1,
				outbox.recipients as p2,
				outbox.subject as p3,
				'reserved' as p4,
				'reserved' as p5,
				outbox.time as time,
				'outbox' as type
			FROM `outbox_keywords`
			RIGHT JOIN `outbox`
				ON outbox_keywords.outbox_id = outbox.id
			WHERE
				outbox_keywords.sender = ". sql::quote($user_id) ."
				". $prepend($query_where_outbox, ' AND ', ' 0 = 0 ') ."
				". $prepend($getCondition('outbox_keywords.word'), ' OR ') ."
				". $prepend($getMetaphoneCondition('outbox_keywords.metaphone'), ' OR ') ."
			GROUP BY outbox.id
			UNION ALL
			SELECT
				inbox.id as p1,
				inbox.sender_name as p2,
				inbox.subject as p3,
				inbox.has_seen as p4,
				inbox.is_sender_verified as p5,
				inbox.time as time,
				'inbox' as type
			FROM `inbox_keywords`
			RIGHT JOIN `inbox`
				ON inbox_keywords.inbox_id = inbox.id
			WHERE
				inbox_keywords.receiver = ". sql::quote($user_id) ."
				". $prepend($query_where_inbox, ' AND ', ' 0 = 0 ') ."
				". $prepend($getCondition('inbox_keywords.word'), ' OR ') ."
				". $prepend($getMetaphoneCondition('inbox_keywords.metaphone'), ' OR ') ."
			GROUP BY inbox.id
			UNION ALL
			SELECT
				vinbox.id as p1,
				vinbox.sender_name as p2,
				vinbox.subject as p3,
				vinbox.has_seen as p4,
				vinbox.receiver as p5,
				vinbox.time as time,
				'vinbox' as type
			FROM `vinbox_keywords`
			RIGHT JOIN `vinbox`
				ON vinbox_keywords.vinbox_id = vinbox.id
			WHERE
				vinbox_keywords.receiver_parent = ". sql::quote($user_id) ."
				". $prepend($query_where_vinbox, ' AND ', ' 0 = 0 ') ."
				". $prepend($getCondition('vinbox_keywords.word'), ' OR ') ."
			GROUP BY vinbox.id

			ORDER BY time DESC
			LIMIT 100
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'No results.'
			]);
		}

		return function_response(true, [
			'message' => 'Successfully got query',
			'results' => $result
		]);
	}
}
