<?php
namespace Solarfield\Lightship;

use Solarfield\Lightship\Events\DoTaskEvent;
use Throwable;

/**
 * Class TerminalController
 * @package Solarfield\Lightship
 *
 * @method TerminalContext getContext() : ContextInterface
 */
abstract class TerminalController extends Controller {
	static public function route(EnvironmentInterface $aEnvironment, ContextInterface $aContext): ContextInterface {
		$context = parent::route($aEnvironment, $aContext);

		if (($inputModule = $context->getRoute()->getNextStep()) !== null) {
			$availableModules = array_filter(
				scandir($aEnvironment->getVars()->get('appPackageFilePath') . '/App/Modules'),
				function ($name) {
					return preg_match('/[a-z0-9]+/i', $name) == true;
				}
			);

			if (in_array($inputModule, $availableModules, true)) {
				$context->setRoute([
					'moduleCode' => (string)$inputModule,
				]);
			}
		}

		return $context;
	}

	protected function executeScript() {
		//NOTE: override this method to do your module-specific stuff
	}

	public function getRequestedViewType() {
		$type = trim($this->getInput()->getAsString('--view'));

		if ($type === '') $type = $this->getDefaultViewType();

		return $type;
	}

	public function runTasks() {
		$this->doTask();
	}

	public function runRender() {
		$view = $this->getView();

		if ($view) {
			$view->render();
		}
	}

	public function onDoTask(DoTaskEvent $aEvt) {
		$startTime = microtime(true);

		$input = $this->getInput();
		$stdout = $this->getEnvironment()->getStandardOutput();

		$verbose = (bool)$input->getAsString('--verbose');

		//check if the resolved controller is App\Controller (i.e. no matching module exists).
		//If it is, the --module argument was probably mistyped.
		$thisClass = get_class($this);
		if ($thisClass == 'App\Controller') {
			$stdout->warning("Warning: Script controller resolved to app-level '$thisClass'.");
		}

		if ($verbose) {
			$stdout->write("Script '" . $this->getCode() . "' started at " . date('c', $startTime) . '.');
		}

		$this->executeScript();

		$endTime = microtime(true);

		if ($verbose) {
			$stdout->write("Script '" . $this->getCode() . "' ended at " . date('c', $endTime) . '.');
			$stdout->write('Script duration was ' . round($endTime - $startTime, 2) . ' seconds.');
		}
	}

	public function handleException(Throwable $aEx) {
		$this->getLogger()->error($aEx->getMessage(), [
			'exception' => $aEx,
		]);

		$this->getEnvironment()->getStandardOutput()->error('FATAL ERROR: ' . $aEx->getMessage());
	}

	public function __construct(EnvironmentInterface $aEnvironment, $aCode, ContextInterface $aContext, $aOptions = []) {
		parent::__construct($aEnvironment, $aCode, $aContext, $aOptions);

		$this->setDefaultViewType('Stdout');
	}
}
