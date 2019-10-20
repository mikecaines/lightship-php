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
 * @method WebSourceContext getContext
 */
abstract class WebController extends Controller {
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

		$this->getHints()->set('app.queuedRedirect', [
			'url' => (string)$aUrl,
			'httpStatusCode' => (int)$options['httpStatusCode'],
		]);
	}

	public function run() : DestinationContextInterface {
		$destinationContext = new WebDestinationContext();

		// if a redirect is queued, exit early, skipping doTask() and render()
		if (($queuedRedirect = $this->getHints()->get('app.queuedRedirect'))) {
			$destinationContext->queueRedirect($queuedRedirect['url'], $queuedRedirect['httpStatusCode']);
			return $destinationContext;
		}

		// connect the view
		$view = $this->createView($this->getRequestedViewType());
		$this->getInput()->mergeReverse($view->getInput());
		$this->getHints()->mergeReverse($view->getHints());

		$destinationContext = $this->doTask($destinationContext);

		// if a redirect is queued, exit early, skipping render()
		if (($queuedRedirect = $this->getHints()->get('app.queuedRedirect'))) {
			$destinationContext->queueRedirect($queuedRedirect['url'], $queuedRedirect['httpStatusCode']);
			return $destinationContext;
		}

		//erase any buffered output.
		//There could be buffered output if we are in a reboot
		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		$destinationContext = $view->render($destinationContext);

		return $destinationContext;
	}

	public function handleException(Throwable $aEx) : DestinationContextInterface {
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
		
		$context = new WebSourceContext([
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
		
		return $this->getEnvironment()->boot($context);
	}

	public function __construct(EnvironmentInterface $aEnvironment, $aCode, ModelInterface $aModel, SourceContextInterface $aContext, $aOptions = []) {
		parent::__construct($aEnvironment, $aCode, $aModel, $aContext, $aOptions);

		$this->setDefaultViewType('Html');
	}
}
