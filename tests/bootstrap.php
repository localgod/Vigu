<?php
/**
 * This file is part of the Vigu PHP error aggregation system.
 *
 * PHP version 5.3+
 * 
 * @category  Test
 * @package   Vigu
 * @author    Johannes Skov Frandsen <localgod@heaven.dk>
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link      https://github.com/localgod/Vigu
 */
if (false === spl_autoload_functions()) {
	if (function_exists('__autoload')) {
		spl_autoload_register('__autoload', false);
	}
}
