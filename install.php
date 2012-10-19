#!/usr/bin/php
<?php
/**
 * This file is part of the Vigu PHP error aggregation system.
 * @link https://github.com/localgod/Vigu
 *
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
set_time_limit(0);

if (!is_dir($libPath = dirname(__FILE__) . '/lib')) {
	mkdir($libPath, 0777, true);
}

$libs = array(
	array(
		'lib' => 'frood',
		'url' => 'http://github.com/Ibmurai/frood.git',
		'ver' => 'v.1.0'
	),
	array(
		'lib' => 'froodTwig',
		'url' => 'http://github.com/akimsko/froodTwig.git',
		'ver' => 'v.1.0'
	),
	array(
		'lib' => 'PHP-Daemon',
		'url' => 'http://github.com/shaneharter/PHP-Daemon.git',
		'ver' => 'origin/master'
	),
	array(
		'lib' => 'php-gearman-admin',
		'url' => 'http://github.com/Ibmurai/php-gearman-admin.git',
		'ver' => 'v.1.0'
	),
);

foreach ($libs as $lib) {
	$installPath = "$libPath/{$lib['lib']}";

	if (!is_dir($installPath)) {
		echo " > Cloning {$lib['lib']}, version {$lib['ver']}\n";

		system('git clone ' . escapeshellarg($lib['url']) . ' ' . escapeshellarg($installPath));
	}

	echo " > Fetching {$lib['lib']}, version {$lib['ver']}\n";

	system('cd ' . escapeshellarg($installPath) . ' && git fetch origin && git reset --hard ' . escapeshellarg($lib['ver']));

	if (file_exists($libInstall = $libPath . "/{$lib['lib']}/install.php")) {
		system('php ' . escapeshellarg($libInstall));
	}
}
