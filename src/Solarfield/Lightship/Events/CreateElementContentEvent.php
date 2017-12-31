<?php
namespace Solarfield\Lightship\Events;

use Solarfield\Ok\Event;

class CreateElementContentEvent extends Event {
	private $innerHtml = "";
	private $attributes = [];
	
	public function setAttribute(string $aName, string $aValue = null) {
		$this->attributes[strtolower($aName)] = $aValue;
	}
	
	public function getAttribute(string $aName): string {
		$name = strtolower($aName);
		return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
	}
	
	public function getAttributeNames(): array {
		return array_keys($this->attributes);
	}
	
	public function getInnerHtml() {
		return $this->innerHtml;
	}
	
	public function setInnerHtml(string $aHtml) {
		$this->innerHtml = $aHtml;
	}
	
	public function insertAdjacentHtml(string $aPosition, string $aHtml) {
		if ($aPosition == 'afterbegin') $this->innerHtml = $aHtml . $this->innerHtml;
		else if ($aPosition == 'beforeend') $this->innerHtml = $this->innerHtml . $aHtml;
		
		else throw new \Exception(
			"Unsupported position '{$aPosition}' for " . __METHOD__ . "."
		);
	}
}