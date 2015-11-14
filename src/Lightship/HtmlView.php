<?php
namespace Lightship;

use App\Environment as Env;
use Batten\Event;
use Batten\Reflector;
use Ok\HtmlUtils;
use Ok\JsonUtils;

abstract class HtmlView extends View {
	private $styleIncludes;
	private $scriptIncludes;

	protected function resolveHints() {
		$hints = $this->getHints();
		$hints->set('doLoadServerData', true);

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-resolve-hints', ['target' => $this])
			);
		}
	}

	protected function resolveStyleIncludes() {
		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-resolve-style-includes', ['target' => $this])
			);
		}
	}

	protected function resolveScriptIncludes() {
		$includes = $this->getScriptIncludes();
		$appWebPath = Env::getVars()->get('appPackageWebPath');

		$includes->addFile($appWebPath . '/deps/modernizr/modernizr/modernizr.custom.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/ok-kit-js/src/Ok/ok.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/Environment.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/Controller.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/ControllerPlugin.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/ControllerPlugins.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/ComponentResolver.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/EventTarget.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/mikecaines/batten-js/src/Batten/Model.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/solarfield/lightship-js/src/Lightship/Environment.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/solarfield/lightship-js/src/Lightship/Controller.js', ['bundleKey' => 'app']);
		$includes->addFile($appWebPath . '/deps/solarfield/lightship-js/src/Lightship/HttpMux.js', ['bundleKey' => 'app']);

		$moduleCode = $this->getCode();
		$chain = $this->getController()->getChain($moduleCode);

		$includes->addFile($appWebPath . '/App/Environment.js', [
			'group' => 1500,
			'bundleKey' => 'app',
		]);

		$includes->addFile($appWebPath . '/App/Controller.js', [
			'group' => 1500,
			'bundleKey' => 'app',
		]);

		$moduleLink = array_key_exists('module', $chain) ? $chain['module'] : null;
		if ($moduleLink) {
			$includes->addFile('/Controller.js', [
				'base' => 'module',
				'onlyIfExists' => true,
				'group' => 2500,
				'bundleKey' => 'module',
			]);
		}

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$this->dispatchEvent(
				new Event('app-resolve-script-includes', ['target' => $this])
			);
		}
	}

	public function createDocument() {
		ob_start();

		?><!DOCTYPE html>

		<html>
			<head><?php echo($this->createHeadContent()); ?></head>

			<body>
				<?php
				echo($this->createBootstrapScript());
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
		echo($this->createScriptElements());

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

	public function getFinalStyleIncludes() {
		$this->resolveStyleIncludes();
		$includes = $this->getStyleIncludes();

		$items = $includes->getResolvedFiles();

		/** @var \Lightship\CsiProcessor\Plugins\StyleIncludeProcessor\HtmlViewPlugin $styleIncludeProxyView */
		$styleIncludeProxyView = $this->getPlugins()->get('app-style-include-processor');

		if ($styleIncludeProxyView) {
			$items = $styleIncludeProxyView->processItems($items);
		}

		return $items;
	}

	public function createStyleElements() {
		$items = $this->getFinalStyleIncludes();

		ob_start();

		foreach ($items as $item) {
			?>
			<link rel="stylesheet" type="text/css" href="<?php $this->out($item['resolvedUrl']); ?>"/>
			<?php
		}

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$buffer = '';

			$this->dispatchEvent(
				new ArrayBufferEvent('app-create-style-elements', ['target' => $this], $buffer)
			);

			echo($buffer);
		}

		return ob_get_clean();
	}

	public function getScriptIncludes() {
		if (!$this->scriptIncludes) {
			$this->scriptIncludes = new ClientSideIncludes($this);
		}

		return $this->scriptIncludes;
	}

	public function getFinalScriptIncludes() {
		$this->resolveScriptIncludes();
		$includes = $this->getScriptIncludes();

		$items = $includes->getResolvedFiles();

		/** @var \Lightship\CsiProcessor\Plugins\ScriptIncludeProcessor\HtmlViewPlugin $scriptIncludeProxyView */
		$scriptIncludeProxyView = $this->getPlugins()->get('app-script-include-processor');

		if ($scriptIncludeProxyView) {
			$items = $scriptIncludeProxyView->processItems($items);
		}

		return $items;
	}

	public function createScriptElements() {
		$items = $this->getFinalScriptIncludes();

		ob_start();

		foreach ($items as $item) {
			?>
			<script type="text/javascript" src="<?php $this->out($item['resolvedUrl']); ?>"></script>
			<?php
		}

		if (Reflector::inSurfaceOrModuleMethodCall()) {
			$buffer = '';

			$this->dispatchEvent(
				new ArrayBufferEvent('app-create-script-elements', ['target' => $this], $buffer)
			);

			echo($buffer);
		}

		return ob_get_clean();
	}

	public function createBootstrapScript() {
		$model = $this->getModel();
		$serverData = $model->get('app.serverData');

		$envInitData = [
			'baseChain' => $serverData['baseChain'],
		];

		$fromCodeData = [
			'pluginRegistrations' => $serverData['pluginRegistrations'],
		];

		/** @var JsonView $jsonView */
		$jsonView = $this->getController()->createView('Json');
		$jsonView->setController($this->getController());
		$jsonView->init();
		$jsonView->setModel($this->getModel());
		$pendingData = $jsonView->createJsonData();
		unset($jsonView);

		ob_start();

		?>
		<script id="appBootstrapScript" type="text/javascript">
			App.DEBUG = <?php echo(\App\DEBUG ? 'true' : 'false') ?>;
			App.Environment.init(<?php echo(JsonUtils::toJson($envInitData)) ?>);

			(function () {
				var controller = App.Controller.fromCode(<?php echo(JsonUtils::toJson($this->getCode())); ?>, <?php echo(JsonUtils::toJson($fromCodeData)); ?>);

				if (controller) {
					controller.init();

					<?php
					if (count($pendingData) > 0) {
						?>
						controller.getModel().set('app.pendingData', <?php echo(JsonUtils::toJson($pendingData)); ?>);
						<?php
					}
					?>

					function handleDomReady() {
						controller.hookup();
						controller.go();
						document.removeEventListener('DOMContentLoaded', handleDomReady);
					}

					document.addEventListener('DOMContentLoaded', handleDomReady);
				}

				<?php
				if (\App\DEBUG) {
					?>
					App.controller = controller;
					<?php
				}
				?>
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
