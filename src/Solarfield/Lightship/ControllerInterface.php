<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\EventTargetInterface;

interface ControllerInterface extends EventTargetInterface {
	/**
	 * @param EnvironmentInterface $aEnvironment
	 * @param SourceContextInterface $aContext
	 * @return ControllerInterface
	 */
	static public function fromContext(EnvironmentInterface $aEnvironment, SourceContextInterface $aContext): ControllerInterface;

	/**
	 * Manages early, low-level routing, in a static context.
	 * @param EnvironmentInterface $aEnvironment
	 * @param SourceContextInterface $aContext
	 * @return DestinationContextInterface
	 */
	static public function boot(EnvironmentInterface $aEnvironment, SourceContextInterface $aContext) : DestinationContextInterface;

	static public function bail(EnvironmentInterface $aEnvironment, \Throwable $aException) : DestinationContextInterface;

	/**
	 * Performs low-level routing.
	 * @param EnvironmentInterface $aEnvironment
	 * @param SourceContextInterface $aContext
	 * @return SourceContextInterface
	 */
	static public function route(EnvironmentInterface $aEnvironment, SourceContextInterface $aContext): SourceContextInterface;

	/**
	 * Manages late, high-level routing, in a controller instance context.
	 * @param SourceContextInterface $aContext
	 * @return DestinationContextInterface
	 */
	public function bootDynamic(SourceContextInterface $aContext) : DestinationContextInterface;

	/**
	 * Performs high-level routing.
	 * @param SourceContextInterface $aContext
	 * @return SourceContextInterface
	 */
	public function routeDynamic(SourceContextInterface $aContext): SourceContextInterface;

	public function run() : DestinationContextInterface;

	public function handleException(\Throwable $aEx) : DestinationContextInterface;
	
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
	 * @return ModelInterface|null
	 */
	public function getModel();

	/**
	 * @param string $aType
	 * @return ViewInterface
	 */
	public function createView($aType);

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
