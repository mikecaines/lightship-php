<?php
namespace Solarfield\Lightship;

class UserFriendlyException extends \Exception implements UserFriendlyExceptionInterface {
	public function getUserFriendlyMessage() {
		return $this->getMessage();
	}

	public function __debugInfo() {
		return [
			'message' => $this->getMessage(),
			'code' => $this->getCode(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'trace' => $this->getTrace(),
			'previous' => $this->getPrevious(),
			'userFriendlyMessage' => $this->getUserFriendlyMessage(),
		];
	}
}
