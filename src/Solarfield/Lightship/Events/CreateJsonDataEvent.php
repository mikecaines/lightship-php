<?php
namespace Solarfield\Lightship\Events;

use Solarfield\Ok\Event;
use Solarfield\Ok\StructProxy;

class CreateJsonDataEvent extends Event {
	private $data;

	public function getJsonData() {
		if (!$this->data) {
			$this->data = new StructProxy();
		}

		return $this->data;
	}
}