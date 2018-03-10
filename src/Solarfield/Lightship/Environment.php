<?php
namespace Solarfield\Lightship;

use ErrorException;
use Exception;
use Solarfield\Ok\MiscUtils;

abstract class Environment {
	static private $logger;
	static private $standardOutput;
	static private $vars;
	static private $config;
	
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
	
	/**
	 * @return Logger
	 */
	static public function getLogger() {
		if (!self::$logger) {
			self::$logger = new Logger();
		}
		
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
		
		$vars = static::getVars();
		
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
		$vars->add('appPackageFilePath', $path);
		
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
		$path = $vars->get('appPackageFilePath') . '/config.php';
		/** @noinspection PhpIncludeInspection */
		self::$config = new Config(file_exists($path) ? MiscUtils::extractInclude($path) : []);
		
		//define low level debug flag
		if (!defined('App\DEBUG')) define('App\DEBUG', false);
		
		
		if (\App\DEBUG) {
			$config = static::getConfig();
			
			$vars->add('debugComponentResolution', (bool)$config->get('debugComponentResolution'));
			$vars->add('debugComponentLifetimes', (bool)$config->get('debugComponentLifetimes'));
			$vars->add('debugMemUsage', (bool)$config->get('debugMemUsage'));
			$vars->add('debugPaths', (bool)$config->get('debugPaths'));
			$vars->add('debugRouting', (bool)$config->get('debugRouting'));
			$vars->add('debugReflection', (bool)$config->get('debugReflection'));
			$vars->add('debugClassAutoload', (bool)$config->get('debugClassAutoload'));
		}
	}
}
