<?php
namespace Solarfield\Lightship;

class HttpException extends \Exception implements HttpExceptionInterface {
	private $httpCode;
	private $httpMessage;

	public function getHttpStatusCode() {
		return $this->httpCode;
	}

	public function getHttpStatusMessage() {
		return $this->httpMessage;
	}

	public function __debugInfo() {
		return [
			'message' => $this->getMessage(),
			'code' => $this->getCode(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'trace' => $this->getTrace(),
			'previous' => $this->getPrevious(),
			'httpStatusCode' => $this->getHttpStatusCode(),
			'httpStatusMessage' => $this->getHttpStatusMessage(),
		];
	}

	public function __construct($aMessage, $aCode, $aPrevious, $aHttpStatusCode, $aHttpStatusMessage = null) {
		parent::__construct(
			$aMessage != null ? $aMessage : 'HTTP ' . $aHttpStatusCode,
			$aCode,
			$aPrevious
		);

		$this->httpCode = (int)$aHttpStatusCode;
		$this->httpMessage = (string)$aHttpStatusMessage;
	}
}
