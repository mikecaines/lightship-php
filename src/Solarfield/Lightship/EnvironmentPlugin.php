<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructProxy;
use Solarfield\Ok\EventTargetTrait;

class EnvironmentPlugin {
	use EventTargetTrait;
	
	private $manifest;
	private $environment;
	private $componentCode;
	private $proxy;
	
	/**
	 * @return EnvironmentInterface
	 */
	public function getEnvironment() {
		return $this->environment;
	}
	
	public function getCode() {
		return $this->componentCode;
	}

	public function getManifest() {
		if (!$this->manifest) {
			$this->manifest = new StructProxy();
		}

		return $this->manifest;
	}
	
	public function __construct(EnvironmentInterface $aEnvironment, $aComponentCode) {
		$this->environment = $aEnvironment;
		$this->componentCode = (string)$aComponentCode;
	}
}
