<?php
namespace Solarfield\Lightship;

use Psr\Log\AbstractLogger;
use Solarfield\Ok\EventTargetInterface;
use Solarfield\Ok\EventTargetTrait;

class StandardOutput extends AbstractLogger implements EventTargetInterface {
	use EventTargetTrait;
	
	public function log($level, $message, array $context = []) {
		$this->dispatchEvent(new StandardOutputEvent($this, $message, $level, $context));
	}
	
	public function write($message) {
		$this->info($message);
	}
}
