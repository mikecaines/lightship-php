<?php
namespace Solarfield\Lightship\Events;

use Solarfield\Ok\Event;

class ProcessRouteEvent extends Event {
	private $route = null;

	public function setRoute(array $aRoute = null) {
		$this->route = $aRoute;
	}

	public function getRoute() {
		return $this->route;
	}
}