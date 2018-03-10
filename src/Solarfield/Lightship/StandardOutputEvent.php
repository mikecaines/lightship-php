<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\Event;

class StandardOutputEvent extends Event {
	private $output = '';

	public function getText() {
		return $this->output;
	}

	public function __construct(StandardOutput $aStandardOutput, $aOutput) {
		parent::__construct('standard-output', [
			'target' => $aStandardOutput,
		]);

		$this->output = (string)$aOutput;
	}
}
