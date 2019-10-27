<?php
namespace Solarfield\Lightship;

use Throwable;

abstract class WebEnvironment extends Environment {
	public function bail(Throwable $aEx) : DestinationContextInterface {
		$this->getLogger()->error("Bailed.", ['exception'=>$aEx]);
		return new WebDestinationContext(500);
	}

	public function __construct($aOptions) {
		parent::__construct($aOptions);
		$options = $this->getVars();

		//url to the app package dir, i.e. /__
		$path = preg_replace('/^' . preg_quote(realpath($_SERVER['DOCUMENT_ROOT']), '/') . '/', '', realpath($options->get('appPackageFilePath')));
		$path = str_replace('\\', '/', $path);
		$options->add('appPackageWebPath', $path);

		//url to app dependencies dir, i.e. /__/deps
		$options->add('appDependenciesWebPath', $options->get('appPackageWebPath') . '/deps');
	}
}
