<?php
namespace Solarfield\Lightship\Events;

use Solarfield\Lightship\ContextInterface;
use Solarfield\Ok\Event;

class ProcessRouteEvent extends Event {
	private $context = null;

	public function getContext() {
		return $this->context;
	}
	
	public function __construct($aType, array $aInfo, ContextInterface $aContext) {
		parent::__construct($aType, $aInfo);
		$this->context = $aContext;
	}
}
