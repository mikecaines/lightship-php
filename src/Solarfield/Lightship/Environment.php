<?php
namespace Solarfield\Lightship;

use ErrorException;
use Exception;
use Psr\Log\LogLevel;
use Solarfield\Lightship\Events\ProcessRouteEvent;
use Solarfield\Ok\EventTargetTrait;
use Solarfield\Ok\LoggerInterface;
use Solarfield\Ok\Logger;
use Solarfield\Ok\MiscUtils;
use Throwable;

abstract class Environment implements EnvironmentInterface {
	use EventTargetTrait;

	private $plugins;
	private $logger;
	private $standardOutput;
	private $vars;
	private $config;
	private $chain;
	private $componentResolver;
	private $devModeEnabled;
	
	/**
	 * Creates the logger returned by getLogger().
	 * Override this to inject a custom logger.
	 * @return LoggerInterface
	 */
	protected function createLogger(): LoggerInterface {
		return new Logger();
	}
	
	protected function createComponentChain(): ComponentChain {
		return new ComponentChain([
			[
				'id' => 'solarfield/lightship-php',
				'namespace' => __NAMESPACE__,
				'path' => __DIR__,
			],

			[
				'id' => 'app',
				'namespace' => 'App',
				'path' => $this->getVars()->get('appPackageFilePath') . '/App',
			]
		]);
	}

	protected function createComponentResolver() : ComponentResolver {
		return new ComponentResolver([
			'logger' => $this->getVars()->get('logComponentResolution')
				? $this->getLogger()->withName($this->getLogger()->getName() . '/componentResolver') : null,

			'logLevel' => $this->getVars()->get('loggingLevel'),
		]);
	}

	protected function resolvePlugins() {

	}

	protected function onProcessRoute(ProcessRouteEvent $aEvt) {

	}

	public function isDevModeEnabled() : bool {
		return $this->devModeEnabled;
	}

	/**
	 * @return Config
	 */
	public function getConfig(): Config {
		return $this->config;
	}
	
	public function getComponentChain($aModuleCode = null): ComponentChain {
		// create the base chain
		if (!$this->chain) $this->chain = $this->createComponentChain();
		
		$chain = $this->chain;
		
		if ($aModuleCode) {
			$chain = $chain->withLinkAppended([
				'id' => 'module',
				'namespace' => 'App\\Modules\\' . $aModuleCode,
				'path' => $this->getVars()->get('appPackageFilePath') . '/App/Modules/' . $aModuleCode,
			]);
		}
		
		return $chain;
	}

	public function getComponentResolver() : ComponentResolver {
		if (!$this->componentResolver) $this->componentResolver = $this->createComponentResolver();
		return $this->componentResolver;
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

	public function getPlugins() {
		if (!$this->plugins) {
			$this->plugins = new EnvironmentPlugins($this);
		}

		return $this->plugins;
	}

	public function route(SourceContextInterface $aContext): SourceContextInterface {
		$event = new ProcessRouteEvent('process-route', ['target' => $this], $aContext);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onProcessRoute'],
		]);

		$this->dispatchEvent($event);

		return $aContext;
	}

	public function boot(SourceContextInterface $aContext) : DestinationContextInterface {
		try {
			if ($this->getVars()->get('logMemUsage')) {
				$bytesUsed = memory_get_usage();
				$bytesLimit = ini_get('memory_limit');

				$this->getLogger()->debug(
					'mem[boot begin]: ' . ceil($bytesUsed/1024) . 'K/' . $bytesLimit
					. ' ' . round($bytesUsed/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
				);

				unset($bytesUsed, $bytesLimit);
			}

			if ($this->getVars()->get('logPaths')) {
				$this->getLogger()->debug('App dependencies file path: '. $this->getVars()->get('appDependenciesFilePath'));
				$this->getLogger()->debug('App package file path: '. $this->getVars()->get('appPackageFilePath'));
			}

			$context = static::route($aContext);
			$stubController = \App\Controller::fromContext($this, $context);

			try {
				$destinationContext = $stubController->boot($context);
			}
			catch (Throwable $e) {
				//if the boot loop was already recovered previously
				if ($context->getBootRecoveryCount() > 0) {
					//don't attempt to recover again, to avoid causing an infinite loop
					throw new Exception(
						"Unrecoverable boot loop error.",
						0, $e
					);
				}

				//let the stub controller handle the exception
				$destinationContext = $stubController->handleException($e);
			}

			if ($this->getVars()->get('logMemUsage')) {
				$bytesUsed = memory_get_usage();
				$bytesPeak = memory_get_peak_usage();
				$bytesLimit = ini_get('memory_limit');

				$this->getLogger()->debug(
					'mem[boot end]: ' . ceil($bytesUsed/1024) . 'K/' . $bytesLimit
					. ' ' . round($bytesUsed/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
				);

				$this->getLogger()->debug(
					'mem-peak[boot end]: ' . ceil($bytesPeak/1024) . 'K/' . $bytesLimit
					. ' ' . round($bytesPeak/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
				);

				unset($bytesUsed, $bytesPeak, $bytesLimit);

				$bytesUsed = realpath_cache_size();
				$bytesLimit = ini_get('realpath_cache_size');

				$this->getLogger()->debug(
					'realpath-cache-size[boot end]: ' . (ceil($bytesUsed/1024)) . 'K/' . $bytesLimit
					. ' ' . round($bytesUsed/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
				);

				unset($bytesUsed, $bytesLimit);
			}
		}
		catch (Throwable $e) {
			$destinationContext = static::bail($e);
		}

		return $destinationContext;
	}

	public function init() {
		$this->resolvePlugins();
	}
	
	public function __construct($aOptions) {
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

		//define the low level "unsafe development mode enabled" flag
		$this->devModeEnabled = $this->getConfig()->has('UNSAFE_DEV_MODE_ENABLED')
			? $this->getConfig()->get('UNSAFE_DEV_MODE_ENABLED') : false;

		
		//add an env var for the desired log message verbosity level
		//This should be set to least as verbose as you intend to capture in your logger.
		//Log message producers may obey it to avoid generating expensive messages.
		//Normally defaults to WARNING. If dev mode is enabled, defaults to DEBUG.
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
			$logLevel = $this->isDevModeEnabled() ? LogLevel::DEBUG : LogLevel::WARNING;
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
