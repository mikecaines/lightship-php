<?php
namespace Lightship;

/**
 * @method HtmlView getView()
 */
class HtmlViewPlugin extends \Batten\ViewPlugin {
	public function __construct(HtmlView $aView, $aCode) {
		parent::__construct($aView, $aCode);
	}
}
