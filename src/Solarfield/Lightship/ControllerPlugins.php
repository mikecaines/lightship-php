<?php
namespace Solarfield\Lightship;

use Exception;

class ControllerPlugins {
	private $controller;
	private $items = [];
	private $itemsByClass = [];

	public function register($aComponentCode) {
		if (array_key_exists($aComponentCode, $this->items)) {
			$this->controller->getEnvironment()->getLogger()->notice("Duplicate plugin registration.", [
				'componentCode' => $aComponentCode,
			]);
		}

		$plugin = null;

		$component = $this->controller->getEnvironment()->getComponentResolver()->resolveComponent(
			$this->controller->getEnvironment()->getComponentChain($this->controller->getCode()),
			'ControllerPlugin',
			null,
			$aComponentCode
		);

		if ($component) {
			/** @noinspection PhpIncludeInspection */
			include_once $component['includeFilePath'];

			if (!class_exists($component['className'])) {
				throw new Exception(
					"Class class '" . $component['className'] . "'"
					. " was not found in file '" . $component['includeFilePath'] . "'."
				);
			}

			$plugin = new $component['className']($this->controller, $aComponentCode);
		}

		$this->items[$aComponentCode] = [
			'plugin' => $plugin,
			'componentCode' => $aComponentCode,
		];

		return $this->get($aComponentCode);
	}

	/**
	 * Gets the plugin with the specified component code, or null if it is not found.
	 * @param string $aComponentCode
	 * @return ControllerPlugin|null
	 * @throws Exception
	 */
	public function get($aComponentCode) {
		if (array_key_exists($aComponentCode, $this->items) && $this->items[$aComponentCode]['plugin']) {
			return $this->items[$aComponentCode]['plugin'];
		}

		return null;
	}
	
	/**
	 * Gets the plugin implementing the specified interface, or null if it is not found.
	 * @param $aClass
	 * @return ControllerPlugin|null
	 * @throws Exception
	 */
	public function getByClass($aClass) {
		$plugin = null;

		if (array_key_exists($aClass, $this->itemsByClass)) {
			return $this->itemsByClass[$aClass];
		}

		else {
			foreach ($this->getRegistrations() as $registration) {
				if (($item = $this->get($registration['componentCode'])) && $item instanceof $aClass) {
					if ($plugin) {
						$this->controller->getLogger()->warning("Could not retrieve plugin because multiple instances of " . $aClass . " are registered.");
						break;
					}

					$plugin = $item;
				}
			}

			$this->itemsByClass[$aClass] = $plugin;

			return $plugin;
		}
	}
	
	/**
	 * Gets the plugin implementing the specified interface, or throws if it is not found.
	 * @param $aClass
	 * @return ControllerPlugin|null
	 * @throws Exception
	 */
	public function expectByClass($aClass) {
		$plugin = $this->getByClass($aClass);
		
		if (!$plugin) throw new Exception(
			"Expected plugin of type {$aClass}."
		);
		
		return $plugin;
	}

	public function getRegistrations() {
		$registrations = [];

		foreach ($this->items as $k => $item) {
			$registrations[] = [
				'componentCode' => $item['componentCode'],
			];
		}

		return $registrations;
	}

	public function __construct(Controller $aController) {
		$this->controller = $aController;
	}
}
