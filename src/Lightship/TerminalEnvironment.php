<?php
namespace Lightship;

use Ok\StructUtils;

require_once __DIR__ . '/Environment.php';

abstract class TerminalEnvironment extends Environment {
	static public function getBaseChain() {
		static $chain;

		if (!$chain) {
			$chain = parent::getBaseChain();

			$chain = StructUtils::insertBefore($chain, 'app', __NAMESPACE__, [
				'namespace' => __NAMESPACE__,
				'path' => __DIR__,
			]);
		}

		return $chain;
	}
}
