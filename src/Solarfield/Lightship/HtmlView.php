<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Exception;
use Solarfield\Lightship\Events\CreateHtmlEvent;
use Solarfield\Lightship\Events\ResolveHintsEvent;
use Solarfield\Lightship\Events\ResolveScriptIncludesEvent;
use Solarfield\Lightship\Events\ResolveStyleIncludesEvent;
use Solarfield\Ok\HtmlUtils;

abstract class HtmlView extends View {
	private $styleIncludes;
	private $scriptIncludes;

	protected function resolveStyleIncludes() {
		$event = new ResolveStyleIncludesEvent('resolve-style-includes', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveStyleIncludes'],
		]);

		$this->dispatchEvent($event);
	}

	protected function resolveScriptIncludes() {
		$event = new ResolveScriptIncludesEvent('resolve-script-includes', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveScriptIncludes'],
		]);

		$this->dispatchEvent($event);
	}

	protected function onResolveScriptIncludes(ResolveScriptIncludesEvent $aEvt) {
		
	}

	protected function onResolveStyleIncludes(ResolveStyleIncludesEvent $aEvt) {

	}

	protected function onResolveHints(ResolveHintsEvent $aEvt) {
		parent::onResolveHints($aEvt);
		
	}

	protected function onCreateScriptElements(CreateHtmlEvent $aEvt) {
		$this->resolveScriptIncludes();
		$items = $this->getScriptIncludes()->getResolvedFiles();

		ob_start();

		foreach ($items as $item) {
			$item = array_replace([
				'defer' => false,
				'bootstrap' => false,
			], $item);
			
			$attrs = [];
			if ($item['defer']) $attrs[] = "defer";
			$attrs = $attrs ? ' ' . implode(' ', $attrs) : '';
			
			if ($item['type'] == 'file') {
				if (!$item['bootstrap']) {
					?>
					<script<?php echo($attrs) ?> src="<?php $this->out($item['resolvedUrl']); ?>"></script>
					<?php
				}
			}
			
			else if ($item['type'] == 'inline') {
				?>
				<script<?php echo($attrs) ?>><?php echo(trim($item['content'])); ?></script>
				<?php
			}
			
			else {
				throw new Exception(
					"Unknown client side include type '{$item['type']}'."
				);
			}
		}

		$aEvt->getHtml()->append(ob_get_clean());
	}

	protected function onCreateStyleElements(CreateHtmlEvent $aEvt) {
		$this->resolveStyleIncludes();
		$items = $this->getStyleIncludes()->getResolvedFiles();

		ob_start();

		foreach ($items as $item) {
			if ($item['type'] == 'file') {
				?>
				<link rel="stylesheet" type="text/css" href="<?php $this->out($item['resolvedUrl']); ?>"/>
				<?php
			}
			else {
				?>
				<style type="text/css"><?php echo($item['content']) ?></style>
				<?php
			}
		}

		$aEvt->getHtml()->append(ob_get_clean());
	}

	public function createDocument() {
		ob_start();

		?><!DOCTYPE html>

		<html lang="en">
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
		<title><?php $this->out($this->createTitle()); ?></title>
		<?php

		// always output stylesheets, before script, to avoid flash-of-unstyled-content (FOAC)
		// https://bugzilla.mozilla.org/show_bug.cgi?id=1459305
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

	public function createStyleElements() {
		$event = new CreateHtmlEvent('create-style-elements', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onCreateStyleElements'],
		]);

		$this->dispatchEvent($event);

		return $event->getHtml();
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
		$event = new CreateHtmlEvent('create-script-elements', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onCreateScriptElements'],
		]);

		$this->dispatchEvent($event);

		return $event->getHtml();
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
