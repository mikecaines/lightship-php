<?php
declare(strict_types=1);

namespace Solarfield\Lightship;

use Psr\Http\Message\ServerRequestInterface;
use Solarfield\Ok\Url;

class WebContext extends Context {
	static public function fromRequest(ServerRequestInterface $aRequest): WebContext {
		$context = new static([
			'url' => (string)$aRequest->getUri(),
			'input' => WebInput::fromRequest($aRequest),
		]);

		// resolve the route from the request
		$context->setRoute([
			'nextStep' => (new Url($context->getUrl()))->getPath(),
		]);

		return $context;
	}

	/** @var string */ private $url;

	public function getUrl(): string {
		return $this->url;
	}

	public function __construct(array $aOptions = null) {
		$options = array_replace([
			'input' => null,
			'url' => null,
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

		parent::__construct($options);
	}
}
