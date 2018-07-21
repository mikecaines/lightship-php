<?php
namespace Solarfield\Lightship;

interface ControllerProxyInterface {
	/**
	 * @return ComponentResolver
	 */
	public function getComponentResolver();

	/**
	 * @param string $aCode
	 * @return ComponentChain
	 */
	public function getComponentChain($aCode): ComponentChain;

	/**
	 * @param string $aType
	 * @return ViewInterface
	 */
	public function createView($aType);

	/**
	 * @return HintsInterface
	 */
	public function createHints();

	/**
	 * @return InputInterface
	 */
	public function createInput();

	/**
	 * @return ControllerPluginsProxy
	 */
	public function getPlugins();
}
