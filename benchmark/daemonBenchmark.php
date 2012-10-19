<?php
/**
 * This file is part of the Vigu PHP error aggregation system.
 *
 * PHP version 5.3+
 *
 * @category  ErrorAggregation
 * @package   Vigu
 * @author    Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link      https://github.com/localgod/Vigu
 */
require_once dirname(__FILE__) . '/../handlers/RedisFunctions.php';

$redis = new RedisFunctions(100000);
$redis->flushAll();

echo "Generating notices...\n";
`php gearmanBenchmarkGenerateNotices.php 2>/dev/null`;

echo 'I will now start the daemon.';

chdir('../handlers');
system("nohup php daemon.php >/dev/null 2>&1 &");
$start = microtime(true);

usleep(250000);

echo "Monitoring incoming...\n";

while (($size = $redis->getIncomingSize()) > 0) {
	echo "Incoming size is $size...\n";
	usleep(100000);
}

printf("Incoming queue emptied in %.1f seconds.\n", microtime(true) - $start);

echo `kill \`cat /vigu/ViguDaemon.lock\``;
