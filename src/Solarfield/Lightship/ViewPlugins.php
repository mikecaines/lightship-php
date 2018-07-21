<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Exception;

class ViewPlugins {
	private $view;
	private $items = [];
	private $itemsByClass = [];

	public function register($aComponentCode) {
		if (array_key_exists($aComponentCode, $this->items)) {
			throw new Exception(
				"Plugin '$aComponentCode' is already registered."
			);
		}

		else {
			$plugin = null;

			$component = $this->view->getController()->getComponentResolver()->resolveComponent(
				$this->view->getController()->getComponentChain($this->view->getCode()),
				'ViewPlugin',
				$this->view->getType(),
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

				$plugin = new $component['className']($this->view, $aComponentCode);
			}

			$this->items[$aComponentCode] = [
				'plugin' => $plugin,
				'componentCode' => $aComponentCode,
			];
		}

		return $this->get($aComponentCode);
	}

	/**
	 * @param string $aComponentCode
	 * @return ViewPlugin|null
	 * @throws Exception
	 */
	public function get($aComponentCode) {
		if (array_key_exists($aComponentCode, $this->items) && $this->items[$aComponentCode]['plugin']) {
			return $this->items[$aComponentCode]['plugin'];
		}

		return null;
	}

	public function getByClass($aClass) {
		$plugin = null;

		if (array_key_exists($aClass, $this->itemsByClass)) {
			return $this->itemsByClass[$aClass];
		}

		else {
			foreach ($this->getRegistrations() as $registration) {
				if (($item = $this->get($registration['componentCode'])) && $item instanceof $aClass) {
					if ($plugin) {
						Env::getLogger()->warn("Could not retrieve plugin because multiple instances of " . $aClass . " are registered.");
						break;
					}

					$plugin = $item;
				}
			}

			$this->itemsByClass[$aClass] = $plugin;

			return $plugin;
		}
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

	public function __construct(View $aView) {
		$this->view = $aView;
	}
}
