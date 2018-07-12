<?php
namespace Solarfield\Lightship;

use ErrorException;
use Exception;
use Psr\Log\LogLevel;
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
		
		//add an env var for the desired log message verbosity level
		//This should be set to least as verbose as you intend to capture in your logger.
		//Log message producers may obey it to avoid generating expensive messages.
		//Normally defaults to WARNING. If \App\DEBUG is enabled, defaults to DEBUG.
		//@see \Psr\Log\LogLevel
		//@see static::createLogger()
		if (($t = static::getConfig()->get('loggingLevel'))) {
			if (in_array($t, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice','info', 'debug'])) {
				$logLevel = $t;
			}
			else {
				$logLevel = LogLevel::WARNING;
				
				static::getLogger()->warning("Environment var 'loggingLevel' is invalid.", [
					'value' => $t,
				]);
			}
		}
		else {
			$logLevel = \App\DEBUG ? LogLevel::DEBUG : LogLevel::WARNING;
		}
		static::getVars()->set('loggingLevel', $logLevel);
		
		//define some env vars to control log message output
		//These are noisy, so are disabled by default.
		static::getVars()->add('logComponentResolution', (bool)static::getConfig()->get('logComponentResolution'));
		static::getVars()->add('logComponentLifetimes', (bool)static::getConfig()->get('logComponentLifetimes'));
		static::getVars()->add('logMemUsage', (bool)static::getConfig()->get('logMemUsage'));
		static::getVars()->add('logPaths', (bool)static::getConfig()->get('logPaths'));
		static::getVars()->add('logRouting', (bool)static::getConfig()->get('logRouting'));
	}
}
