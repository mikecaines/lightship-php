<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;

require_once __DIR__ . '/Environment.php';

abstract class TerminalEnvironment extends Environment {
	static private $chain;

	static public function getBaseChain() {
		if (!self::$chain) {
			self::$chain = parent::getBaseChain();

			self::$chain = StructUtils::insertBefore(self::$chain, 'app', __NAMESPACE__, [
				'namespace' => __NAMESPACE__,
				'path' => __DIR__,
			]);
		}

		return self::$chain;
	}
}
