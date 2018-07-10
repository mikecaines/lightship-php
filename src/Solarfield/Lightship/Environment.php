<?php
namespace Solarfield\Lightship;

use ErrorException;
use Exception;
use Solarfield\Ok\LoggerInterface;
use Solarfield\Ok\Logger;
use Solarfield\Ok\MiscUtils;

abstract class Environment {
	static private $logger;
	static private $standardOutput;
	static private $vars;
	static private $config;
	
	/**
	 * Creates the logger returned by getLogger().
	 * Override this to inject a custom logger.
	 * @return LoggerInterface
	 */
	static protected function createLogger(): LoggerInterface {
		return new Logger();
	}
	
	/**
	 * @return Config
	 */
	static public function getConfig() {
		return self::$config;
	}
	
	static public function getBaseChain() {
		return $chain = [
			[
				'id' => 'solarfield/lightship-php',
				'namespace' => __NAMESPACE__,
				'path' => __DIR__,
			],
			
			[
				'id' => 'app',
				'namespace' => 'App',
				'path' => static::getVars()->get('appPackageFilePath') . '/App',
			],
		];
	}
	
	static public function getLogger(): LoggerInterface {
		if (!self::$logger) self::$logger = static::createLogger();
		return self::$logger;
	}
	
	/**
	 * @return StandardOutput
	 */
	static public function getStandardOutput() {
		if (!self::$standardOutput) {
			self::$standardOutput = new StandardOutput();
		}
		
		return self::$standardOutput;
	}
	
	static public function getVars() {
		if (!self::$vars) {
			require_once __DIR__ . '/Options.php';
			self::$vars = new Options(['readOnly'=>true]);
		}
		
		return self::$vars;
	}
	
	static public function init($aOptions) {
		$options = array_replace([
			'projectPackageFilePath' => null,
			'appPackageFilePath' => null,
			'errorReporting' => E_ALL,
		], $aOptions);
		
		set_error_handler(function ($aNumber, $aMessage, $aFile, $aLine) {
			throw new ErrorException($aMessage, 0, $aNumber, $aFile, $aLine);
		});
		
		error_reporting($options['errorReporting']);
		
		if (PHP_VERSION_ID < 70000) throw new Exception(
			"PHP version 7 or higher is required."
		);
		
		static::getVars()->add('requestId', MiscUtils::guid());
		
		//validate app package file path
		if (!$options['appPackageFilePath']) throw new Exception(
			"The appPackageFilePath option must be specified when calling " . __METHOD__ . "."
		);
		$path = realpath($options['appPackageFilePath']);
		if (!$path) {
			throw new Exception(
				"Invalid appPackageFilePath: '" . $options['appPackageFilePath'] . "'."
			);
		}
		static::getVars()->add('appPackageFilePath', $path);
		
		//set the project package file path
		//This is the top level directory that contains composer's vendor dir, www, etc.
		if (!array_key_exists('projectPackageFilePath', $options)) throw new Exception(
			"The projectPackageFilePath option must be specified when calling " . __METHOD__ . "."
		);
		$projectPackageFilePath = realpath($options['projectPackageFilePath']);
		if (!$projectPackageFilePath) throw new Exception(
			"Invalid projectPackageFilePath: '" . $options['projectPackageFilePath'] . "'."
		);
		static::getVars()->add('projectPackageFilePath', $projectPackageFilePath);
		
		//set the app dependencies dir path (i.e. composer's vendor dir)
		$path = $projectPackageFilePath . DIRECTORY_SEPARATOR . 'vendor';
		if (!is_dir($path)) throw new Exception(
			"Did not find composer's vendor directory at $path. Have you run 'composer install' yet?"
		);
		static::getVars()->add('appDependenciesFilePath', $path);
		
		
		//include the config
		require_once __DIR__ . '/Config.php';
		$path = static::getVars()->get('appPackageFilePath') . '/config.php';
		/** @noinspection PhpIncludeInspection */
		self::$config = new Config(file_exists($path) ? MiscUtils::extractInclude($path) : []);
		
		//define the low level "unsafe debug mode enabled" flag
		if (!defined('App\DEBUG')) define('App\DEBUG', false);
		
		//define some debug behaviour flags
		static::getVars()->add('debugComponentResolution', (bool)static::getConfig()->get('debugComponentResolution'));
		static::getVars()->add('debugComponentLifetimes', (bool)static::getConfig()->get('debugComponentLifetimes'));
		static::getVars()->add('debugMemUsage', (bool)static::getConfig()->get('debugMemUsage'));
		static::getVars()->add('debugPaths', (bool)static::getConfig()->get('debugPaths'));
		static::getVars()->add('debugRouting', (bool)static::getConfig()->get('debugRouting'));
		static::getVars()->add('debugReflection', (bool)static::getConfig()->get('debugReflection'));
		static::getVars()->add('debugClassAutoload', (bool)static::getConfig()->get('debugClassAutoload'));
	}
}
