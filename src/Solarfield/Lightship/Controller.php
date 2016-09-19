<?php
namespace Solarfield\Lightship;

use Exception;
use Solarfield\Ok\StructUtils;

abstract class Controller extends \Solarfield\Batten\Controller {
	static public function bootstrap() {
		$exitCode = 1;

		try {
			if (($controller = static::boot())) {
				try {
					$controller->connect();
					$controller->run();
					$exitCode = 0;
				}
				catch (Exception $ex) {
					$controller->handleException($ex);
				}
			}
		}

		catch (Exception $ex) {
			static::bail($ex);
		}

		return $exitCode;
	}

	private function resolvePluginDependencies_step($plugin) {
		$plugins = $this->getPlugins();

		foreach ($plugin->getManifest()->getAsArray('dependencies.plugins') as $dep) {
			if (StructUtils::search($plugins->getRegistrations(), 'componentCode', $dep['code']) === false) {
				if (($depPlugin = $plugins->register($dep['code']))) {
					$this->resolvePluginDependencies_step($depPlugin);
				}
			}
		}
	}

	private function resolvePluginDependencies() {
		$plugins = $this->getPlugins();

		foreach ($plugins->getRegistrations() as $registration) {
			if (($plugin = $plugins->get($registration['componentCode']))) {
				$this->resolvePluginDependencies_step($plugin);
			}
		}
	}

	public function init() {
		$this->resolvePlugins();
		$this->resolvePluginDependencies();

		$this->resolveOptions();
	}
}
