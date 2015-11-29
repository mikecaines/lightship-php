<?php
namespace Solarfield\Lightship;

/**
 * @method HtmlView getView()
 */
class HtmlViewPlugin extends \Solarfield\Batten\ViewPlugin {
	public function __construct(HtmlView $aView, $aComponentCode, $aInstallationCode) {
		parent::__construct($aView, $aComponentCode, $aInstallationCode);
	}
}
