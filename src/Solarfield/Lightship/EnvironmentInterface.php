<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

use Solarfield\Ok\LoggerInterface;

interface EnvironmentInterface {
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
}
