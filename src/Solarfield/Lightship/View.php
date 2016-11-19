<?php
namespace Solarfield\Lightship;

use Solarfield\Lightship\Events\ResolveHintsEvent;
use Solarfield\Lightship\Events\ResolveInputEvent;


abstract class View extends \Solarfield\Batten\View {
	protected function resolveHints() {
		parent::resolveHints();

		$event = new ResolveHintsEvent('resolve-hints', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveHints'],
		]);

		$this->dispatchEvent($event);
	}

	protected function resolveInput() {
		parent::resolveInput();

		$event = new ResolveInputEvent('resolve-input', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveInput'],
		]);

		$this->dispatchEvent($event);
	}

	protected function onResolveHints(ResolveHintsEvent $aEvt) {

	}

	protected function onResolveInput(ResolveInputEvent $aEvt) {

	}
}
