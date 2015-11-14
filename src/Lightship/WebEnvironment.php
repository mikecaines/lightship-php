<?php
namespace Lightship;

use Ok\StructUtils;

require_once __DIR__ . '/Environment.php';

abstract class WebEnvironment extends Environment {
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

	static public function init($aOptions) {
		parent::init($aOptions);
		$options = static::getVars();

		$path = preg_replace('/^' . preg_quote(realpath($_SERVER['DOCUMENT_ROOT']), '/') . '/', '', realpath($options->get('appPackageFilePath')));
		$path = str_replace('\\', '/', $path);
		$options->add('appPackageWebPath', $path);

		$options->add('appDependenciesWebPath', $options->get('appPackageWebPath') . '/deps');
	}
}
