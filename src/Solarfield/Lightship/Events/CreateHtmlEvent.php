<?php
namespace Solarfield\Lightship\Events;

use Solarfield\Lightship\StringProxy;
use Solarfield\Ok\Event;

class CreateHtmlEvent extends Event {
	private $html;

	public function getHtml() {
		if (!$this->html) {
			$this->html = new StringProxy();
		}

		return $this->html;
	}
}