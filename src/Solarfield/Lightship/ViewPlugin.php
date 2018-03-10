<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\EventTargetTrait;

abstract class ViewPlugin {
	use EventTargetTrait;
	
	private $view;
	private $componentCode;

	/**
	 * @return ViewInterface
	 */
	public function getView() {
		return $this->view;
	}

	public function getCode() {
		return $this->componentCode;
	}

	public function __construct(ViewInterface $aView, $aComponentCode) {
		$this->view = $aView;
		$this->componentCode = (string)$aComponentCode;
	}
}
