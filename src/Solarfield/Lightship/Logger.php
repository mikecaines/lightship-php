<?php
namespace Solarfield\Lightship;


use Solarfield\Ok\MiscUtils;

class Logger implements LoggerInterface {
	protected function processEntry($aMessage, $aContext = null, $aType) {
		$output = '';

		switch ($aType) {
			case \Solarfield\Lightship\LOG_LEVEL_INFO: $typeName = 'INFO'; break;
			case \Solarfield\Lightship\LOG_LEVEL_WARNING: $typeName = 'WARNING'; break;
			case \Solarfield\Lightship\LOG_LEVEL_ERROR: $typeName = 'ERROR'; break;
			case \Solarfield\Lightship\LOG_LEVEL_DEBUG: $typeName = 'DEBUG'; break;
			default: $typeName = 'OTHER';
		}
		$output .= '[type=' . $typeName . ']';

		$output .= ' ' . $aMessage;

		if ($aContext !== null) {
			$output .= "\n\n[context] " . MiscUtils::varInfo($aContext);
		}

		error_log($output);
	}

	public function info($aMessage, $aContext = null) {
		$this->processEntry($aMessage, $aContext, LOG_LEVEL_INFO);
	}

	public function warn($aMessage, $aContext = null) {
		$this->processEntry($aMessage, $aContext, LOG_LEVEL_WARNING);
	}

	public function error($aMessage, $aContext = null) {
		$this->processEntry($aMessage, $aContext, LOG_LEVEL_ERROR);
	}

	public function debug($aMessage, $aContext = null) {
		$this->processEntry($aMessage, $aContext, LOG_LEVEL_DEBUG);
	}
}
