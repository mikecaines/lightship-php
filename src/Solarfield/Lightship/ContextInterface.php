<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

interface ContextInterface {
	static public function fromParts(array $aParts): ContextInterface;
	
	public function getInput(): InputInterface;
	
	public function getHints(): Hints;
	
	public function getRoute(): Route;
	
	/**
	 * @param Route|array $aRoute
	 */
	public function setRoute($aRoute);
	
	public function getBootPath(): array;
	
	public function getBootRecoveryCount(): int;
	
	public function withAddedBootStep($aStep): ContextInterface;
	
	public function toParts(): array;
}
