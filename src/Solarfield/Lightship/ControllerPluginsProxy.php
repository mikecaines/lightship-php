<?php
namespace Solarfield\Lightship;

class ControllerPluginsProxy {
	private $plugins;

	/** @var ControllerPluginProxy[] */
	private $proxies = [];

	protected function getActualPlugins() {
		return $this->plugins;
	}

	public function get($aComponentCode) {
		$proxy = null;

		if (array_key_exists($aComponentCode, $this->proxies)) {
			$proxy = $this->proxies[$aComponentCode];
		}

		else {
			$plugin = $this->getActualPlugins()->get($aComponentCode);

			if ($plugin) {
				$proxy = $plugin->getProxy();
				$this->proxies[$aComponentCode] = $proxy;
			}
		}

		return $proxy;
	}

	/**
	 * Gets the plugin by the plugin class (not the plugin proxy class).
	 * @param string $aClass
	 * @return null|ControllerPluginProxy
	 */
	public function getByClass($aClass) {
		$plugin = $this->getActualPlugins()->getByClass($aClass);
		return $plugin ? $plugin->getProxy() : null;
	}

	public function getRegistrations() {
		return $this->plugins->getRegistrations();
	}

	public function __construct(ControllerPlugins $aPlugins) {
		$this->plugins = $aPlugins;
	}
}
