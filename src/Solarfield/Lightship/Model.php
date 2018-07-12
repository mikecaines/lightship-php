<?php
namespace Solarfield\Lightship;

use \app\Environment as Env;
use Solarfield\Ok\StructUtils;

class Model implements ModelInterface {
	private $code;
	private $data = [];

	public function getCode() {
		return $this->code;
	}

	public function set($aPath, $aObject) {
		StructUtils::set($this->data, $aPath, $aObject);
	}

	public function push($aPath, $aObject) {
		StructUtils::pushSet($this->data, $aPath, $aObject);
	}

	public function merge($aData) {
		$this->data = StructUtils::merge($this->data, $aData);
	}

	public function get($aPath) {
		return StructUtils::get($this->data, $aPath);
	}

	public function getAsArray($aPath) {
		$value = StructUtils::get($this->data, $aPath);
		return is_array($value) ? $value : [];
	}

	public function toArray() {
		return $this->data;
	}

	public function init() {
		//this method provides a hook to resolve plugins, options, etc.
	}

	public function __construct($aCode) {
		if (Env::getVars()->get('logComponentLifetimes')) {
			Env::getLogger()->debug(get_class($this) . "[code=" . $aCode . "] was constructed.");
		}

		$this->code = (string)$aCode;
	}

	public function __destruct() {
		if (Env::getVars()->get('logComponentLifetimes')) {
			Env::getLogger()->debug(get_class($this) . "[code=" . $this->getCode() . "] was destructed.");
		}
	}
}
