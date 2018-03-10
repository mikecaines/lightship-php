<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Ok\StructUtils;

class ClientSideIncludes {
	private $view;
	private $items = [];

	public function addFile($aUrl, $aOptions = array()) {
		$options = array_replace([
			'group' => 2000000,
			'onlyIfExists' => false,
			'base' => null, //null, 'app', 'module'
			'filePath' => null,
			'bootstrap' => false,
		], $aOptions);

		$groupIndex = str_pad($options['group'], 10, ' ', STR_PAD_LEFT);

		if (!array_key_exists($groupIndex, $this->items)) {
			$this->items[$groupIndex] = [];
		}

		$item = $options;
		$item['url'] = $aUrl;
		$item['content'] = null;
		$item['type'] = 'file';

		$this->items[$groupIndex][$item['base'] . '+' . $item['url']] = $item;
	}
	
	public function addInline($aContent, $aOptions = []) {
		$options = array_replace([
			'group' => 2000000,
		], $aOptions);
		
		$groupIndex = str_pad($options['group'], 10, ' ', STR_PAD_LEFT);
		
		if (!array_key_exists($groupIndex, $this->items)) {
			$this->items[$groupIndex] = [];
		}
		
		$item = $options;
		$item['content'] = $aContent;
		$item['url'] = null;
		$item['type'] = 'inline';
		
		$this->items[$groupIndex][] = $item;
	}

	public function getResolvedFiles() {
		$resolvedItems = [];

		ksort($this->items);

		foreach ($this->items as $groupIndex => $group) {
			$groupItemCounter = 0;

			foreach ($group as $item) {
				if ($item['type'] == 'file') {
					$resolvedUrl = null;
					$resolvedFileFilePath = null;
	
					//if the item is relative to the app or module
					if ($item['base'] == 'app' || $item['base'] == 'module') {
						$moduleCode = $this->view->getCode();
						$chain = $this->view->getController()->getChain($moduleCode);
	
						if ($item['base'] == 'app') {
							$link = StructUtils::find($chain, 'id', 'app');
						}
						else {
							$link = StructUtils::find($chain, 'id', 'module');
						}
	
						if ($link) {
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
	
					//else the item is not relative to the app or module
					else {
						//if we should check that the file exists in the filesystem
						if ($item['onlyIfExists']) {
							if ($item['filePath']) {
								if (($realPath = realpath($item['filePath']))) {
									$resolvedFileFilePath = $realPath;
									$resolvedUrl = $item['url'];
								}
							}
	
							else {
								if (($realPath = realpath($item['url']))) {
									$resolvedFileFilePath = $realPath;
									$resolvedUrl = $item['url'];
								}
							}
						}
	
						//else
						else {
							$resolvedUrl = $item['url'];
						}
					}
	
	
					if ($resolvedUrl) {
						$resolvedItem = $item;
						$resolvedItem['resolvedUrl'] = $resolvedUrl;
						$resolvedItem['resolvedFilePath'] = $resolvedFileFilePath;
	
						$resolvedItems[] = $resolvedItem;
						$groupItemCounter++;
					}
				}
				
				else if ($item['type'] == 'inline') {
					$resolvedItem = $item;
					$resolvedItem['resolvedUrl'] = null;
					$resolvedItem['resolvedFilePath'] = null;
					
					$resolvedItems[] = $resolvedItem;
					$groupItemCounter++;
				}
				
				else {
					throw new \Exception(
						"Unknown client side include type '{$item['type']}'."
					);
				}
			}
		}

		return $resolvedItems;
	}

	public function __construct(View $aView) {
		$this->view = $aView;
	}

	public function __destruct() {
		$this->view = null;
	}
}

