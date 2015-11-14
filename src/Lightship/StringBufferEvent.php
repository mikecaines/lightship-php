<?php
namespace Lightship;

class StringBufferEvent extends \Batten\Event {
	public $buffer;

	/**
	 * @param $aType
	 * @param array $aInfo
	 * @param string $aBuffer
	 */
	public function __construct($aType, $aInfo = [], &$aBuffer) {
		parent::__construct($aType, $aInfo);

		$this->buffer =& $aBuffer;
	}
}
