<?php

class search
{
	public static function doSearch($user_id, $keywords)
	{
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
		if($keywords_exploded_count <= 0) {
			return function_response(false, [
				'message' => 'No valid keywords found'
			]);
		}


		// Gets all possible conditions from keywords for a specific column
		$getCondition = function($column) use($keywords_exploded, $keywords_exploded_count) {
			$ret = '(';

			for($i = 0; $i < $keywords_exploded_count; $i++) {
				$word = &$keywords_exploded[$i];

				// Removing the multichar wildcard with single-char wildcard.
				$censored_word = str_replace(
					["%", "_"],
					["\\%", "\\_"],
					$word
				);

				$ret .= " $column LIKE ";
				$ret .= "\"";
				$ret .= mysqli_real_escape_string(sql::$instance, $censored_word);
				$ret .= "%\"";

				if($i < $keywords_exploded_count - 1) {
					$ret .= " OR";
				}
			}

			$ret .= ")";
			return $ret;
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
				outbox_keywords.sender = ". sql::quote($user_id) ." AND
				". $getCondition('outbox_keywords.word') ."
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
				inbox_keywords.receiver = ". sql::quote($user_id) ." AND
				". $getCondition('inbox_keywords.word') ."
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
				vinbox_keywords.receiver_parent = ". sql::quote($user_id) ." AND
				". $getCondition('vinbox_keywords.word') ."
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
