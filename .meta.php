<?php
/*
 * This file exists only to help IDE's with inspection of objects in the app namespace,
 * which are not in this package.
 */

namespace App {
	if (true) die();

	define('App\DEBUG', false);
	abstract class Environment extends \Solarfield\Batten\Environment {}
}
