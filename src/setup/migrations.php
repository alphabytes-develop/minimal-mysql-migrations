<?php

function getMigrations() {
	$directory = getConfig()['migrations'];
	$migrations = array_values(array_filter(scandir($directory), function($entry) use($directory) {
		return $entry !== '.' && $entry !== '..' && is_dir($directory . '/' . $entry);
	}));

	/* Sort them because file names are strings and we need numerical ordering */
	natsort($migrations);

	/* PHP keeps the keys even though we reorder. WHY??? */
	$migrations = array_values($migrations);

	/* Parse them into full paths for up/down */
	$migrations = array_map(function($migration) use($directory) {
		return [
			'no' => intval($migration),
			'up' => $directory . '/' . $migration . '/up.sql',
			'dir' => $directory . '/' . $migration,
			'down' => $directory . '/' . $migration . '/down.sql'
		];
	}, $migrations);


	$last = 0;
	foreach($migrations as &$migration) {
		if($migration['no'] !== ++$last) {
			echo "Missing migration: $last\n";
			exit(1);
		}

		if(!is_file($migration['up'])) {
			echo "Missing file: $migration[up]\n";
			exit(1);
		}

		if(!is_file($migration['down'])) {
			echo "Missing file: $migration[down]\n";
			exit(1);
		}

		$return = 0;
		$migration['hash'] = hash('sha256', hash_file('sha256', $migration['up']) . hash_file('sha256', $migration['down']));
		$migration['upSql'] = file_get_contents($migration['up']);
		$migration['downSql'] = file_get_contents($migration['down']);

		if($return !== 0) {
			echo "Could not determine git version of directory: '$migration[dir]'\n";
			exit(1);
		}
	}

	return $migrations;
}
