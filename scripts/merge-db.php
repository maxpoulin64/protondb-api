<?php
ini_set('memory_limit', '512M');
require_once __DIR__.'/../core.php';

/**
 * Checks the last timestamp we processed from the metadata table
 */
function check_last_timestamp($db) {
	$query = $db->query('SELECT `value` FROM `meta` WHERE `key`="last_update"');
	$query->execute();
	$ts = $query->fetchColumn(0);
	$query->closeCursor();
	
	if($ts === false) {
		throw new Exception('Invalid database metadata state: "last_update"');
	}
	
	return (int)$ts;
}

/**
 * Updates the last timestamp we processed on the metadata table
 */
function update_timestamp($db, $ts) {
	$ts = (int)$ts;
	$query = $db->query("UPDATE `meta` SET `value`=$ts WHERE `key`='last_update'");
	$query->execute();
}

/**
 * Returns an array of all the known appIds from the local database
 */
function get_appId_array($db) {
	$query = $db->query('SELECT `appId` FROM `games` GROUP BY `appId` ORDER BY `appId` ASC');
	$query->execute();
	$appIds = $query->fetchAll(PDO::FETCH_COLUMN, 0);
	return array_map('intval', $appIds);
}

/**
 * Validates that a report JSON has the structure we expect and add missing
 * fields in-place in the original array
 */
function normalize_upstream_report(&$report) {
	if(!is_array($report)) {
		throw new Exception('Invalid upstream report object');
	}
	
	// Fields we need for DB consistency
	$required_fields = [
		'appId',
		'title',
		'timestamp'
	];
	
	foreach($required_fields as $field) {
		if(!array_key_exists($field, $report)) {
			throw new Exception('Missing report key: '.$field);
		}
	}
	
	// Some of these are missing in some reports, setting them to null
	$optional_fields = [
		'rating',
		'notes',
		'os',
		'gpuDriver',
		'specs',
		'protonVersion'
	];
	
	foreach($optional_fields as $field) {
		if(!array_key_exists($field, $report)) {
			$report[$field] = null;
		}
	}
}

/**
 * Extracts only the specified $keys from $array
 */
function filter_array_keys($array, $keys) {
	return array_filter($array, function($key) use($keys) {
		return in_array($key, $keys);
	}, ARRAY_FILTER_USE_KEY);
}

/**
 * Processes and merges all data from upstream database to local database
 */
function merge_upstream_database($db, $jsondb) {
	if(!is_array($jsondb)) {
		throw new Exception('Unexpected upstream database format: expected top level array.');
	}
	
	$last_ts = check_last_timestamp($db);
	$new_ts = $last_ts;
	$known_appIds = get_appId_array($db);
	
	// Prepare query to add game
	$add_game = $db->prepare('INSERT INTO `games` (`appId`, `title`) VALUES(:appId, :title)');
	
	// Prepare query to add report
	$report_fields = ['appId', 'timestamp', 'rating', 'notes', 'os', 'gpuDriver', 'specs', 'protonVersion'];
	$add_report = $db->prepare(
		'INSERT INTO `reports` (`'
			.implode('`, `', $report_fields)
		.'`) VALUES(:'
			.implode(', :', $report_fields)
		.')'
	);
	
	// Process all the reports
	foreach($jsondb as $report) {
		// Skip invalid reports, because even the initial database is inconsistent
		try {
			normalize_upstream_report($report);
		} catch(Exception $e) {
			continue;
		}
		
		// Skip known reports
		if((int)$report['timestamp'] <= $last_ts) continue;
		
		// Add games as needed
		if(!in_array((int)$report['appId'], $known_appIds)) {
			$add_game->execute(filter_array_keys($report, ['appId', 'title']));
			$known_appIds[] = (int)$report['appId'];
		}
		
		$add_report->execute(filter_array_keys($report, $report_fields));
		$new_ts = max($new_ts, (int)$report['timestamp']);
	}
	
	update_timestamp($db, $new_ts);
}


// Kick off main processing
try {
	$db = connectDB();
	$jsondb = json_decode(file_get_contents(__DIR__.'/../webroot/reports.json'), true);

	if(!$jsondb) {
		throw new Exception('Failed to read or parse upstream JSON database');
	}
	
	$db->beginTransaction();
	merge_upstream_database($db, $jsondb);
	$db->commit();
}
catch(Exception $e) {
	$db->rollback();
	
	echo 'An error has occured while merging upstream database: ';
	echo $e->getMessage(), "\n";
	echo $e->getTraceAsString();
	exit -1;
}
