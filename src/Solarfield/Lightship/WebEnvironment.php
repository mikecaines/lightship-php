<?php
namespace Solarfield\Lightship;

abstract class WebEnvironment extends Environment {
	private $stdoutMessages = [];

	public function takeBufferedStdoutMessages() : array {
		return array_splice($this->stdoutMessages, 0);
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

		//TODO: move this to a LightshipBridge plugin
		$this->getStandardOutput()->addEventListener('standard-output', function (StandardOutputEvent $aEvt) {
			// buffer the message
			$this->stdoutMessages[] = [
				'message' => $aEvt->getText(),
				'level' => $aEvt->getLevel(),
				'context' => $aEvt->getContext(),
			];

			if (\App\DEBUG) {
				$this->getLogger()->debug('[stdout] [' . $aEvt->getLevel() . '] ' . $aEvt->getText(), $aEvt->getContext());
			}
		});
	}
}
