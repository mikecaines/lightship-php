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

		//url to the app source dir
		//NOTE: this defaults to the same as appPackageWebPath, but can be overridden at the app level if desired
		//
		//Possible use cases are:
		//
		//- if you want to use a 'src' dir as a sibling to the 'deps' dir, i.e. /__/src.
		//  Components would therefore be at /__/src/App/Controller.js
		//
		//- if you want to append a virtual dir for versioning/cache-busting purposes.
		//  e.g. /__/@v1.0/App/Controller.js
		$options->add('appSourceWebPath', $options->get('appPackageWebPath'));
	}
}
