<?php
/**
 * @license http://www.apache.org/licenses/LICENSE-2.0
 *
 * @category Vigu
 * @package  Run
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */

// Zaphod with Vigu configuration
require_once dirname(__FILE__) . '/../lib/zaphod/src/Zaphod.php';
require_once dirname(__FILE__) . '/../lib/frood/src/Frood/Configuration.php';
require_once dirname(__FILE__) . '/../Configuration.php';
Zaphod::run(new ViguConfiguration());
