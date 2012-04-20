<?php
/**
 * Vigu test bootstrap file
 *
 * PHP Version 5.1.2
 *
 * @category TODO_DOCUMENT_ME
 * @package  TODO_DOCUMENT_ME
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @author   Johannes Skov Frandsen <localgof@heaven.dk>
 * @since    2012-TODO-
 */
if (false === spl_autoload_functions()) {
	if (function_exists('__autoload')) {
		spl_autoload_register('__autoload', false);
	}
}
