<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\Event;

abstract class View extends \Solarfield\Batten\View {
	protected function resolveHints() {
		parent::resolveHints();

		$this->dispatchEvent(
			new Event('resolve-hints', ['target' => $this])
		);
	}

	protected function resolveInput() {
		parent::resolveInput();

		$this->dispatchEvent(
			new Event('resolve-hinted-input', ['target' => $this])
		);
	}
}
