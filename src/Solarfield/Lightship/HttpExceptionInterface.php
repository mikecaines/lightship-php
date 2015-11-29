<?php
namespace Solarfield\Lightship;

interface HttpExceptionInterface {
	/**
	 * @return int
	 */
	public function getHttpStatusCode();
}
