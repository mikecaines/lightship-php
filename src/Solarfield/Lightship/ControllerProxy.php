<?php
namespace Solarfield\Lightship;

class ControllerProxy implements ControllerProxyInterface {
	private $controller;
	private $plugins;

	public function getComponentResolver() {
		return $this->controller->getComponentResolver();
	}

	public function getComponentChain($aCode): ComponentChain {
		return $this->controller->getComponentChain($aCode);
	}

	public function createHints() {
		return $this->controller->createHints();
	}

	public function createInput() {
		return $this->controller->createInput();
	}

	public function createView($aType) {
		return $this->controller->createView($aType);
	}

	public function getPlugins() {
		if (!$this->plugins) {
			$this->plugins = new ControllerPluginsProxy($this->controller->getPlugins());
		}

		return $this->plugins;
	}

	public function __construct(ControllerInterface $aController) {
		$this->controller = $aController;
	}
}
