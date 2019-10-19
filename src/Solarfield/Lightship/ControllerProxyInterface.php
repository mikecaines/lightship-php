<?php
namespace Solarfield\Lightship;

interface ControllerProxyInterface {
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
