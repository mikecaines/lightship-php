<?php
namespace Lightship;

class ArrayBufferEvent extends \Batten\Event {
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
