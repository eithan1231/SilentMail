<?php

class security_questions
{
	public static function newPair($user_id, $hint, $question, $answer)
	{
		if(!SKIP_REGISTRATION_SECURITY_CHECKS) {
			if(!filters::isValidSecurityQuestion($question)) {
				return function_response(false, [
					'message' => 'Invalid question'
				]);
			}

			if(!filters::isValidSecurityHint($hint)) {
				return function_response(false, [
					'message' => 'Invalid hint'
				]);
			}

			if(!filters::isValidSecurityAnswer($answer)) {
				return function_response(false, [
					'message' => 'Invalid answer'
				]);
			}
		}

		$question_lower = strtolower($question);
		$answer_lower = strtolower($answer);
		$id = security_questions::generateId();

		$result = sql::query("
			INSERT INTO `security_questions`
			(`id`, `user_id`, `question`, `question_lower`, `hint`, `answer`, `answer_lower`)
			VALUES (
				". sql::quote($id) .",
				". sql::quote($user_id) .",
				". sql::quote($question) .",
				". sql::quote($question_lower) .",
				". sql::quote($hint) .",
				". sql::quote($answer) .",
				". sql::quote($answer_lower) ."
			)
		");

		if($result) {
			return function_response(true, [
				'message' => 'Successful',
				'id' => $id
			]);
		}
		else {
			return function_response(false, [
				'message' => 'Internal error'
			]);
		}
	}

	public static function validatePair($question_id, $user_id, $answer)
	{
		$answer_lower = strtolower($answer);

		if(!filters::isValidSecurityAnswer($answer)) {
			return function_response(false, [
				'message' => 'Answer too long. 128 character limit.'
			]);
		}

		$result = sql::query_fetch("
			SELECT `question`, `question_lower`, `answer`, `answer_lower`
			FROM `security_questions`
			WHERE
				`id` = ". sql::quote($question_id) ." AND
				`user_id` = ". sql::quote($user_id) ."
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'Question not found'
			]);
		}

		if($result['answer_lower'] === $answer_lower) {
			return function_response(true, [
				'message' => 'Successful'
			]);
		}
		else {
			return function_response(false, [
				'message' => 'Answer does not match'
			]);
		}
	}

	/**
	* On success if will return id, question, hint, and answer in an array, on
	* failure it will return false.
	*/
	public static function getPair($user_id)
	{
		return sql::query_fetch("
			SELECT `id`, `question`, `hint`, `answer`
			FROM `security_questions`
			WHERE
				`user_id` = ". sql::quote($user_id) ."
		");
	}

	private static function idExists($id)
	{
		return sql::query("
			SELECT `id`
			FROM `security_questions`
			WHERE `id` = ". sql::quote($id) ."
		")->num_rows > 0;
	}

	private static function generateId()
	{
		$val = '';

		while(true) {
			$val = cryptography::randomString(32);

			if(!security_questions::idExists($val)) {
				break;
			}
		}

		return $val;
	}
}
