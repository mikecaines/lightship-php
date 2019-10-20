<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

use Solarfield\Ok\EventTargetInterface;
use Solarfield\Ok\LoggerInterface;
use Throwable;

interface EnvironmentInterface extends EventTargetInterface {
	public function getLogger(): LoggerInterface;
	
	public function getConfig(): Config;
	
	/**
	 * @param string|null $aModuleCode
	 * @return ComponentChain
	 */
	public function getComponentChain($aModuleCode): ComponentChain;

	public function getComponentResolver() : ComponentResolver;
	
	public function getStandardOutput(): StandardOutput;
	
	public function getVars(): Options;

	/**
	 * @return EnvironmentPlugins
	 */
	public function getPlugins();

	public function route(SourceContextInterface $aContext) : SourceContextInterface;

	public function boot(SourceContextInterface $aContext) : DestinationContextInterface;

	/**
	 * Will be called by ::boot() if an uncaught error occurs before a Controller is created.
	 * Normally this is only called when in an unrecoverable error state.
	 * @see ::handleException().
	 * @param Throwable $aEx
	 * @return DestinationContextInterface
	 */
	public function bail(Throwable $aEx) : DestinationContextInterface;
}
