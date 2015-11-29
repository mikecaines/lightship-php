<?php
namespace Solarfield\Lightship;

class ArrayBufferEvent extends \Solarfield\Batten\Event {
	public $buffer;

	/**
	 * @param string $aType
	 * @param array $aInfo
	 * @param array $aBuffer
	 */
	public function __construct($aType, $aInfo = [], &$aBuffer) {
		parent::__construct($aType, $aInfo);

		$this->buffer =& $aBuffer;
	}
}
