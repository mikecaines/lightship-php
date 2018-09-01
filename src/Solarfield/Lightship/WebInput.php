<?php
namespace Solarfield\Lightship;

use Psr\Http\Message\ServerRequestInterface;
use Solarfield\Ok\StringUtils;
use Solarfield\Ok\StructUtils;

class WebInput implements InputInterface {
	private $data = [];

	static private function normalize($aArray) {
		$arr = [];

		foreach ($aArray as $k => $v) {
			$k = StringUtils::dashToCamel($k);
			$arr[$k] = is_array($v) ? static::normalize($v) : $v;
		}

		return $arr;
	}
	
	static public function fromRequest(ServerRequestInterface $aRequest) : InputInterface {
		$data = [];
		
		// import query params
		$requestData = $aRequest->getQueryParams();
		$requestData = static::normalize($requestData);
		$requestData = StructUtils::unflatten($requestData, '_');
		$data = StructUtils::merge($data, $requestData);
		
		// import post
		$requestData = $aRequest->getParsedBody();
		if (is_array($requestData)) {
			$requestData = static::normalize($requestData);
			$requestData = StructUtils::unflatten($requestData, '_');
			$data = StructUtils::merge($data, $requestData);
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
		$incomingData = StructUtils::unflatten($incomingData, '.');

		$this->data = StructUtils::merge($this->data, $incomingData);
	}

	public function mergeReverse($aData) {
		$incomingData = StructUtils::toArray($aData, true);
		$incomingData = StructUtils::unflatten($incomingData, '.');

		$this->data = StructUtils::merge($incomingData, $this->data);
	}
	
	public function __construct($aData = null) {
		$this->merge($aData?:[]);
	}
}
