<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Exception;
use Solarfield\Lightship\Errors\UnresolvedRouteException;
use Solarfield\Ok\EventTargetTrait;
use Solarfield\Ok\MiscUtils;
use Solarfield\Ok\StructUtils;
use Throwable;

abstract class Controller implements ControllerInterface {
	use EventTargetTrait;
	
	static private $bootPath = [];
	static private $bootLoopRecoveryAttempted;
	static private $componentResolver;
	
	static public function fromCode($aCode, $aOptions = array()) {
		$component = static::getComponentResolver()->resolveComponent(
			static::getChain($aCode),
			'Controller',
			null,
			null
		);
		
		if (!$component) {
			throw new \Exception(
				"Could not resolve Controller component for module '" . $aCode . "'."
				. " No component class files could be found."
			);
		}
		
		/** @noinspection PhpIncludeInspection */
		include_once $component['includeFilePath'];
		
		if (!class_exists($component['className'])) {
			throw new \Exception(
				"Could not resolve Controller component for module '" . $aCode . "'."
				. " No component class was found in include file '" . $component['includeFilePath'] . "'."
			);
		}
		
		/** @var Controller $controller */
		$controller = new $component['className']($aCode, $aOptions);
		
		return $controller;
	}
	
	static public function getChain($aModuleCode) {
		$chain = Env::getBaseChain();
		
		if ($aModuleCode != null) {
			$moduleNamespace = $aModuleCode;
			$moduleDir = $moduleNamespace;
			
			$chain[] = [
				'id' => 'module',
				'namespace' => 'App\\Modules\\' . $moduleNamespace,
				'path' => Env::getVars()->get('appPackageFilePath') . '/App/Modules/' . $moduleDir,
			];
		}
		
		return $chain;
	}
	
	static public function processInitialRoute() {
		return null;
	}
	
	static public function getComponentResolver() {
		if (!self::$componentResolver) {
			self::$componentResolver = new ComponentResolver();
		}
		
		return self::$componentResolver;
	}
	
	static public function bootstrap() {
		$exitCode = 1;
		
		try {
			if (($controller = static::boot(static::processInitialRoute()))) {
				try {
					$controller->connect();
					$controller->run();
					$exitCode = 0;
				}
				catch (Throwable $ex) {
					$controller->handleException($ex);
				}
			}
		}
		
		catch (Throwable $ex) {
			static::bail($ex);
		}
		
		return $exitCode;
	}
	
	static public function boot($aInfo = null) {
		if (\App\DEBUG && Env::getVars()->get('debugMemUsage')) {
			$bytesUsed = memory_get_usage();
			$bytesLimit = ini_get('memory_limit');
			
			Env::getLogger()->debug(
				'mem[boot begin]: ' . ceil($bytesUsed/1024) . 'K/' . $bytesLimit
				. ' ' . round($bytesUsed/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
			);
			
			unset($bytesUsed, $bytesLimit);
		}
		
		if (\App\DEBUG && Env::getVars()->get('debugPaths')) {
			Env::getLogger()->debug('App dependencies file path: '. Env::getVars()->get('appDependenciesFilePath'));
			Env::getLogger()->debug('App package file path: '. Env::getVars()->get('appPackageFilePath'));
		}
		
		//default some expected boot info
		//Any additional data will be kept and passed to resolveController()
		$info = $aInfo?:[];
		if (!array_key_exists('moduleCode', $info)) $info['moduleCode'] = '';
		if (!array_key_exists('nextRoute', $info)) $info['nextRoute'] = static::getInitialRoute();
		if (!array_key_exists('controllerOptions', $info)) $info['controllerOptions'] = [];
		
		$stubController = static::fromCode($info['moduleCode'], $info['controllerOptions']);
		$stubController->init();
		
		$finalController = null;
		$finalError = null;
		
		try {
			$finalController = $stubController->resolveController($info);
		}
		catch (Throwable $ex) {
			$finalError = $ex;
		}
		
		//if we couldn't route, but we didn't encounter an exception
		if (!$finalController && !$finalError) {
			//imply a 'could not route' error
			
			$t = $info['nextRoute'];
			$message = "Could not route: " . (is_scalar($t) ? "'$t'" : MiscUtils::varInfo($t)) . ".";
			
			$finalError = new UnresolvedRouteException(
				$message, 0, null,
				[
					'iterations' => array_values(self::$bootPath),
				]
			);
			
			unset($t, $message);
		}
		
		if ($finalError) {
			//if the boot loop was already recovered previously
			if (self::$bootLoopRecoveryAttempted) {
				//don't attempt to recover again, to avoid causing an infinite loop
				throw new Exception(
					"Unrecoverable boot loop error.",
					0, $finalError
				);
			}
			
			//flag that we are attempting to recover the boot loop
			self::$bootLoopRecoveryAttempted = true;
			
			//let the stub controller handle the exception
			$stubController->handleException($finalError);
		}
		
		if (\App\DEBUG && Env::getVars()->get('debugMemUsage')) {
			$bytesUsed = memory_get_usage();
			$bytesPeak = memory_get_peak_usage();
			$bytesLimit = ini_get('memory_limit');
			
			Env::getLogger()->debug(
				'mem[boot end]: ' . ceil($bytesUsed/1024) . 'K/' . $bytesLimit
				. ' ' . round($bytesUsed/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
			);
			
			Env::getLogger()->debug(
				'mem-peak[boot end]: ' . ceil($bytesPeak/1024) . 'K/' . $bytesLimit
				. ' ' . round($bytesPeak/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
			);
			
			unset($bytesUsed, $bytesPeak, $bytesLimit);
			
			$bytesUsed = realpath_cache_size();
			$bytesLimit = ini_get('realpath_cache_size');
			
			Env::getLogger()->debug(
				'realpath-cache-size[boot end]: ' . (ceil($bytesUsed/1024)) . 'K/' . $bytesLimit
				. ' ' . round($bytesUsed/\Solarfield\Ok\PhpUtils::parseShorthandBytes($bytesLimit)*100, 2) . '%'
			);
			
			unset($bytesUsed, $bytesLimit);
		}
		
		return $finalController;
	}
	
	/**
	 * Will be called by ::bootstrap() if an uncaught error occurs before a Controller is created.
	 * Normally this is only called when in an unrecoverable error state.
	 * @see ::handleException().
	 * @param Throwable $aEx
	 */
	static public function bail(Throwable $aEx) {
		Env::getLogger()->error("Bailed.", ['exception'=>$aEx]);
	}
	
	private $code;
	private $hints;
	private $input;
	private $model;
	private $view;
	private $defaultViewType;
	private $options;
	private $plugins;
	private $proxy;
	
	private function resolvePluginDependencies_step($plugin) {
		$plugins = $this->getPlugins();
		
		//if plugin is Lightship-compatible
		if ($plugin instanceof ControllerPlugin) {
			foreach ($plugin->getManifest()->getAsArray('dependencies.plugins') as $dep) {
				if (StructUtils::search($plugins->getRegistrations(), 'componentCode', $dep['code']) === false) {
					if (($depPlugin = $plugins->register($dep['code']))) {
						$this->resolvePluginDependencies_step($depPlugin);
					}
				}
			}
		}
	}
	
	private function resolvePluginDependencies() {
		$plugins = $this->getPlugins();
		
		foreach ($plugins->getRegistrations() as $registration) {
			if (($plugin = $plugins->get($registration['componentCode']))) {
				$this->resolvePluginDependencies_step($plugin);
			}
		}
	}
	
	protected function resolvePlugins() {
	
	}
	
	protected function resolveOptions() {
	
	}
	
	public function resolveController($aInfo) {
		//this remains true until the boot loop stops.
		//During each iteration of the boot loop, controllers are created and asked to provide the next step in the route.
		//Once the same step is returned twice (i.e. no movement), we consider the route successfully processed, and the
		//last created controller is returned. Note that the controller will have a model and input already attached.
		//If any controller during the loop routes to null, we stop and consider the route unsuccessfully processed.
		$keepRouting = true;
		
		/** @var Controller $tempController */
		$tempController = null;
		
		$model = $this->createModel();
		$model->init();
		
		$input = $this->createInput();
		$input->importFromGlobals();
		if ($aInfo && array_key_exists('input', $aInfo)) {
			$input->merge($aInfo['input']);
		}
		
		$hints = $this->createHints();
		if ($aInfo && array_key_exists('hints', $aInfo)) {
			$hints->merge($aInfo['hints']);
		}
		
		//the temporary boot info passed along through the boot loop
		//The only data/keys kept are moduleCode, nextRoute, controllerOptions
		$tempInfo = $aInfo;
		
		$loopCount = 0;
		do {
			if ($tempInfo != null) {
				//reset the boot info for the next iteration
				$tempInfo = [
					'moduleCode' => array_key_exists('moduleCode', $tempInfo) ? $tempInfo['moduleCode'] : '',
					'nextRoute' => array_key_exists('nextRoute', $tempInfo) ? $tempInfo['nextRoute'] : null,
					'controllerOptions' => array_key_exists('controllerOptions', $tempInfo) ? $tempInfo['controllerOptions'] : [],
				];
				
				//create a unique key representing this iteration of the loop.
				//This is used to detect infinite loops, due to a later iteration routing back to an earlier iteration
				$tempIteration = implode('+', [
					$tempInfo['moduleCode'],
					$tempInfo['nextRoute'],
				]);
				
				//if we don't have a temp controller yet,
				//or the temp controller is not the target controller (comparing by module code)
				//or we still have routing to do
				if ($tempController == null || $tempInfo['moduleCode'] != $tempController->getCode() || $tempInfo['nextRoute'] !== null) {
					//if the current iteration has not been encountered before
					if (!array_key_exists($tempIteration, self::$bootPath)) {
						//append the current iteration to the boot path
						self::$bootPath[$tempIteration] = [
							'moduleCode' => $tempInfo['moduleCode'],
							'nextRoute' => $tempInfo['nextRoute'],
						];
						
						//if we already have a temp controller
						if ($tempController) {
							//tell it to create the target controller
							$tempController = $tempController::fromCode($tempInfo['moduleCode'], $tempInfo['controllerOptions']);
							$tempController->init();
						}
						
						//else we don't have a controller yet
						else {
							//if the target controller's code is the same as the current controller
							if ($tempInfo['moduleCode'] == $this->getCode()) {
								//use the current controller as the target controller
								$tempController = $this;
							}
							
							//else the target controller's code is different that the current controller
							else {
								//tell the current controller to create the target controller
								$tempController = $this::fromCode($tempInfo['moduleCode'], $tempInfo['controllerOptions']);
								$tempController->init();
							}
						}
						
						//attach the hints to the new temp controller
						$tempController->setHints($hints);
						
						//attach the input to the new temp controller
						$tempController->setInput($input);
						
						//attach the model to the new temp controller
						$tempController->setModel($model);
						
						//if we have routing to do
						if ($tempInfo['nextRoute'] != null || $loopCount == 0) {
							//tell the temp controller to process the route
							$newInfo = $tempController->processRoute($tempInfo);
							
							if (\App\DEBUG && Env::getVars()->get('debugRouting')) {
								Env::getLogger()->debug(get_class($tempController) . ' routed from -> to: ' . MiscUtils::varInfo($tempInfo) . ' -> ' . MiscUtils::varInfo($newInfo));
							}
							
							$tempInfo = $newInfo;
							unset($newInfo);
							
							//if we get here, the next iteration of the boot loop will now occur
						}
					}
					
					//else the current iteration is a duplication of an earlier iteration
					else {
						//we have detected an infinite boot loop, and cannot resolve the controller
						
						$tempController = null;
						$keepRouting = false;
						
						//append the current iteration to the boot path
						self::$bootPath[$tempIteration] = [
							'moduleCode' => $tempInfo['moduleCode'],
							'nextRoute' => $tempInfo['nextRoute'],
						];
					}
				}
				
				//else we don't have any routing to do
				else {
					$keepRouting = false;
				}
			}
			
			//else $tempInfo is null
			else {
				//if we get here, we could not resolve the final controller
				
				//clear any temp controller as it does not represent the final controller
				$tempController = null;
				
				$keepRouting = false;
			}
			
			$loopCount++;
		}
		while ($keepRouting);
		
		if ($tempController) {
			$tempController->markResolved();
		}
		
		return $tempController;
	}
	
	public function markResolved() {
	
	}
	
	public function processRoute($aInfo) {
		return $aInfo;
	}
	
	public function connect() {
		$viewType = $this->getRequestedViewType();
		
		if ($viewType != null) {
			$view = $this->createView($viewType);
			$view->setController($this->getProxy());
			$view->init();
			
			$input = $view->getInput();
			if ($input) {
				$this->getInput()->mergeReverse($input);
			}
			unset($input);
			
			$hints = $view->getHints();
			if ($hints) {
				$this->getHints()->mergeReverse($hints);
			}
			unset($hints);
			
			$view->setModel($this->getModel());
			
			$this->setView($view);
		}
	}
	
	public function run() {
		$this->runTasks();
		$this->runRender();
	}
	
	/**
	 * Will be called by ::bootstrap() if an uncaught error occurs after a Controller is created.
	 * Normally this is only called when ::connect() or ::run() fails.
	 * You can override this method, and attempt to boot another Controller for recovery purposes, etc.
	 * @see ::bail().
	 * @param Throwable $aEx
	 */
	public function handleException(Throwable $aEx) {
		static::bail($aEx);
	}
	
	public function getDefaultViewType() {
		return $this->defaultViewType;
	}
	
	public function setDefaultViewType($aType) {
		$this->defaultViewType = (string)$aType;
	}
	
	public function createHints() {
		return new Hints();
	}
	
	public function getHints() {
		return $this->hints;
	}
	
	public function setHints(HintsInterface $aHints) {
		$this->hints = $aHints;
	}
	
	public function setInput(InputInterface $aInput) {
		$this->input = $aInput;
	}
	
	/**
	 * @return InputInterface
	 */
	public function getInput() {
		return $this->input;
	}
	
	public function setModel(ModelInterface $aModel) {
		$this->model = $aModel;
	}
	
	/**
	 * @return ModelInterface
	 */
	public function getModel() {
		return $this->model;
	}
	
	public function createModel() {
		$code = $this->getCode();
		
		$component = static::getComponentResolver()->resolveComponent(
			static::getChain($code),
			'Model'
		);
		
		if (!$component) {
			throw new \Exception(
				"Could not resolve Model component for module '" . $code . "'."
				. " No component class files could be found."
			);
		}
		
		/** @noinspection PhpIncludeInspection */
		include_once $component['includeFilePath'];
		
		if (!class_exists($component['className'])) {
			throw new \Exception(
				"Could not resolve Model component for module '" . $code . "'."
				. " No component class was found in include file '" . $component['includeFilePath'] . "'."
			);
		}
		
		$model = new $component['className']($code);
		
		return $model;
	}
	
	public function createView($aType) {
		$code = $this->getCode();
		
		$component = static::getComponentResolver()->resolveComponent(
			static::getChain($code),
			'View',
			$aType
		);
		
		if (!$component) {
			throw new \Exception(
				"Could not resolve " . $aType . " View component for module '" . $code . "'."
				. " No component class files could be found."
			);
		}
		
		/** @noinspection PhpIncludeInspection */
		include_once $component['includeFilePath'];
		
		if (!class_exists($component['className'])) {
			throw new \Exception(
				"Could not resolve " . $aType . " View component for module '" . $code . "'."
				. " No component class was found in include file '" . $component['includeFilePath'] . "'."
			);
		}
		
		$view = new $component['className']($code);
		
		return $view;
	}
	
	public function getView() {
		return $this->view;
	}
	
	public function setView(ViewInterface $aView) {
		$this->view = $aView;
	}
	
	public function getProxy() {
		if (!$this->proxy) {
			$this->proxy = new ControllerProxy($this);
		}
		
		return $this->proxy;
	}
	
	public function getCode() {
		return $this->code;
	}
	
	public function getOptions() {
		if (!$this->options) {
			$this->options = new Options();
		}
		
		return $this->options;
	}
	
	public function getPlugins() {
		if (!$this->plugins) {
			$this->plugins = new ControllerPlugins($this);
		}
		
		return $this->plugins;
	}
	
	public function init() {
		//this method provides a hook to resolve plugins, options, etc.
		
		$this->resolvePlugins();
		$this->resolvePluginDependencies();
		$this->resolveOptions();
	}
	
	public function __construct($aCode) {
		if (\App\DEBUG && Env::getVars()->get('debugComponentLifetimes')) {
			Env::getLogger()->debug(get_class($this) . "[code=" . $aCode . "] was constructed");
		}
		
		$this->code = (string)$aCode;
	}
	
	public function __destruct() {
		if (\App\DEBUG && Env::getVars()->get('debugComponentLifetimes')) {
			Env::getLogger()->debug(get_class($this) . "[code=" . $this->getCode() . "] was destructed");
		}
	}
}
