<?php
namespace Solarfield\Lightship;

class HttpException extends \Exception implements HttpExceptionInterface {
	private $httpCode;

	public function getHttpStatusCode() {
		return $this->httpCode;
	}

	public function __construct($aMessage, $aCode, $aPrevious, $aHttpCode) {
		parent::__construct(
			$aMessage != null ? $aMessage : 'HTTP ' . $aHttpCode,
			$aCode,
			$aPrevious
		);

		$this->httpCode = (int)$aHttpCode;
	}
}
