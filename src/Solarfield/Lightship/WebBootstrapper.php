<?php
namespace Solarfield\Lightship;

use Solarfield\Lightship\Http\ServerRequest;
use Solarfield\Ok\HttpUtils;

abstract class WebBootstrapper {
	/**
	 * @param array $aOptions
	 * @throws \Exception
	 */
	static public function go(array $aOptions = []) {
		//register the composer autoloader
		require_once $aOptions['projectPackageFilePath'] . '/vendor/autoload.php';
		$autoloader = new \Composer\Autoload\ClassLoader();
		$autoloader->addPsr4('App\\', realpath($aOptions['appPackageFilePath'] . '/App'));
		$autoloader->register();

		//boot the environment
		$environment = new \App\Environment([
			'projectPackageFilePath' => $aOptions['projectPackageFilePath'],
			'appPackageFilePath' => $aOptions['appPackageFilePath'],
		]);
		$environment->init();

		// create the boot context (representing the current http request)
		$context = WebSourceContext::fromRequest(ServerRequest::fromGlobals());

		//boot the controller
		/** @var WebDestinationContext $destinationContext */
		$destinationContext = \App\Controller::boot($environment, $context);

		//send the http response
		$response = $destinationContext->toResponse();
		header(HttpUtils::createStatusHeader($response->getStatusCode(), $response->getReasonPhrase()));
		foreach ($response->getHeaders() as $name => $values) {
			foreach ($values as $value) {
				header($name . ':' . $value);
			}
		}
		echo((string)$response->getBody());
	}
}
