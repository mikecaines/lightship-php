<?php
namespace Solarfield\Lightship;

use Exception;
use Solarfield\Lightship\Errors\UnresolvedRouteException;
use Solarfield\Lightship\Events\DoTaskEvent;
use Solarfield\Lightship\Events\ProcessRouteEvent;
use Solarfield\Lightship\Events\ResolveOptionsEvent;
use Solarfield\Ok\EventTargetTrait;
use Solarfield\Ok\MiscUtils;
use Solarfield\Ok\StructUtils;
use Throwable;

abstract class Controller implements ControllerInterface {
	use EventTargetTrait;
	
	static public function fromContext(EnvironmentInterface $aEnvironment, SourceContextInterface $aContext): ControllerInterface {
		$moduleCode = $aContext->getRoute()->getModuleCode();

		$component = $aEnvironment->getComponentResolver()->resolveComponent(
			$aEnvironment->getComponentChain($moduleCode),
			'Model'
		);
		if (!$component) throw new \Exception(
			"Could not resolve Model component for module '" . $moduleCode . "'."
			. " No component class files could be found."
		);
		/** @var ModelInterface $model */ $model = new $component['className']($aEnvironment, $moduleCode);
		$model->init();

		$component = $aEnvironment->getComponentResolver()->resolveComponent(
			$aEnvironment->getComponentChain($moduleCode),
			'Controller'
		);
		if (!$component) throw new \Exception(
			"Could not resolve Controller component for module '" . $moduleCode . "'."
			. " No component class files could be found."
		);
		/** @var ControllerInterface $controller */ $controller = new $component['className'](
			$aEnvironment, $moduleCode, $model, $aContext, $aContext->getRoute()->getControllerOptions()
		);
		$controller->init();
		
		return $controller;
	}

	private $environment;
	private $context;
	private $code;
	private $hints;
	private $model;
	private $defaultViewType;
	private $options;
	private $plugins;
	private $proxy;
	private $logger;

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
	
	protected function getContext(): SourceContextInterface {
		return $this->context;
	}
	
	protected function resolvePlugins() {
	
	}
	
	protected function resolveOptions() {
		$event = new ResolveOptionsEvent('resolve-options', ['target' => $this]);
		
		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveOptions'],
		]);
		
		$this->dispatchEvent($event);
	}
	
	protected function onResolveOptions(ResolveOptionsEvent $aEvt) {
	
	}

	/**
	 * @param ProcessRouteEvent $aEvt
	 */
	protected function onProcessRoute(ProcessRouteEvent $aEvt) {

	}

	protected function onDoTask(DoTaskEvent $aEvt) {

	}
	
	public function boot(SourceContextInterface $aContext) : DestinationContextInterface {
		$keepRouting = true;

		/** @var Controller $tempController */
		$tempController = null;

		//the temporary boot info passed along through the boot loop
		//The only data/keys kept are moduleCode, nextStep, controllerOptions
		$tempContext = $aContext;
		
		$loopCount = 0;
		do {
			$aContext->getInput()->merge($tempContext->getRoute()->getInput());
			$aContext->getHints()->merge($tempContext->getRoute()->getHints());

			//create a unique key representing this iteration of the loop.
			//This is used to detect infinite loops, due to a later iteration routing back to an earlier iteration
			$tempIteration = implode('+', [
				$tempContext->getRoute()->getModuleCode(),
				$tempContext->getRoute()->getNextStep(),
			]);

			//if we don't have a temp controller yet,
			//or the temp controller is not the target controller (comparing by module code)
			//or we still have routing to do
			if ($tempController == null || $tempContext->getRoute()->getModuleCode() != $tempController->getCode() || $tempContext->getRoute()->getNextStep() !== null) {
				//if the current iteration has not been encountered before
				if (!in_array($tempIteration, $tempContext->getBootPath())) {
					//append the current iteration to the boot path
					$tempContext->addBootStep($tempIteration);

					//if we already have a temp controller
					if ($tempController) {
						//tell it to create the target controller
						$tempController = $tempController::fromContext($this->getEnvironment(), $tempContext);
					}

					//else we don't have a controller yet
					else {
						//if the target controller's code is the same as the current controller
						if ($tempContext->getRoute()->getModuleCode() == $this->getCode()) {
							//use the current controller as the target controller
							$tempController = $this;
						}

						//else the target controller's code is different that the current controller
						else {
							//tell the current controller to create the target controller
							$tempController = $this::fromContext($this->getEnvironment(), $tempContext);
						}
					}

					//if we have routing to do
					if ($tempContext->getRoute()->getNextStep() !== null || $loopCount == 0) {
						//tell the temp controller to process the route
						$newContext = $tempController->route($tempContext);

						if ($this->getEnvironment()->getVars()->get('logRouting')) {
							$this->getLogger()->debug(get_class($tempController) . ' routed from -> to: ' . MiscUtils::varInfo($tempContext->getRoute()) . ' -> ' . MiscUtils::varInfo($newContext->getRoute()));
						}

						$tempContext = $newContext;
						unset($newContext);

						//if we get here, the next iteration of the boot loop will now occur
					}
				}

				//else the current iteration is a duplication of an earlier iteration
				else {
					//we have detected an infinite boot loop, and cannot resolve the controller

					$tempController = null;
					$keepRouting = false;

					//append the current iteration to the boot path
					$tempContext->addBootStep($tempIteration);
				}
			}

			//else we don't have any routing to do
			else {
				$keepRouting = false;
			}
			
			$loopCount++;
		}
		while ($keepRouting);

		if (!$tempController) {
			$nextStep = $aContext->getRoute()->getNextStep();

			throw new UnresolvedRouteException(
				"Could not route: " . (is_scalar($nextStep) ? "'$nextStep'" : MiscUtils::varInfo($nextStep)) . ".",
				0, null,
				[
					'bootPath' => $tempContext->getBootPath()
				]
			);
		}

		return $tempController->run();
	}

	public function route(SourceContextInterface $aContext): SourceContextInterface {
		$event = new ProcessRouteEvent('process-route', ['target' => $this], $aContext);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onProcessRoute'],
		]);

		$this->dispatchEvent($event);

		return $aContext;
	}

	public function doTask(DestinationContextInterface $aDestinationContext) : DestinationContextInterface {
		$event = new DoTaskEvent('do-task', ['target' => $this], $aDestinationContext);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onDoTask'],
		]);

		$this->dispatchEvent($event);

		return $aDestinationContext;
	}

	/**
	 * Will be called by ::boot() if an uncaught error occurs after a Controller is created.
	 * Normally this is only called when ::route() or ::run() fails.
	 * You can override this method, and attempt to boot another Controller for recovery purposes, etc.
	 * @see ::bail().
	 * @param Throwable $aEx
	 * @return DestinationContextInterface
	 */
	public function handleException(Throwable $aEx) : DestinationContextInterface {
		return $this->getEnvironment()->bail($aEx);
	}
	
	public function getDefaultViewType() {
		return $this->defaultViewType;
	}
	
	public function setDefaultViewType($aType) {
		$this->defaultViewType = (string)$aType;
	}
	
	public function getHints() {
		return $this->getContext()->getHints();
	}
	
	/**
	 * @return InputInterface
	 */
	public function getInput() {
		return $this->getContext()->getInput();
	}

	/**
	 * @return ModelInterface
	 */
	public function getModel() {
		return $this->model;
	}

	/**
	 * Creates a view, connected to this controller, and its associated model.
	 * @param string $aType
	 * @return ViewInterface
	 * @throws Exception
	 */
	public function createView($aType) {
		$code = $this->getCode();
		
		$component = $this->getEnvironment()->getComponentResolver()->resolveComponent(
			$this->getEnvironment()->getComponentChain($code),
			'View',
			$aType
		);
		if (!$component) throw new \Exception(
			"Could not resolve " . $aType . " View component for module '" . $code . "'."
			. " No component class files could be found."
		);
		/** @var ViewInterface $view */ $view = new $component['className'](
			$this->getEnvironment(), $code, $this->getModel(), $this->getProxy()
		);
		$view->init();
		
		return $view;
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
	
	public function getLogger() {
		if (!$this->logger) {
			$this->logger = $this->getEnvironment()->getLogger()->withName('controller[' . $this->getCode() . ']');
		}
		
		return $this->logger;
	}
	
	public function getEnvironment(): EnvironmentInterface {
		return $this->environment;
	}
	
	public function init() {
		//this method provides a hook to resolve plugins, options, etc.
		
		$this->resolvePlugins();
		$this->resolvePluginDependencies();
		$this->resolveOptions();
	}
	
	public function __construct(
		EnvironmentInterface $aEnvironment, $aCode,
		ModelInterface $aModel, SourceContextInterface $aContext, $aOptions = []
	) {
		$this->environment = $aEnvironment;
		$this->model = $aModel;
		$this->context = $aContext;
		$this->code = (string)$aCode;
		
		if ($aEnvironment->getVars()->get('logComponentLifetimes')) {
			$this->getLogger()->debug(get_class($this) . "[code=" . $aCode . "] was constructed");
		}
	}
	
	public function __destruct() {
		if ($this->getEnvironment()->getVars()->get('logComponentLifetimes')) {
			$this->getLogger()->debug(get_class($this) . "[code=" . $this->getCode() . "] was destructed");
		}
	}
}
