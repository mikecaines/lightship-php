<?php
namespace Solarfield\Lightship\Errors;

interface HttpExceptionInterface {
	/**
	 * @return int
	 */
	public function getHttpStatusCode();

	/**
	 * @return string|null
	 */
	public function getHttpStatusMessage();
}
