<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

abstract class SourceContext implements SourceContextInterface {
	static public function fromParts(array $aParts): SourceContextInterface {
		return new static($aParts);
	}
	
	/** @var Route */ private $route;
	/** @var Hints */ private $hints;
	/** @var InputInterface */ private $input;
	private $bootPath = [];
	private $bootRecoveryCount = 0;
	
	public function getRoute(): Route {
		return $this->route;
	}
	
	public function getHints(): Hints {
		return $this->hints;
	}
	
	public function getInput(): InputInterface {
		return $this->input;
	}
	
	public function setRoute($aRoute) {
		$this->route = ($aRoute instanceof Route) ? $aRoute : new Route($aRoute);
	}
	
	public function getBootPath(): array {
		return $this->bootPath;
	}
	
	public function getBootRecoveryCount(): int {
		return $this->bootRecoveryCount;
	}
	
	public function addBootStep($aStep) {
		$this->bootPath[] = $aStep;
	}
	
	public function toParts(): array {
		return [
			'route' => $this->route,
			'hints' => $this->hints,
			'input' => $this->input,
			'bootPath' => $this->bootPath,
			'bootRecoveryCount' => $this->bootRecoveryCount,
		];
	}
	
	public function __construct(array $aOptions = null) {
		$options = array_replace([
			'route' => null,
			'hints' => null,
			'input' => null,
			'bootPath' => [],
			'bootRecoveryCount' => 0,
		], $aOptions?:[]);
		
		$this->bootPath = $options['bootPath'];
		$this->bootRecoveryCount = $options['bootRecoveryCount'];
		
		if ($options['route']) {
			if (!($options['route'] instanceof Route)) throw new \Exception(
				"Option route must be an instance of Route."
			);
			
			$this->setRoute($options['route']);
		}
		else {
			$this->setRoute(new Route());
		}
		
		if ($options['hints']) {
			if (!($options['hints'] instanceof Hints)) throw new \Exception(
				"Option hints must be an instance of Hints."
			);
			
			$this->hints = $options['hints'];
		}
		else {
			$this->hints = new Hints();
		}
		
		if (!($options['input'] && ($options['input'] instanceof InputInterface))) throw new \Exception(
			"Option input must be an instance of InputInterface."
		);
		$this->input = $options['input'];
	}
}
