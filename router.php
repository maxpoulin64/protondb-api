<?php
require_once __DIR__.'/core.php';
// can't be bothered to set up an autoloader for such a tiny project
require_once __DIR__.'/includes/Router.php';

/**
 * Redirects the user to a file on the filesystem
 */
function nginx($url = null) {
	$url = $url ?: '/__internal'.$_SERVER['REQUEST_URI'];
	header('X-Accel-Redirect: '.$url);
	header('Content-Type:'); // otherwise nginx won't override it
}

/**
 * Returns a JSON response
 */
function api_return_json($data, $status = 200) {
	header('Content-Type: application/json');
	http_response_code($status);
	echo json_encode($data);
}


// Process API requests
$R = new Router();

$R->get('^/games/?$', function() {
	$db = connectDB();
	$query = $db->query('SELECT `appId`, `title` FROM `games` ORDER BY `appId` ASC');
	$query->execute();
	api_return_json($query->fetchAll(PDO::FETCH_ASSOC));
});

$R->get('^/games/([0-9]+)/reports/?$', function($appId) {
	$db = connectDB();
	// no point setting up the whole prepared queries for a one-off at the moment
	$query = $db->query("SELECT * FROM `reports` WHERE `appId`=$appId ORDER BY `timestamp` DESC");
	$query->execute();
	api_return_json($query->fetchAll(PDO::FETCH_ASSOC));
});


// Dispatch request
if(!$R->dispatch(
	parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
	$_SERVER['REQUEST_METHOD'] )
) {
	nginx(); // return hand to nginx if no match
}
