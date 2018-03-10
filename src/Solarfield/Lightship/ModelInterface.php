<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\ToArrayInterface;

interface ModelInterface extends ToArrayInterface {
	public function getCode();
	public function set($aPath, $aObject);
	public function merge($aData);
	public function get($aPath);
	public function getAsArray($aPath);
	public function init();
}
