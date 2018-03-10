<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;

class Config implements \IteratorAggregate {
	private $data;

	public function get($aName) {
		return array_key_exists($aName, $this->data) ? $this->data[$aName] : null;
	}
	
	public function has($aPath): bool {
		return StructUtils::scout($this->data, $aPath)[0];
	}

	public function __construct(array $aData) {
		$this->data = $aData;
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->data);
	}
}