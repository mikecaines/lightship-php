<?php

namespace Solarfield\Lightship;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class WebDestinationContext implements DestinationContextInterface {
	private $statusCode;
	private $statusMessage;
	private $headers;
	private $body;

	private function replaceHeader(string $aName, array $aValue) {
		$this->headers[strtolower($aName)] = $aValue;
	}

	public function getStatus() : int {
		return $this->statusCode;
	}

	public function setStatus(int $aCode, string $aMessage = null) {
		$this->statusCode = $aCode;
		$this->statusMessage = $aMessage;
	}

	public function getHeader(string $aName) {
		$name = strtolower($aName);
		if (array_key_exists($name, $this->headers)) return $this->headers[$name][0];
		return null;
	}

	public function setHeader(string $aName, string $aValue) {
		$this->replaceHeader($aName, [$aValue]);
	}

	public function queueRedirect(string $aUrl, int $aStatusCode = 302, string $aStatusMessage = null) {
		$this->statusCode = $aStatusCode;
		$this->statusMessage = $aStatusMessage;
		$this->replaceHeader('location', [$aUrl]);
	}

	public function setBody($aBody) {
		$this->body = stream_for($aBody);
	}

	public function getBody() : StreamInterface {
		return $this->body;
	}

	public function toResponse() : ResponseInterface {
		$response = new Response($this->statusCode, $this->headers, $this->body, null, $this->statusMessage);
		return $response;
	}

	public function __construct(int $aStatus = 200, array $aHeaders = null, $aBody = null) {
		$this->headers = [];
		$this->body = stream_for($aBody);
		$this->statusCode = $aStatus;

		if ($aHeaders) {
			foreach ($aHeaders as $name => $values) {
				if (!is_array($values)) $values = [$values];

				foreach ($values as $value) {
					$this->headers[$name][] = $value;
				}
			}
		}
	}
}
