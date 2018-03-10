<?php
namespace Solarfield\Lightship;

use Exception;

class Options implements
	\Solarfield\Ok\ToArrayInterface,
	\IteratorAggregate
{
	private $data = [];
	private $readOnly;

	public function add($aCode, $aValue) {
		if (!$this->has($aCode)) {
			$this->set($aCode, $aValue);
		}
	}

	public function set($aCode, $aValue) {
		if ($this->readOnly && $this->has($aCode)) {
			throw new Exception(
				"Option '$aCode' is read only."
			);
		}

		if (!(is_scalar($aValue) || $aValue === null)) {
			throw new Exception(
				"Option values must be scalar or null."
			);
		}

		$this->data[(string)$aCode] = $aValue;
	}

	public function get($aCode) {
		if (!$this->has($aCode)) {
			throw new Exception(
				"Unknown option: '" . $aCode . "'."
			);
		}

		return $this->data[$aCode];
	}

	public function has($aCode) {
		return array_key_exists($aCode, $this->data);
	}

	public function toArray() {
		return $this->data;
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->data);
	}
	
	function __construct($aOptions = []) {
		$this->readOnly = array_key_exists('readOnly', $aOptions) ? (bool)$aOptions['readOnly'] : false;
	}
}
