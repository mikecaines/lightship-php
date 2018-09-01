<?php
namespace Solarfield\Lightship;

use Solarfield\Lightship\Http\ServerRequest;
use Throwable;

abstract class WebBootstrapper {
	/**
	 * @param array $aOptions
	 * @return int The exit status code.
	 */
	static public function go(array $aOptions = []) {
		$exitCode = 1;
		
		try {
			//register the composer autoloader
			require_once $aOptions['projectPackageFilePath'] . '/vendor/autoload.php';
			$autoloader = new \Composer\Autoload\ClassLoader();
			$autoloader->addPsr4('App\\', realpath($aOptions['appPackageFilePath'] . '/App'));
			$autoloader->register();
			
			//boot the environment
			$environment = new \App\Environment();
			$environment->init([
				'projectPackageFilePath' => $aOptions['projectPackageFilePath'],
				'appPackageFilePath' => $aOptions['appPackageFilePath'],
			]);
			
			// create the boot context (representing the current http request)
			$context = WebContext::fromRequest(ServerRequest::fromGlobals());
			
			//boot the controller
			$exitCode = \App\Controller::bootstrap($environment, $context);
		}
		
		catch (Throwable $e) {
			error_log($e);
		}
		
		return $exitCode;
	}
}
