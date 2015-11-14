<?php
namespace Lightship;

interface HttpExceptionInterface {
	/**
	 * @return int
	 */
	public function getHttpStatusCode();
}
