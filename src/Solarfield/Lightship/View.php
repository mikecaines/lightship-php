<?php
namespace Solarfield\Lightship;

use Solarfield\Batten\Event;
use Solarfield\Batten\Reflector;

abstract class View extends \Solarfield\Batten\View {
	protected function resolveHints() {
		parent::resolveHints();

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-resolve-hints', ['target' => $this])
			);
		}
	}

	protected function resolveHintedInput() {
		parent::resolveHintedInput();

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-resolve-hinted-input', ['target' => $this])
			);
		}
	}
}
