<?php
namespace Solarfield\Lightship;

use App\Environment as Env;

class ClientSideIncludes {
	private $view;
	private $items = [];

	public function addFile($aUrl, $aOptions = array()) {
		$options = array_replace([
			'loadMethod' => 'static', //'static' (i.e. <script>, <link>), 'dynamic' (System.import)
			'group' => 2000000,
			'onlyIfExists' => false,
			'base' => null, //null, 'app', 'module'
			'filePath' => null,
		], $aOptions);

		$groupIndex = str_pad($options['group'], 10, ' ', STR_PAD_LEFT);

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
				$resolvedFileFilePath = null;

				if ($item['base'] == 'app' || $item['base'] == 'module') {
					if (($link = $item['base'] == 'app' ? $appLink : $moduleLink)) {
						$sourceDirUrl = Env::getVars()->get('appSourceWebPath') . '/' . str_replace('\\', '/', $link['namespace']);

						//if item specifies an explicit file path (relative to link)
						if ($item['filePath']) {
							//if file exists on disk
							if (($path = realpath($link['path'] . $item['filePath']))) {
								//if item's url is a url path
								if (mb_substr($item['url'], 0, 1) == '/') {
									$resolvedUrl = $sourceDirUrl . $item['url'];
									$resolvedFileFilePath = $path;
								}

								//else item's url is a module id
								else {
									$resolvedUrl = $item['url'];
								}
							}
						}

						//else item only specifies a url (web path)
						else {
							//if file exists on disk
							if (($path = realpath($link['path'] . $item['url']))) {
								$resolvedFileFilePath = $path;
								$resolvedUrl = $sourceDirUrl . $item['url'];
							}
						}
					}
				}

				else {
					if ($item['filePath']) {
						if (($realPath = realpath($item['filePath']))) {
							$resolvedFileFilePath = $realPath;
						}
					}

					else {
						//if url starts with a single forward slash, consider it relative to document root
						if (preg_match('/^\/[^\/]/', $resolvedUrl) == 1) {
							if (($realPath = realpath($docRoot . $resolvedUrl))) {
								$resolvedFileFilePath = $realPath;
							}
						}
					}

					if (!$item['onlyIfExists'] || ($item['onlyIfExists'] && $resolvedFileFilePath)) {
						$resolvedUrl = $item['url'];
					}
				}


				if ($resolvedUrl) {
					$resolvedItem = $item;
					$resolvedItem['resolvedUrl'] = $resolvedUrl;
					$resolvedItem['fileFilePath'] = $resolvedFileFilePath;

					$resolvedItems[] = $resolvedItem;
					$groupItemCounter++;
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

