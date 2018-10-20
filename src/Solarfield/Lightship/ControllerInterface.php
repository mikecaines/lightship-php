<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\EventTargetInterface;

interface ControllerInterface extends EventTargetInterface {
	/**
	 * @param EnvironmentInterface $aEnvironment
	 * @param ContextInterface $aContext
	 * @return ControllerInterface
	 */
	static public function fromContext(EnvironmentInterface $aEnvironment, ContextInterface $aContext): ControllerInterface;
	
	/**
	 * Manages early, low-level routing, in a static context.
	 * @param EnvironmentInterface $aEnvironment
	 * @param ContextInterface $aContext
	 * @return ControllerInterface
	 */
	static public function boot(EnvironmentInterface $aEnvironment, ContextInterface $aContext);
	
	/**
	 * Performs low-level routing.
	 * @param EnvironmentInterface $aEnvironment
	 * @param ContextInterface $aContext
	 * @return ContextInterface
	 */
	static public function route(EnvironmentInterface $aEnvironment, ContextInterface $aContext): ContextInterface;
	
	/**
	 * Manages late, high-level routing, in a controller instance context.
	 * @param ContextInterface $aContext
	 * @return ControllerInterface|null
	 */
	public function bootDynamic(ContextInterface $aContext);

	/**
	 * Called if the controller is resolved to the final controller in routeDynamic().
	 */
	public function markResolved();
	
	/**
	 * Performs high-level routing.
	 * @param ContextInterface $aContext
	 * @return ContextInterface
	 */
	public function routeDynamic(ContextInterface $aContext): ContextInterface;

	public function run();

	public function runTasks();

	public function runRender();

	public function handleException(\Throwable $aEx);
	
	/**
	 * @return ComponentResolver
	 */
	public function getComponentResolver();
	
	/**
	 * @return string|null
	 */
	public function getDefaultViewType();

	/**
	 * @return string|null
	 */
	public function getRequestedViewType();

	/**
	 * @return Hints|null
	 */
	public function getHints();

	/**
	 * @return InputInterface|null
	 */
	public function getInput();

	/**
	 * @return ModelInterface
	 */
	public function createModel();

	/**
	 * @return ModelInterface|null
	 */
	public function getModel();

	public function setModel(ModelInterface $aModel);

	/**
	 * @param string $aType
	 * @return ViewInterface
	 */
	public function createView($aType);

	/**
	 * @return ViewInterface|null
	 */
	public function getView();

	public function setView(ViewInterface $aView);

	public function getCode();

	/**
	 * @return ControllerPlugins
	 */
	public function getPlugins();

	/**
	 * @return Options
	 */
	public function getOptions();

	public function getEnvironment(): EnvironmentInterface;
	
	public function init();
}
