<?php
namespace Solarfield\Lightship;

class ClientSideIncludes {
	private $view;
	private $items = [];

	public function addFile($aUrl, $aOptions = array()) {
		$options = array_replace([
			'loadMethod' => 'static', //'static' (i.e. <script>, <link>), 'dynamic' (System.import)
			'group' => 2000000,
			'bundleKey' => null,
			'onlyIfExists' => false,
			'base' => null, //null, 'app', 'module'
		], $aOptions);

		$groupIndex = $options['loadMethod'] . '+' . str_pad($options['group'], 10, ' ', STR_PAD_LEFT);

		if (!array_key_exists($groupIndex, $this->items)) {
			$this->items[$groupIndex] = [];
		}

		$item = $options;
		$item['url'] = $aUrl;

		$this->items[$groupIndex][$item['base'] . '+' . $item['url']] = $item;
	}

	public function getResolvedFiles() {
		$resolvedItems = [];

		$moduleCode = $this->view->getCode();
		$chain = $this->view->getController()->getChain($moduleCode);

		$moduleLink = array_key_exists('module', $chain) ? $chain['module'] : null;
		$appLink = array_key_exists('app', $chain) ? $chain['app'] : null;

		$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);

		ksort($this->items);

		foreach ($this->items as $groupIndex => $group) {
			$groupItemCounter = 0;

			foreach ($group as $item) {
				$resolvedUrl = null;

				if ($item['base'] == 'app') {
					if ($appLink) {
						$path = realpath($appLink['path'] . $item['url']);

						if ($path) {
							$resolvedUrl = preg_replace('/^' . preg_quote($docRoot, '/') . '/', '', $path);
						}
					}
				}

				else if ($item['base'] == 'module') {
					if ($moduleLink) {
						$path = realpath($moduleLink['path'] . $item['url']);

						if ($path) {
							$resolvedUrl = preg_replace('/^' . preg_quote($docRoot, '/') . '/', '', $path);
						}
					}
				}

				else {
					$resolvedUrl = $item['url'];
				}


				if ($resolvedUrl) {
					$resolvedItem = [
						'loadMethod' => $item['loadMethod'],
						'base' => $item['base'],
						'url' => $item['url'],
						'resolvedUrl' => $resolvedUrl,
						'group' => $item['group'],
						'bundleKey' => $item['bundleKey'],
						'onlyIfExists' => $item['onlyIfExists'],
						'fileFilePath' => null,
						'fileMtime' => null,
					];

					if (preg_match('/^\/[^\/]/', $resolvedItem['resolvedUrl']) == 1) {
						$realPath = realpath($docRoot . $resolvedItem['resolvedUrl']);

						if ($realPath) {
							$resolvedItem['fileFilePath'] = $realPath;
							$resolvedItem['fileMtime'] = filemtime($realPath);
						}
					}

					if (!$resolvedItem['onlyIfExists'] || $resolvedItem['fileFilePath']) {
						$resolvedItems[] = $resolvedItem;
						$groupItemCounter++;
					}
				}
			}
		}

		return $resolvedItems;
	}

	public function __construct(\Solarfield\Batten\View $aView) {
		$this->view = $aView;
	}

	public function __destruct() {
		$this->view = null;
	}
}

