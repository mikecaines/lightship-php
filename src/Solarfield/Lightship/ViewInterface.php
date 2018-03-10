<?php
namespace Solarfield\Lightship;

interface ViewInterface {
	public function getCode();

	public function setModel(ModelInterface $aModel);

	/**
	 * @return ModelInterface|null
	 */
	public function getModel();

	/**
	 * @return InputInterface
	 */
	public function getInput();

	/**
	 * @return HintsInterface
	 */
	public function getHints();

	public function setController(ControllerProxyInterface $aController);

	/**
	 * @return ControllerProxyInterface|null
	 */
	public function getController();

	public function render();

	public function addEventListener($aEventType, $aListener);

	public function init();
}
