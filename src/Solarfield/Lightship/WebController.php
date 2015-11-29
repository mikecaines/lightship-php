<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Batten\Event;
use Solarfield\Batten\Reflector;
use Solarfield\Batten\StandardOutputEvent;
use Solarfield\Ok\Url;

/**
 * Class WebController
 * @method \Solarfield\Batten\Model getModel
 */
abstract class WebController extends \Solarfield\Batten\Controller {
	static public function boot() {
		header('X-Request-Guid: ' . Env::getVars()->get('requestId'));

		parent::boot();
	}

	static public function getInitialRoute() {
		$route = (new Url($_SERVER['REQUEST_URI']))->getPath();
		if ($route == '/') $route = '';

		return $route;
	}

	private $redirecting = false;

	protected function resolveOptions() {
		parent::resolveOptions();

		$options = $this->getOptions();
		$options->add('app.allowCachedResponse', true);

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-resolve-options', ['target' => $this])
			);
		}
	}

	protected function doLoadServerData() {
		$model = $this->getModel();

		$baseChain = array();
		foreach (Env::getBaseChain() as $k => $v) {
			//TODO: this should be defaulted elsewhere
			$v = array_replace([
				'namespace' => null,
				'pluginsSubNamespace' => '\\Plugins',
			], $v);

			$link = array(
				'namespace' => str_replace('\\', '.', $v['namespace']),
				'pluginsSubNamespace' => array_key_exists('pluginsSubNamespace', $v) ? str_replace('\\', '.', $v['pluginsSubNamespace']) : null,
			);

			$baseChain[$k] = $link;
		}

		$serverData = [
			'pluginRegistrations' => $this->getPlugins()->getRegistrations(),
			'baseChain' => $baseChain,
		];

		$model->set('app.serverData', $serverData);
	}

	public function processRoute($aInfo) {
		$info = parent::processRoute($aInfo);
		if ($info) return $info;

		$buffer = [
			'inputRoute' => $aInfo,
			'outputRoute' => null,
		];

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new ArrayBufferEvent('app-process-route', ['target' => $this], $buffer)
			);
		}

		if ($buffer['outputRoute']) {
			return $buffer['outputRoute'];
		}

		return null;
	}

	public function getRequestedViewType() {
		$type = trim($this->getInput()->getAsString('app.viewType.code'));

		if ($type === '') $type = $this->getDefaultViewType();

		return $type;
	}

	public function createInput() {
		return new WebInput();
	}

	public function queueRedirect($aUrl, $aOptions = []) {
		$options = array_replace([
			'httpStatusCode' => 301,
		], $aOptions);

		$this->getModel()->set('app.queuedRedirect', [
			'url' => (string)$aUrl,
			'httpStatusCode' => (int)$options['httpStatusCode'],
		]);
	}

	public function goTasks() {
		//if a redirect is queued, we will not call doTask().
		//Redirects at this level would normally only come from routing.
		//Redirecting here is ideal, because it is early and inexpensive.

		$queuedRedirect = $this->getModel()->get('app.queuedRedirect');

		if ($queuedRedirect) {
			//flag that we are redirecting. This is used by goRender()
			$this->redirecting = true;

			header('Location: ' . $queuedRedirect['url'], true, $queuedRedirect['httpStatusCode']);
		}

		else {
			$this->doTask();
		}
	}

	public function goRender() {
		//if we are already flagged as redirecting, do nothing
		if (!$this->redirecting) {
			//if a redirect is queued, don't bother rendering (i.e. sending any response body).
			//Redirects at this level would normally come from within doTask().
			//Redirecting here is a little more tricky because doTask() has already been called, and is expected to
			//terminate normally. This method could be implemented in a different way, such as not sending a Location header,
			//and instead rendering a 'redirecting you to ...' page for the user.

			$queuedRedirect = $this->getModel()->get('app.queuedRedirect');

			if ($queuedRedirect) {
				header('Location: ' . $queuedRedirect['url'], true, $queuedRedirect['httpStatusCode']);
			}

			else {
				$view = $this->getView();

				if ($view) {
					//erase any buffered output.
					//There could be buffered output if we are in a reboot
					while (ob_get_level() > 0) {
						ob_end_clean();
					}

					$view->render();
				}
			}
		}
	}

	public function doTask() {
		parent::doTask();

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-before-do-task', ['target' => $this])
			);
		}

		$hints = $this->getHints();
		$moduleOptions = $this->getOptions();

		if (!$moduleOptions->get('app.allowCachedResponse')) {
			header('Cache-Control: no-cache, no-store, must-revalidate');
		}

		if ($hints->get('doLoadServerData')) {
			$this->doLoadServerData();
		}

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-do-task', ['target' => $this])
			);
		}
	}

	public function handleException(\Exception $aEx) {
		//log the error
		Env::getLogger()->error($aEx);

		//reboot to the 'Error' module.
		//We use the Error module to present error messages to the client
		static::reboot(
			[
				'moduleCode' => 'Error', //boot to the 'Error' module
				'nextRoute' => static::getInitialRoute(), //process the entire route again
			],

			//specify some initial model data, such as the exception we are handling
			[
				'app' => [
					'errorState' => [
						'error' => $aEx,
					]
				]
			]
		);
	}

	public function handleStandardOutput(StandardOutputEvent $aEvt) {
		$this->getModel()->push('app.standardOutput.messages', [
			'message' => $aEvt->getText(),
		]);

		if (\App\DEBUG) {
			Env::getLogger()->debug($aEvt->getText());
		}
	}

	public function markResolved() {
		parent::markResolved();

		Env::getStandardOutput()->addEventListener('standard-output', [$this, 'handleStandardOutput']);
	}

	public function __construct($aCode) {
		parent::__construct($aCode);

		$this->setDefaultViewType('Html');
	}
}
