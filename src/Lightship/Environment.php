<?php
namespace Lightship;

use Exception;

require_once \App\DEPENDENCIES_FILE_PATH . '/mikecaines/batten-php/src/Batten/Environment.php';

abstract class Environment extends \Batten\Environment {
	static private $config;
	static private $classLoader;

	static protected function getConfig() {
		return self::$config;
	}

	static protected function getClassLoader() {
		if (!self::$classLoader) {
			include_once __DIR__ . '/ClassLoader.php';
			self::$classLoader = new ClassLoader();
		}

		return self::$classLoader;
	}

	static public function init($aOptions) {
		parent::init($aOptions);

		error_reporting(E_ALL | E_STRICT);

		self::$config = array_key_exists('config', $aOptions) ? $aOptions['config'] : [];


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


		//register the class loader
		spl_autoload_register([static::getClassLoader(), 'handleClassAutoload'], true, true);
	}
}
