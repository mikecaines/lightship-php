<?php
namespace Solarfield\Lightship;

class StdoutView extends View {
	public function handleStandardOutput(StandardOutputEvent $aEvt) {
		$this->out($aEvt->getText());
	}

	public function out($aMessage) {
		fwrite(STDOUT, $aMessage . "\n");
	}

	public function render(DestinationContextInterface $aDestinationContext) : DestinationContextInterface {
		return $aDestinationContext;
	}

	public function init() {
		parent::init();
		
		$this->getEnvironment()->getStandardOutput()->addEventListener('standard-output', [$this, 'handleStandardOutput']);
	}
	
	public function __construct(EnvironmentInterface $aEnvironment, string $aCode, $aOptions = []) {
		$this->type = 'Stdout';
		parent::__construct($aEnvironment, $aCode, $aOptions);
	}
}
