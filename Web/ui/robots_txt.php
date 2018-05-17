<?php

if(substr_count($_SERVER['REQUEST_URI'], '/') >= 2) {
	echo "# Inaccessible robots.txt file\n";
	echo "# ------------------------------------------------------------------\n";
	echo "# Administrator Note: This auto generated robots.txt file must be at\n";
	echo "# the root directory, otherwise it will not work.";
	die();
}

function get_viewable_route($route)
{
	$prefix = config['dirFromRoot'];
	$prefix_length = strlen($prefix);

	if($prefix[$prefix_length - 1] == '/') {
		$prefix = substr($prefix, 0, $prefix_length - 1);
	}

	return $prefix . preg_replace('/\{[^}]+\}/', '*', $route);
}

function echo_allowed_disallowed($is_alllowed)
{
	if($is_alllowed) {
		echo "Allowed: ";
	}
	else {
		echo "Disallow: ";
	}
}

$routes = router::Instance()->getRoutes();

echo "#---------------------------------------------------------------------\n";
echo "# Auto generated robots.txt file for ". remove_clrf(config['projectName']) ."\n";
echo "#---------------------------------------------------------------------\n\n\n";
echo "User-agent: *\n\n";
foreach ($routes as $route) {
	if($route['method'] !== 'GET') {
		continue;
	}

	echo_allowed_disallowed($route['crawlers_allowed']);
	echo get_viewable_route($route['route']) ."\n";
}
