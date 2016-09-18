<?php
namespace Solarfield\Lightship;

abstract class Bootstrapper {
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
			\App\Environment::init([
				'projectPackageFilePath' => $aOptions['projectPackageFilePath'],
				'appPackageFilePath' => $aOptions['appPackageFilePath'],
			]);

			//boot the controller
			$exitCode = \App\Controller::bootstrap();
		}

		catch (\Exception $e) {
			error_log($e);
		}

		return $exitCode;
	}
}