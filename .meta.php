<?php
/*
 * This file exists only to help IDE's with inspection of objects in the app namespace,
 * which are not in this package.
 */

namespace App {
	if (true) die();

	define('App\DEBUG', false);
	define('App\DEPENDENCIES_FILE_PATH', realpath(__DIR__ . '/../../'));
	abstract class Environment extends \Batten\Environment {}
}
