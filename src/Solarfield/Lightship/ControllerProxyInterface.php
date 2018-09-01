<?php
namespace Solarfield\Lightship;

interface ControllerProxyInterface {
	/**
	 * @return ComponentResolver
	 */
	public function getComponentResolver();

	/**
	 * @param string $aType
	 * @return ViewInterface
	 */
	public function createView($aType);

	/**
	 * @return ControllerPluginsProxy
	 */
	public function getPlugins();
}
