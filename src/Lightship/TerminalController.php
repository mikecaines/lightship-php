<?php
namespace Lightship;

use App\Environment as Env;
use Ok\StructUtils;

abstract class TerminalController extends \Batten\Controller {
	static public function getInitialRoute() {
		$route = null;

		$input = new TerminalInput();
		$input->importFromGlobals();
		$input = StructUtils::toArray($input);
		$route = StructUtils::get($input, '--module');

		return $route;
	}

	protected function executeScript() {
		//NOTE: override this method to do your module-specific stuff
	}

	public function getRequestedViewType() {
		$type = trim($this->getInput()->getAsString('--view'));

		if ($type === '') $type = $this->getDefaultViewType();

		return $type;
	}

	public function createInput() {
		return new TerminalInput();
	}

	public function goTasks() {
		$this->doTask();
	}

	public function goRender() {
		$view = $this->getView();

		if ($view) {
			$view->render();
		}
	}

	public function processRoute($aInfo) {
		//because of how we implemented getInitialRoute(),
		//$aInfo['nextRoute'] will contain the value of the --module argument.
		//The value is expected to be a module code, in camelCase form.
		//So here, we just route to that module

		return [
			'moduleCode' => $aInfo['nextRoute'],
		];
	}

	public function doTask() {
		parent::doTask();

		$startTime = microtime(true);

		$input = $this->getInput();
		$stdout = Env::getStandardOutput();

		$verbose = (bool)$input->getAsString('--verbose');

		//check if the resolved controller is App\Controller (i.e. no matching module exists).
		//If it is, the --module argument was probably mistyped.
		$thisClass = get_class($this);
		if ($thisClass == 'App\Controller') {
			$stdout->write("Warning: Script controller resolved to app-level '$thisClass'.");
		}

		if ($verbose) {
			$stdout->write("Script '" . $this->getCode() . "' started at " . date('c', $startTime) . '.');
			$stdout->write("Request ID is " . Env::getVars()->get('requestId') . ".");
		}

		$this->executeScript();

		$endTime = microtime(true);

		if ($verbose) {
			$stdout->write("Script '" . $this->getCode() . "' ended at " . date('c', $endTime) . '.');
			$stdout->write('Script duration was ' . round($endTime - $startTime, 2) . ' seconds.');
		}
	}

	public function handleException(\Exception $aEx) {
		Env::getLogger()->error($aEx);
		Env::getStandardOutput()->write('FATAL ERROR: ' . $aEx->getMessage());
	}

	public function __construct($aCode) {
		parent::__construct($aCode);

		$this->setDefaultViewType('Stdout');
	}
}
