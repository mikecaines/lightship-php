<?php
namespace Solarfield\Lightship;

use Exception;

class UnresolvedRouteException extends \Exception {
	private $routeInfo;

	public function getRouteInfo() {
		return $this->routeInfo;
	}

	public function __debugInfo() {
		return [
			'message' => $this->getMessage(),
			'code' => $this->getCode(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'trace' => $this->getTrace(),
			'previous' => $this->getPrevious(),
			'routeInfo' => $this->getRouteInfo(),
		];
	}

	public function __construct($message = null, $code = 0, Exception $previous = null, $aRouteInfo = null) {
		parent::__construct($message, $code, $previous);
		$this->routeInfo = $aRouteInfo;
	}
}
