<?php

class javascript
{
	/**
	* Variables that we're going to render in the js snippets
	*/
	private static $m_variables = [];

	public static function pushVariable(string $name, $value)
	{
		if(!filters::isValidJavascriptVariable($name)) {
			throw new Exception("Invalid variable name <{$name}>");
		}
		javascript::$m_variables[$name] = $value;
	}

	/**
	* gets a javascript snippet fot the top of each file.
	*/
	public static function getJsSnippet()
	{
		?>
		<script>
		const template_token = <?= json_encode(templateToken) ?>;
		const working_directory = <?= json_encode(config['dirFromRoot']) ?>;
		const post_route = <?= json_encode(post::getPostRoute("__replaceme__")) ?>;
		const template_route = <?= json_encode(router::instance()->getRoutePath('template', [
			'token' => '__token__',
			'name' => '__name__',
		])) ?>;


		<?php foreach (javascript::$m_variables as $key => $value): ?>
		const <?= $key ?> = <?= json_encode($value) ?>;
		<?php endforeach; ?>
		</script>
		<?php
	}
}
