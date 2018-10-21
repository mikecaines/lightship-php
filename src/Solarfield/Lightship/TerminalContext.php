<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

class TerminalContext extends Context {
	static public function fromGlobals(): TerminalContext {
		$context = new static([
			'input' => TerminalInput::fromGlobals(),
		]);
		
		$context->setRoute([
			'nextStep' => $context->getInput()->getAsString('--module'),
		]);
		
		return $context;
	}
	
	public function __construct(array $aOptions = null) {
		$options = array_replace([
			'input' => null,
		], $aOptions?:[]);
		
		if ($options['input']) {
			if (!($options['input'] instanceof TerminalInput)) throw new \Exception(
				"Option input must be an instance of TerminalInput."
			);
		}
		else {
			$options['input'] = new TerminalInput();
		}
		
		parent::__construct($options);
	}
}
