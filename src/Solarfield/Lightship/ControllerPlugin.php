<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructProxy;

class ControllerPlugin extends \Solarfield\Batten\ControllerPlugin {
	private $manifest;

	public function getManifest() {
		if (!$this->manifest) {
			$this->manifest = new StructProxy();
		}

		return $this->manifest;
	}
}
