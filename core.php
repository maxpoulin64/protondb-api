<?php
function connectDB() {
	$config = require __DIR__.'/config.php';
	
	return new PDO(
		$config['DB_DSN'],
		$config['DB_USER'], $config['DB_PASS'],
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		]
	);
}
