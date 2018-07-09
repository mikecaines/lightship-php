<?php
namespace Solarfield\Lightship;

/**
 * Provides one-time read access to an array of string-based key/value pairs.
 * Config entries are normally read once, processed in some way (e.g. defaulted),
 * and then expressed as environment variables.
 */
class Config {
	private $data;
	private $read = [];

	public function get($aName) {
		if (array_key_exists($aName, $this->read)) throw new \Exception(
			"Config value '{$aName}' has already been read."
		);
		
		if (array_key_exists($aName, $this->data)) {
			$value = (string)$this->data[$aName];
			$this->read[$aName] = null;
			unset($this->data[$aName]);
			return $value;
		}
		
		return null;
	}
	
	public function has($aName): bool {
		if (array_key_exists($aName, $this->read)) throw new \Exception(
			"Config value '{$aName}' has already been read."
		);
		
		return array_key_exists($aName, $this->data);
	}
	
	public function keys() {
		return array_keys($this->data);
	}

	public function __construct(array $aData) {
		$this->data = $aData;
	}
}
