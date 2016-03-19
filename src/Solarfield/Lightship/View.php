<?php
namespace Solarfield\Lightship;

use Solarfield\Batten\Event;

abstract class View extends \Solarfield\Batten\View {
	protected function resolveHints() {
		parent::resolveHints();

		$this->dispatchEvent(
			new Event('app-resolve-hints', ['target' => $this])
		);
	}

	protected function resolveHintedInput() {
		parent::resolveHintedInput();

		$this->dispatchEvent(
			new Event('app-resolve-hinted-input', ['target' => $this])
		);
	}
}
