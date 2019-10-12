<?php
namespace Solarfield\Lightship;

use Exception;
use Solarfield\Lightship\Errors\HttpException;
use Solarfield\Lightship\Errors\UnresolvedRouteException;
use Solarfield\Lightship\Errors\UserFriendlyException;
use Throwable;

/**
 * Class WebController
 * @package Solarfield\Lightship
 *
 * @method WebContext getContext() : ContextInterface
 */
abstract class WebController extends Controller {
	private $redirecting = false;

	public function getRequestedViewType() {
		$type = trim($this->getInput()->getAsString('app.viewType.code'));

		if ($type === '') $type = $this->getDefaultViewType();

		if (!preg_match('/^[a-z0-9]+$/i', $type)) throw new Exception(
			"Requested view type contains invalid characters: '$type'."
		);

		return $type;
	}

	public function queueRedirect($aUrl, $aOptions = []) {
		$options = array_replace([
			'httpStatusCode' => 302,
		], $aOptions);

		$this->getModel()->set('app.queuedRedirect', [
			'url' => (string)$aUrl,
			'httpStatusCode' => (int)$options['httpStatusCode'],
		]);
	}

	public function runTasks() {
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

	public function runRender() {
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

	public function handleException(Throwable $aEx) {
		$finalEx = $aEx;

		if ($finalEx instanceof UserFriendlyException) {
			//do nothing, as UserFriendlyException's are considered low-priority
		}

		else {
			//if the exception is due to an unresolved route
			if ($finalEx instanceof UnresolvedRouteException) {
				//interpret it as a 404, and wrap the original exception
				$finalEx = new HttpException(
					$finalEx->getMessage(), $finalEx->getCode(), $finalEx,
					404
				);
			}

			//log the error
			$this->getLogger()->error($finalEx->getMessage(), [
				'exception' => $finalEx
			]);
		}

		
		//reboot to the 'Error' module
		//We create a totally new context here, but do preserve the boot history, so that we avoid
		//infinite loops caused by any continuously failing error recovery.
		
		$context = new WebContext([
			'route' => new Route([
				'moduleCode' => 'Error', //boot to the 'Error' module
				
				//specify some initial hints, such as the exception we are handling
				'hints' => [
					'app' => [
						'errorState' => [
							'error' => $finalEx,
						],
					],
				],
			]),
			
			'url' => $this->getContext()->getUrl(),
			'bootPath' => $this->getContext()->getBootPath(),
			'bootRecoveryCount' => $this->getContext()->getBootRecoveryCount() + 1,
		]);
		
		$controller = static::boot(
			$this->getEnvironment(),
			$context
		);

		if ($controller) {
			$controller->connect();
			$controller->run();
		}
	}

	public function __construct(EnvironmentInterface $aEnvironment, $aCode, ContextInterface $aContext, $aOptions = []) {
		parent::__construct($aEnvironment, $aCode, $aContext, $aOptions);

		$this->setDefaultViewType('Html');
	}
}
