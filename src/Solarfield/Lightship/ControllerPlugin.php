<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructProxy;
use Solarfield\Ok\EventTargetTrait;

class ControllerPlugin {
	use EventTargetTrait;
	
	private $manifest;
	private $controller;
	private $componentCode;
	private $proxy;
	
	/**
	 * @return ControllerInterface
	 */
	public function getController() {
		return $this->controller;
	}
	
	public function getCode() {
		return $this->componentCode;
	}
	
	/**
	 * @return ControllerPluginProxy|null
	 */
	public function getProxy() {
		if (!$this->proxy) {
			$this->proxy = new ControllerPluginProxy($this);
		}
		
		return $this->proxy;
	}
	
	public function getManifest() {
		if (!$this->manifest) {
			$this->manifest = new StructProxy();
		}

		return $this->manifest;
	}
	
	public function __construct(ControllerInterface $aController, $aComponentCode) {
		$this->controller = $aController;
		$this->componentCode = (string)$aComponentCode;
	}
}
