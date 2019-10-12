<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

interface SourceContextInterface {
	static public function fromParts(array $aParts): SourceContextInterface;
	
	public function getInput(): InputInterface;
	
	public function getHints(): Hints;
	
	public function getRoute(): Route;
	
	/**
	 * @param Route|array $aRoute
	 */
	public function setRoute($aRoute);
	
	public function getBootPath(): array;
	
	public function getBootRecoveryCount(): int;
	
	public function addBootStep($aStep);
	
	public function toParts(): array;
}
