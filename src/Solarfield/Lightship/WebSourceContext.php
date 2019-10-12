<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

use Psr\Http\Message\ServerRequestInterface;
use Solarfield\Ok\Url;

class WebSourceContext extends SourceContext {
	static public function fromRequest(ServerRequestInterface $aRequest): WebSourceContext {
		$context = new static([
			'url' => (string)$aRequest->getUri(),
			'input' => WebInput::fromRequest($aRequest),
			'headers' => $aRequest->getHeaders(),
		]);

		// resolve the route from the request
		$context->setRoute([
			'nextStep' => (new Url($context->getUrl()))->getPath(),
		]);

		return $context;
	}

	/** @var string */ private $url;
	/** @var array */ private $headers;

	public function getUrl(): string {
		return $this->url;
	}

	public function getHeader(string $aName) {
		$name = strtolower($aName);
		if (array_key_exists($name, $this->headers)) return $this->headers[$name][0];
		return null;
	}

	public function toParts(): array {
		$parts = parent::toParts();

		$parts['url'] = $this->getUrl();
		$parts['headers'] = $this->headers;

		return $parts;
	}

	public function __construct(array $aOptions = null) {
		$options = array_replace([
			'input' => null,
			'url' => null,
			'headers' => null,
		], $aOptions?:[]);

		if ($options['input']) {
			if (!($options['input'] instanceof WebInput)) throw new \Exception(
				"Option input must be an instance of WebInput."
			);
		}
		else {
			$options['input'] = new WebInput();
		}

		$this->url = (string)$options['url'];

		$this->headers = [];
		if ($options['headers']) {
			foreach ($options['headers'] as $name => $values) {
				$name = strtolower($name);

				foreach ($values as $value) {
					$this->headers[$name][] = (string)$value;
				}
			}
		}

		parent::__construct($options);
	}
}
