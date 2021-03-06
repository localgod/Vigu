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
require_once dirname(__FILE__) . '/../handlers/shutdown.php';
for ($i = 0; $i < 100000; $i++) {
     trigger_error("Notice #" . rand(0, 99));
}
echo "Generated $i notices.\n";
