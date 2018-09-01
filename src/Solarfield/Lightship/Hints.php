<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;

class Hints implements HintsInterface {
	private $data = [];

	public function get($aPath) {
		return StructUtils::get($this->data, $aPath);
	}

	public function toArray() {
		return $this->data;
	}

	public function set($aPath, $aValue) {
		StructUtils::set($this->data, $aPath, $aValue);
	}

	public function merge($aData) {
		$this->data = StructUtils::merge($this->data, $aData);
	}

	public function mergeReverse($aData) {
		$this->data = StructUtils::merge(StructUtils::toArray($aData), $this->data);
	}
	
	public function __construct($aData = null) {
		$this->merge($aData?:[]);
	}
}
