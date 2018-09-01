<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;

class TerminalInput implements InputInterface {
	private $data = [];

	static public function fromGlobals(): InputInterface {
		$data = [];
		
		$args = $_SERVER['argv'];
		array_shift($args);
		
		foreach ($args as $arg) {
			if (preg_match('/^(-{1,2}[^\s=]+)(?:\=([^ ]*))?$/', $arg, $matches) == 1) {
				if (count($matches) == 3) {
					$data[$matches[1]] = $matches[2];
				}
				else {
					$data[$matches[1]] = '1';
				}
			}
			
			else {
				throw new \Exception(
					"Unknown terminal argument: '" . $arg . "'."
				);
			}
		}
		
		return new static($data);
	}
	
	public function getAsString($aPath) {
		$value = StructUtils::get($this->data, $aPath);
		return is_array($value) ? '' : (string)$value;
	}

	public function getAsArray($aPath) {
		$value = StructUtils::get($this->data, $aPath);
		return is_array($value) ? $value : [];
	}
	
	public function has($aPath) {
		return StructUtils::scout($this->data, $aPath)[0];
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
	
	public function __construct($aData = null) {
		$this->merge($aData?:[]);
	}
}
