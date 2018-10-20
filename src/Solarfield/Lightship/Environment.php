<?php
namespace Solarfield\Lightship;

use ErrorException;
use Exception;
use Psr\Log\LogLevel;
use Solarfield\Ok\LoggerInterface;
use Solarfield\Ok\Logger;
use Solarfield\Ok\MiscUtils;

class Environment implements EnvironmentInterface {
	private $logger;
	private $standardOutput;
	private $vars;
	private $config;
	private $chain;
	
	/**
	 * Creates the logger returned by getLogger().
	 * Override this to inject a custom logger.
	 * @return LoggerInterface
	 */
	protected function createLogger(): LoggerInterface {
		return new Logger();
	}
	
	protected function createComponentChain(): ComponentChain {
		$chain = new ComponentChain();
		
		$chain->insertAfter(null, [
			'id' => 'solarfield/lightship-php',
			'namespace' => __NAMESPACE__,
			'path' => __DIR__,
		]);
		
		$chain->insertAfter(null, [
			'id' => 'app',
			'namespace' => 'App',
			'path' => $this->getVars()->get('appPackageFilePath') . '/App',
		]);
		
		return $chain;
	}
	
	/**
	 * @return Config
	 */
	public function getConfig(): Config {
		return $this->config;
	}
	
	public function getComponentChain($aModuleCode): ComponentChain {
		// create the base chain
		if (!$this->chain) $this->chain = $this->createComponentChain();
		
		$chain = $this->chain;
		
		if ($aModuleCode) {
			$chain = clone $chain;
			
			$chain->insertAfter(null, [
				'id' => 'module',
				'namespace' => 'App\\Modules\\' . $aModuleCode,
				'path' => $this->getVars()->get('appPackageFilePath') . '/App/Modules/' . $aModuleCode,
			]);
		}
		
		return $chain;
	}
	
	public function getLogger(): LoggerInterface {
		if (!$this->logger) $this->logger = $this->createLogger();
		return $this->logger;
	}
	
	/**
	 * @return StandardOutput
	 */
	public function getStandardOutput(): StandardOutput {
		if (!$this->standardOutput) {
			$this->standardOutput = new StandardOutput();
		}
		
		return $this->standardOutput;
	}
	
	public function getVars(): Options {
		if (!$this->vars) {
			$this->vars = new Options(['readOnly'=>true]);
		}
		
		return $this->vars;
	}
	
	public function init($aOptions) {
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
		
		$this->getVars()->add('requestId', MiscUtils::guid());
		
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
		$this->getVars()->add('appPackageFilePath', $path);
		
		//set the project package file path
		//This is the top level directory that contains composer's vendor dir, www, etc.
		if (!array_key_exists('projectPackageFilePath', $options)) throw new Exception(
			"The projectPackageFilePath option must be specified when calling " . __METHOD__ . "."
		);
		$projectPackageFilePath = realpath($options['projectPackageFilePath']);
		if (!$projectPackageFilePath) throw new Exception(
			"Invalid projectPackageFilePath: '" . $options['projectPackageFilePath'] . "'."
		);
		$this->getVars()->add('projectPackageFilePath', $projectPackageFilePath);
		
		//set the app dependencies dir path (i.e. composer's vendor dir)
		$path = $projectPackageFilePath . DIRECTORY_SEPARATOR . 'vendor';
		if (!is_dir($path)) throw new Exception(
			"Did not find composer's vendor directory at $path. Have you run 'composer install' yet?"
		);
		$this->getVars()->add('appDependenciesFilePath', $path);
		
		
		//include the config
		$path = $this->getVars()->get('appPackageFilePath') . '/config.php';
		/** @noinspection PhpIncludeInspection */
		$this->config = new Config(file_exists($path) ? MiscUtils::extractInclude($path) : []);
		
		//define the low level "unsafe debug mode enabled" flag
		if (!defined('App\DEBUG')) define('App\DEBUG', false);
		
		//add an env var for the desired log message verbosity level
		//This should be set to least as verbose as you intend to capture in your logger.
		//Log message producers may obey it to avoid generating expensive messages.
		//Normally defaults to WARNING. If \App\DEBUG is enabled, defaults to DEBUG.
		//@see \Psr\Log\LogLevel
		//@see $this->createLogger()
		if (($t = $this->getConfig()->get('loggingLevel'))) {
			if (in_array($t, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice','info', 'debug'])) {
				$logLevel = $t;
			}
			else {
				$logLevel = LogLevel::WARNING;
				
				$this->getLogger()->warning("Environment var 'loggingLevel' is invalid.", [
					'value' => $t,
				]);
			}
		}
		else {
			$logLevel = \App\DEBUG ? LogLevel::DEBUG : LogLevel::WARNING;
		}
		$this->getVars()->set('loggingLevel', $logLevel);
		
		//define some env vars to control log message output
		//These are noisy, so are disabled by default.
		$this->getVars()->add('logComponentResolution', (bool)$this->getConfig()->get('logComponentResolution'));
		$this->getVars()->add('logComponentLifetimes', (bool)$this->getConfig()->get('logComponentLifetimes'));
		$this->getVars()->add('logMemUsage', (bool)$this->getConfig()->get('logMemUsage'));
		$this->getVars()->add('logPaths', (bool)$this->getConfig()->get('logPaths'));
		$this->getVars()->add('logRouting', (bool)$this->getConfig()->get('logRouting'));
	}
}
