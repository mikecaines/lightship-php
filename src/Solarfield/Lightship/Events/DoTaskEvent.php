<?php
namespace Solarfield\Lightship\Events;

use Solarfield\Lightship\DestinationContextInterface;
use Solarfield\Ok\Event;

class DoTaskEvent extends Event {
	private $destinationContext;

	public function getDestinationContext(): DestinationContextInterface {
		return $this->destinationContext;
	}

	public function __construct($aType, array $aInfo, DestinationContextInterface $aDestinationContext) {
		parent::__construct($aType, $aInfo);
		$this->destinationContext = $aDestinationContext;
	}
}
