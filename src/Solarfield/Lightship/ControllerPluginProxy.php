<?php
namespace Solarfield\Lightship;

class ControllerPluginProxy {
	private $plugin;

	protected function getActualPlugin() {
		return $this->plugin;
	}

	public function __construct(ControllerPlugin $aPlugin) {
		$this->plugin = $aPlugin;
	}
}
