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

			self::$chain = StructUtils::insertBefore(self::$chain, 'app', 'solarfield/lightship-php', [
				'namespace' => __NAMESPACE__,
				'path' => __DIR__,
			]);
		}

		return self::$chain;
	}

	static public function init($aOptions) {
		parent::init($aOptions);

		static::getVars()->add('requestId', MiscUtils::guid());


		//init projectPackageFilePath

		if (!array_key_exists('projectPackageFilePath', $aOptions)) {
			throw new Exception(
				"The projectPackageFilePath option must be specified when calling " . __METHOD__ . "."
			);
		}

		$path = realpath($aOptions['projectPackageFilePath']);

		if (!$path) {
			throw new Exception(
				"Invalid projectPackageFilePath: '" . $aOptions['projectPackageFilePath'] . "'."
			);
		}

		static::getVars()->add('projectPackageFilePath', $path);


		//set the php error log path
		ini_set('error_log', static::getVars()->get('projectPackageFilePath') . '/files/logs/php/php.log');


		//init composerVendorFilePath

		if (!array_key_exists('composerVendorFilePath', $aOptions)) {
			throw new Exception(
				"The composerVendorFilePath option must be specified when calling " . __METHOD__ . "."
			);
		}

		$path = realpath($aOptions['composerVendorFilePath']);

		if (!$path) {
			throw new Exception(
				"Invalid composerVendorFilePath: '" . $aOptions['composerVendorFilePath'] . "'."
			);
		}

		static::getVars()->add('composerVendorFilePath', $path);


		static::getVars()->add('appDependenciesFilePath', static::getVars()->get('composerVendorFilePath'));
	}
}
