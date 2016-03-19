<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Batten\Event;
use Solarfield\Ok\HtmlUtils;
use Solarfield\Ok\JsonUtils;

abstract class HtmlView extends View {
	private $styleIncludes;
	private $scriptIncludes;
	private $jsEnvironment;

	protected function resolveHints() {
		$hints = $this->getHints();
		$hints->set('doLoadServerData', true);

		$this->dispatchEvent(
			new Event('resolve-hints', ['target' => $this])
		);
	}

	protected function resolveStyleIncludes() {
		$this->dispatchEvent(
			new Event('resolve-style-includes', ['target' => $this])
		);
	}

	protected function resolveScriptIncludes() {
		$includes = $this->getScriptIncludes();
		$appWebPath = Env::getVars()->get('appPackageWebPath');

		$moduleCode = $this->getCode();
		$chain = $this->getController()->getChain($moduleCode);

		$includes->addFile($appWebPath . '/App/Environment.js', [
			'loadMethod' => 'dynamic',
			'group' => 1000000,
			'bundleKey' => 'app',
		]);

		$includes->addFile($appWebPath . '/App/Controller.js', [
			'loadMethod' => 'dynamic',
			'group' => 1000000,
			'bundleKey' => 'app',
		]);

		$moduleLink = array_key_exists('module', $chain) ? $chain['module'] : null;
		if ($moduleLink) {
			$includes->addFile('/Controller.js', [
				'loadMethod' => 'dynamic',
				'base' => 'module',
				'onlyIfExists' => true,
				'group' => 1250000,
				'bundleKey' => 'module',
			]);
		}

		$this->dispatchEvent(
			new Event('resolve-script-includes', ['target' => $this])
		);
	}

	protected function resolveJsEnvironment() {
		$appPackageWebPath = Env::getVars()->get('appPackageWebPath');
		$depsPath = Env::getVars()->get('appDependenciesWebPath');

		$this->getJsEnvironment()->merge([
			'systemConfig' => [
				'paths' => [
					'solarfield/batten-js/*' => "$depsPath/solarfield/batten-js/*.js",
					'solarfield/lightship-js/*' => "$depsPath/solarfield/lightship-js/*.js",
					'solarfield/ok-kit-js/*' => "$depsPath/solarfield/ok-kit-js/*.js",
					'app/App/*' => "$appPackageWebPath/App/*.js",
				],
			],
		]);
	}

	public function createDocument() {
		ob_start();

		?><!DOCTYPE html>

		<html>
			<head><?php echo($this->createHeadContent()); ?></head>

			<body>
				<?php
				echo($this->createBodyContent());
				?>
			</body>
		</html>
		<?php

		return ob_get_clean();
	}

	public function createHeadContent() {
		ob_start();

		?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<meta name="viewport" content="width=device-width, user-scalable=no"/>

		<title><?php $this->out($this->createTitle()); ?></title>
		<?php

		echo($this->createStyleElements());

		echo($this->createInitScriptElements());
		echo($this->createScriptElements());
		echo($this->createBootstrapScriptElements());

		return ob_get_clean();
	}

	public function createTitle() {
		return Env::getVars()->get('requestId');
	}

	public function getStyleIncludes() {
		if (!$this->styleIncludes) {
			$this->styleIncludes = new ClientSideIncludes($this);
		}

		return $this->styleIncludes;
	}

	public function createStyleElements() {
		$this->resolveStyleIncludes();
		$items = $this->getStyleIncludes()->getResolvedFiles();

		ob_start();

		foreach ($items as $item) {
			?>
			<link rel="stylesheet" type="text/css" href="<?php $this->out($item['resolvedUrl']); ?>"/>
			<?php
		}

		$buffer = '';

		$this->dispatchEvent(
			new ArrayBufferEvent('create-style-elements', ['target' => $this], $buffer)
		);

		echo($buffer);

		return ob_get_clean();
	}

	public function getScriptIncludes() {
		if (!$this->scriptIncludes) {
			$this->scriptIncludes = new ClientSideIncludes($this);
		}

		return $this->scriptIncludes;
	}

	/**
	 * Creates <script> elements from the resolved script includes.
	 * The elements will be output between init/early and bootstrap/late.
	 * If a client side include specifies loadMethod=dynamic, a data-src attribute will be used,
	 * and the script will be handled by System.import later.
	 * @return string
	 */
	public function createScriptElements() {
		$this->resolveScriptIncludes();
		$items = $this->getScriptIncludes()->getResolvedFiles();

		ob_start();

		foreach ($items as $item) {
			if ($item['loadMethod'] == 'static') {
				?>
				<script type="text/javascript" src="<?php $this->out($item['resolvedUrl']); ?>"></script>
				<?php
			}

			else if ($item['loadMethod'] == 'dynamic') {
				?>
				<script type="text/javascript" data-src="<?php $this->out($item['resolvedUrl']); ?>"></script>
				<?php
			}
		}

		$buffer = '';

		$this->dispatchEvent(
			new ArrayBufferEvent('create-script-elements', ['target' => $this], $buffer)
		);

		echo($buffer);

		return ob_get_clean();
	}

	public function getJsEnvironment() {
		if (!$this->jsEnvironment) {
			$this->jsEnvironment = new JsEnvironment();
		}

		return $this->jsEnvironment;
	}

	/**
	 * Creates early-output <script> elements used to set up the script environment.
	 * @return mixed|string
	 * @throws \Exception
	 */
	public function createInitScriptElements() {
		$this->resolveJsEnvironment();

		$depsPath = Env::getVars()->get('appDependenciesWebPath');
		$jsSystemConfig = $this->getJsEnvironment()->get('systemConfig');

		ob_start();

		?>
		<script type="text/javascript" src="<?php $this->out($depsPath . '/systemjs/systemjs/dist/system-csp-production.js'); ?>" class="appBootstrapScript"></script>

		<script type="text/javascript" class="appBootstrapScript">
			<?php
			if ($jsSystemConfig) {
				?>
				System.config(<?php echo(JsonUtils::toJson($jsSystemConfig)) ?>);
				<?php
			}
			?>

			window.define = System.amdDefine;
			window.require = System.amdRequire;
		</script>
		<?php

		$html = ob_get_clean();
		$html = str_replace("\n", '', $html);
		$html = preg_replace('/\s{2,}/', '', $html);

		return $html;
	}

	/**
	 * Creates late-output <script> elements used to bootstrap the script environment.
	 * @return mixed|string
	 */
	public function createBootstrapScriptElements() {
		$model = $this->getModel();
		$serverData = $model->get('app.serverData');

		$envInitData = [
			'baseChain' => $serverData['baseChain'],
		];

		$vars = [];
		foreach (($this->getJsEnvironment()->get('forwardedVars')?:[]) as $k) $vars[$k] = Environment::getVars()->get($k);
		if ($vars) $envInitData['vars'] = $vars;

		$bootInfo = [
			'moduleCode' => $this->getCode(),
			'controllerOptions' => [
				'pluginRegistrations' => $serverData['pluginRegistrations'],
			],
		];

		/** @var JsonView $jsonView */
		$jsonView = $this->getController()->createView('Json');
		$jsonView->setController($this->getController());
		$jsonView->init();
		$jsonView->setModel($this->getModel());
		$pendingData = $jsonView->createJsonData();
		if ($pendingData) $bootInfo['controllerOptions']['pendingData'] = $pendingData;
		unset($jsonView, $pendingData);

		ob_start();

		?>
		<script type="text/javascript" class="appBootstrapScript">
			(function () {
				Promise.all(
					Array.from(document.querySelectorAll('script[data-src]'), function (el) {
						el.parentNode.removeChild(el);
						return System.import(el.getAttribute('data-src'));
					})
				)
				.then(function () {
					Array.from(document.querySelectorAll('.appBootstrapScript'), function (el) {
						el.parentNode.removeChild(el);
					});

					App.DEBUG = <?php echo(JsonUtils::toJson(\App\DEBUG)) ?>;
					App.Environment.init(<?php echo(JsonUtils::toJson($envInitData)) ?>);

					return App.Controller.boot(<?php echo(JsonUtils::toJson($bootInfo)); ?>);
				})
				.then(function (controller) {
					controller.connect()
					.then(function () {
						App.controller = controller;
						controller.run();
					})
					.catch(function (ex) {controller.handleException(ex)});
				})
				.catch(function (ex) {App.Controller.bail(ex)});
			})();
		</script>
		<?php

		$html = ob_get_clean();
		$html = str_replace("\n", '', $html);
		$html = preg_replace('/\s{2,}/', '', $html);

		return $html;
	}

	public function createBodyContent() {
		return null;
	}

	public function enc($aValue) {
		return htmlspecialchars($aValue, ENT_COMPAT|ENT_XML1|ENT_SUBSTITUTE, 'UTF-8');
	}

	public function out($aValue) {
		echo($this->enc($aValue));
	}

	public function render() {
		header('Content-Type: text/html; charset=UTF-8');

		$markup = $this->createDocument();
		$markup = HtmlUtils::squishHtml($markup);

		echo($markup);
	}

	public function __construct($aCode) {
		$this->type = 'Html';
		parent::__construct($aCode);
	}
}
