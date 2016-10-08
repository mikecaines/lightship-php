<?php
namespace Solarfield\Lightship;

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
