<?php
namespace Solarfield\Lightship;

use Exception;

abstract class Controller extends \Solarfield\Batten\Controller {
	static public function bootstrap() {
		try {
			$controller = static::boot();

			try {
				$controller->connect();
				$controller->run();
			}
			catch (Exception $ex) {$controller->handleException($ex);}
		}

		catch (Exception $ex) {
			static::bail($ex);
		}
	}
}
