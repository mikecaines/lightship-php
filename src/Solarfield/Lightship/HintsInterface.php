<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\ToArrayInterface;

interface HintsInterface extends ToArrayInterface {
	public function merge($aData);

	public function mergeReverse($aData);

	/**
	 * @param string $aPath
	 * @param array|string $aValue
	 */
	public function set($aPath, $aValue);

	/**
	 * @param $aPath
	 * @return mixed
	 */
	public function get($aPath);
}
