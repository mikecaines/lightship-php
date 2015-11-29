<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Batten\StandardOutputEvent;

class StdoutView extends View {
	public function handleStandardOutput(StandardOutputEvent $aEvt) {
		$this->out($aEvt->getText());
	}

	public function out($aMessage) {
		fwrite(STDOUT, $aMessage . "\n");
	}

	public function render() {
		//do nothing
	}

	public function init() {
		parent::init();

		Env::getStandardOutput()->addEventListener('standard-output', [$this, 'handleStandardOutput']);
	}

	public function __construct($aCode) {
		$this->type = 'Stdout';
		parent::__construct($aCode);
	}
}
