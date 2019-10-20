<?php
namespace Solarfield\Lightship;

abstract class TerminalBootstrapper {
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

		// create the boot context (representing the current invocation of the script)
		$context = TerminalSourceContext::fromGlobals();

		//boot the controller
		/** @var TerminalDestinationContext $destinationContext */
		$destinationContext = \App\Controller::boot($environment, $context);

		// terminate with the specified exit status
		exit($destinationContext->getExitStatus());
	}
}
