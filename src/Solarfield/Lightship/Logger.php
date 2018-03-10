<?php
namespace Solarfield\Lightship;

use Psr\Log\AbstractLogger;
use Solarfield\Ok\MiscUtils;

/**
 * A simple logger which just logs the message and context via error_log().
 */
class Logger extends AbstractLogger {
	public function log($level, $message, array $context = []) {
		$msg = $message;
		
		if ($context) $msg .= "\n\n[context] ". MiscUtils::varInfo(MiscUtils::varData($context));
		
		error_log($msg);
	}
}