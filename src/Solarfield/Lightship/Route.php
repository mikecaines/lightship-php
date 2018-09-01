<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

class Route {
	private $moduleCode;
	private $controllerOptions;
	private $input;
	private $hints;
	private $nextStep;
	
	public function getControllerOptions(): array {
		return $this->controllerOptions;
	}
	
	public function getInput(): array {
		return $this->input;
	}
	
	public function getHints(): array {
		return $this->hints;
	}
	
	public function getModuleCode(): string {
		return $this->moduleCode;
	}
	
	public function getNextStep() {
		return $this->nextStep;
	}
	
	public function __construct(array $aOptions = null) {
		$options = array_replace([
			'controllerOptions' => [], // handler (controller) to handler options only
			'hints' => [], // view to handler options
			'input' => [], // request to handler options
			'moduleCode' => '', // next route handler to hand off to
			'nextStep' => null, // remaining route for next handler to process
		], $aOptions?:[]);
		
		$this->controllerOptions = (array)$options['controllerOptions'];
		$this->hints = (array)$options['hints'];
		$this->input = (array)$options['input'];
		$this->moduleCode = (string)$options['moduleCode'];
		$this->nextStep = $options['nextStep'];
	}
}
