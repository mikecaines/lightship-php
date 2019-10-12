<?php

namespace Solarfield\Lightship;

class TerminalDestinationContext implements DestinationContextInterface {
	private $exitStatus;

	public function getExitStatus() : int {
		return $this->exitStatus;
	}

	public function __construct(int $aExitStatus = 0) {
		$this->exitStatus = $aExitStatus;
	}
}
