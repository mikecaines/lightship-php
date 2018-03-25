<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Exception;
use Solarfield\Lightship\Events\CreateDocumentEvent;
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
	
	protected function onCreateDocument(CreateDocumentEvent $aEvt) {
	
	}

	protected function onCreateScriptElements(CreateHtmlEvent $aEvt) {
		$this->resolveScriptIncludes();
		$items = $this->getScriptIncludes()->getResolvedFiles();

		ob_start();

		foreach ($items as $item) {
			if (!$item['ignore']) {
				if ($item['type'] == 'file') {
					$item['attributes']['src'] = $item['resolvedUrl'];
					
					$attrs = [];
					foreach ($item['attributes'] as $k => $v) {
						$attr = $this->enc($k);
						if ($v !== true) $attr .= '="' . $this->enc($v) . '"';
						$attrs[] = $attr;
					}
					$attrs = $attrs ? ' ' . implode(' ', $attrs) : '';
					
					?>
					<script<?php echo($attrs) ?>></script>
					<?php
				}
				
				else if ($item['type'] == 'inline') {
					$attrs = [];
					foreach ($item['attributes'] as $k => $v) {
						$attr = $this->enc($k);
						if ($v !== true) $attr .= '="' . $this->enc($v) . '"';
						$attrs[] = $attr;
					}
					$attrs = $attrs ? ' ' . implode(' ', $attrs) : '';
					
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
		}

		$aEvt->getHtml()->append(ob_get_clean());
	}

	protected function onCreateStyleElements(CreateHtmlEvent $aEvt) {
		$this->resolveStyleIncludes();
		$items = $this->getStyleIncludes()->getResolvedFiles();

		ob_start();

		foreach ($items as $item) {
			if (!$item['ignore']) {
				if ($item['type'] == 'file') {
					if (!array_key_exists('rel', $item['attributes'])) $item['attributes']['rel'] = 'stylesheet';
					
					if (strtolower($item['attributes']['rel']) == 'stylesheet') {
						if (!array_key_exists('type', $item['attributes'])) $item['attributes']['type'] = 'text/css';
						if (!array_key_exists('href', $item['attributes'])) $item['attributes']['href'] = $item['resolvedUrl'];
					}
					
					$attrs = [];
					foreach ($item['attributes'] as $k => $v) {
						$attr = $this->enc($k);
						if ($v !== true) $attr .= '="' . $this->enc($v) . '"';
						$attrs[] = $attr;
					}
					$attrs = $attrs ? ' ' . implode(' ', $attrs) : '';
					
					?>
					<link<?php echo($attrs) ?>/>
					<?php
				}

				else if ($item['type'] == 'inline') {
					$attrs = [];
					foreach ($item['attributes'] as $k => $v) {
						$attr = $this->enc($k);
						if ($v !== true) $attr .= '="' . $this->enc($v) . '"';
						$attrs[] = $attr;
					}
					$attrs = $attrs ? ' ' . implode(' ', $attrs) : '';
					
					?>
					<style<?php echo($attrs) ?>><?php echo($item['content']) ?></style>
					<?php
				}
				
				else {
					throw new Exception(
						"Unknown client side include type '{$item['type']}'."
					);
				}
			}
		}

		$aEvt->getHtml()->append(ob_get_clean());
	}
	
	/**
	 * Creates the top level document HTML, including the DOCTYPE.
	 * Note a CreateDocumentEvent is dispatched, but does not currently allow much customization of behaviour other
	 * than setting attributes on the <html> element.
	 * @return string
	 * @see onCreateDocument()
	 */
	public function createDocument() {
		ob_start();

		//dispatch a CreateDocumentEvent
		//This implements CreateElementContentEvent, and corresponds to the <html> event.
		$event = new CreateDocumentEvent('create-document-event', ['target'=>$this]);
		$this->dispatchEvent($event, [
			'listener' => [$this, 'onCreateDocument'],
		]);
		$this->dispatchEvent($event);
		$htmlAttrs = [];
		foreach ($event->getAttributeNames() as $name) {
			$attr = $this->enc($name);
			if (($value = $event->getAttribute($name)) !== null) {
				$attr .= '="' . $this->enc($value) . '"';
			}
			$htmlAttrs[] = $attr;
		}
		$htmlAttrs = $htmlAttrs ? ' ' . implode(' ', $htmlAttrs) : '';
		
		?><!DOCTYPE html>

		<html<?php echo($htmlAttrs) ?>>
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
		
		echo($this->createScriptElements());
		echo($this->createStyleElements());
		
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
