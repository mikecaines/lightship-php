<?php
namespace Solarfield\Lightship;

const LOG_LEVEL_ERROR = 1000;
const LOG_LEVEL_WARNING = 2000;
const LOG_LEVEL_INFO = 3000;
const LOG_LEVEL_DEBUG = 4000;

interface LoggerInterface {
	public function warn($aMessage, $aDetails = null);
	public function error($aMessage, $aDetails = null);
	public function info($aMessage, $aDetails = null);
	public function debug($aMessage, $aDetails = null);
}
