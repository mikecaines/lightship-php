<?php
namespace Solarfield\Lightship;

class StringProxy {
	private $str;

	public function &getData() {
		return $this->str;
	}

	public function prepend(string $aString) {
		$this->str = $aString . $this->str;
	}

	public function append(string $aString) {
		$this->str .= $aString;
	}

	public function toString() {
		return $this->str;
	}

	public function __toString() {
		return $this->toString();
	}

	public function __construct(array &$aStr = null) {
		if ($aStr != null) {
			$this->str = &$aStr;
		}
		else {
			$this->str = '';
		}
	}
}