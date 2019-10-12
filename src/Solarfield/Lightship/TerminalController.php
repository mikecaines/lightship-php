<?php
namespace Solarfield\Lightship;

use Solarfield\Lightship\Events\DoTaskEvent;
use Throwable;

/**
 * Class TerminalController
 * @package Solarfield\Lightship
 *
 * @method TerminalSourceContext getContext
 */
abstract class TerminalController extends Controller {
	static public function bail(EnvironmentInterface $aEnvironment, \Throwable $aException): DestinationContextInterface {
		$aEnvironment->getLogger()->error($aException->getMessage(), [
			'exception' => $aException,
		]);

		$aEnvironment->getStandardOutput()->error('FATAL ERROR: ' . $aException->getMessage());

		return new TerminalDestinationContext(1);
	}

	static public function route(EnvironmentInterface $aEnvironment, SourceContextInterface $aContext): SourceContextInterface {
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

	public function run(): DestinationContextInterface {
		// connect the view
		$view = $this->createView($this->getRequestedViewType());
		$view->setController($this->getProxy());
		$view->init();
		$this->getInput()->mergeReverse($view->getInput());
		$this->getHints()->mergeReverse($view->getHints());
		$view->setModel($this->getModel());

		$destinationContext = new TerminalDestinationContext();

		$destinationContext = $this->doTask($destinationContext);
		$destinationContext = $view->render($destinationContext);

		return $destinationContext;
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

	public function handleException(Throwable $aEx) : DestinationContextInterface {
		return static::bail($this->getEnvironment(), $aEx);
	}

	public function __construct(EnvironmentInterface $aEnvironment, $aCode, SourceContextInterface $aContext, $aOptions = []) {
		parent::__construct($aEnvironment, $aCode, $aContext, $aOptions);

		$this->setDefaultViewType('Stdout');
	}
}
