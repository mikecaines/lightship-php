<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;

class TerminalInput implements \Batten\InputInterface {
	private $data = [];

	public function getAsString($aPath) {
		return StructUtils::get($this->data, $aPath);
	}

	public function getAsArray($aPath) {
		$value = StructUtils::get($this->data, $aPath);
		return is_array($value) ? $value : [];
	}

	public function toArray() {
		return $this->data;
	}

	public function set($aPath, $aValue) {
		StructUtils::set($this->data, $aPath, $aValue);
	}

	public function merge($aData) {
		$incomingData = StructUtils::toArray($aData, true);
		$this->data = StructUtils::merge($this->data, $incomingData);
	}

	public function mergeReverse($aData) {
		$incomingData = StructUtils::toArray($aData, true);
		$this->data = StructUtils::merge($incomingData, $this->data);
	}

	public function importFromGlobals() {
		$args = $_SERVER['argv'];
		array_shift($args);

		foreach ($args as $arg) {
			if (preg_match('/^(-{1,2}[^\s=]+)(?:\=([^ ]*))?$/', $arg, $matches) == 1) {
				if (count($matches) == 3) {
					$this->data[$matches[1]] = $matches[2];
				}
				else {
					$this->data[$matches[1]] = '1';
				}
			}

			else {
				throw new \Exception(
					"Unknown terminal argument: '" . $arg . "'."
				);
			}
		}
	}
}
