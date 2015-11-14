<?php
namespace Lightship;

use Batten\Event;
use Batten\Reflector;

abstract class View extends \Batten\View {
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
