<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;
use Solarfield\Ok\ToArrayInterface;

class JsEnvironment implements ToArrayInterface {
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

	public function push($aPath, $aValue) {
		StructUtils::pushSet($this->data, $aPath, $aValue);
	}

	public function merge($aData) {
		$this->data = StructUtils::merge($this->data, $aData);
	}

	public function mergeReverse($aData) {
		$this->data = StructUtils::merge(StructUtils::toArray($aData), $this->data);
	}
}
