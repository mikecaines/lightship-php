<?php
namespace Solarfield\Lightship;

use Psr\Log\AbstractLogger;
use Solarfield\Ok\EventTargetInterface;
use Solarfield\Ok\EventTargetTrait;

/**
 * A basic sink for ephemeral messages, implementing LoggerInterface and EventTargetInterface.
 * Components may listen for the standard-output event, and deal with the messages in some way.
 * The StdoutView used by the terminal, outputs these messages to the user.
 * The Html and Json views will store these in the model at app.standardOutput.messages, and they will be output to
 * the browser console on the client side.
 */
class StandardOutput extends AbstractLogger implements EventTargetInterface {
	use EventTargetTrait;
	
	public function log($level, $message, array $context = []) {
		$this->dispatchEvent(new StandardOutputEvent($this, $message, $level, $context));
	}
	
	public function write($message) {
		$this->info($message);
	}
}
