<?php
namespace Solarfield\Lightship;

require_once __DIR__ . '/Environment.php';

abstract class WebEnvironment extends Environment {
	public function init($aOptions) {
		parent::init($aOptions);
		$options = $this->getVars();

		//url to the app package dir, i.e. /__
		$path = preg_replace('/^' . preg_quote(realpath($_SERVER['DOCUMENT_ROOT']), '/') . '/', '', realpath($options->get('appPackageFilePath')));
		$path = str_replace('\\', '/', $path);
		$options->add('appPackageWebPath', $path);

		//url to app dependencies dir, i.e. /__/deps
		$options->add('appDependenciesWebPath', $options->get('appPackageWebPath') . '/deps');
	}
}
