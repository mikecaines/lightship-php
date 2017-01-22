<?php
namespace Solarfield\Lightship;

use Exception;
use Solarfield\Ok\MiscUtils;
use Solarfield\Ok\StructUtils;

abstract class Environment extends \Solarfield\Batten\Environment {
	static private $chain;

	static public function getBaseChain() {
		if (!self::$chain) {
			self::$chain = parent::getBaseChain();

			array_splice(self::$chain, StructUtils::search(self::$chain, 'id', 'app'), 0, [[
				'id' => 'solarfield/lightship-php',
				'namespace' => __NAMESPACE__,
				'path' => __DIR__,
			]]);
		}

		return self::$chain;
	}

	static public function init($aOptions) {
		parent::init($aOptions);

		static::getVars()->add('requestId', MiscUtils::guid());


		//set the project package file path
		//This is the top level directory that contains composer's vendor dir, www, etc.
		if (!array_key_exists('projectPackageFilePath', $aOptions)) throw new Exception(
			"The projectPackageFilePath option must be specified when calling " . __METHOD__ . "."
		);
		$projectPackageFilePath = realpath($aOptions['projectPackageFilePath']);
		if (!$projectPackageFilePath) throw new Exception(
			"Invalid projectPackageFilePath: '" . $aOptions['projectPackageFilePath'] . "'."
		);
		static::getVars()->add('projectPackageFilePath', $projectPackageFilePath);

		//set the app dependencies dir path (i.e. composer's vendor dir)
		$path = $projectPackageFilePath . DIRECTORY_SEPARATOR . 'vendor';
		if (!is_dir($path)) throw new Exception(
			"Did not find composer's vendor directory at $path. Have you run 'composer install' yet?"
		);
		static::getVars()->add('appDependenciesFilePath', $path);
	}
}
