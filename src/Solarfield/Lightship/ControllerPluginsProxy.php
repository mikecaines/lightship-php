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
	 * Gets the proxy for the plugin implementing the specified interface,
	 * or null if it is not found.
	 * @param string $aClass
	 * @return null|ControllerPluginProxy
	 * @throws \Exception
	 */
	public function getByClass($aClass) {
		$plugin = $this->getActualPlugins()->getByClass($aClass);
		return $plugin ? $plugin->getProxy() : null;
	}
	
	/**
	 * Gets the proxy for the plugin implementing the specified interface,
	 * or throws if it is not found.
	 * @param $aClass
	 * @return ControllerPluginProxy
	 * @throws \Exception
	 */
	public function expectByClass($aClass) {
		$plugin = $this->getActualPlugins()->getByClass($aClass);
		
		if (!$plugin) throw new \Exception(
			"Expected plugin of type {$aClass}."
		);
		
		return $plugin->getProxy();
	}

	public function getRegistrations() {
		return $this->plugins->getRegistrations();
	}

	public function __construct(ControllerPlugins $aPlugins) {
		$this->plugins = $aPlugins;
	}
}
