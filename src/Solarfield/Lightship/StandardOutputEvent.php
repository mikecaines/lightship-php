<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\Event;

class StandardOutputEvent extends Event {
	private $text = '';
	private $level;
	private $context;

	public function getText(): string {
		return $this->text;
	}
	
	public function getLevel(): string {
		return $this->level;
	}
	
	public function getContext(): array {
		return $this->context;
	}

	public function __construct(StandardOutput $aStandardOutput, $aText, $aSeverity, array $aContext) {
		parent::__construct('standard-output', [
			'target' => $aStandardOutput,
		]);

		$this->text = (string)$aText;
		$this->level = (string)$aSeverity;
		$this->context = $aContext;
	}
}
