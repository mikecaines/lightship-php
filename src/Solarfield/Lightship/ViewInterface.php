<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\EventTargetInterface;

interface ViewInterface extends EventTargetInterface {
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
	 * @return Hints
	 */
	public function getHints();

	public function setController(ControllerProxyInterface $aController);

	/**
	 * @return ControllerProxyInterface|null
	 */
	public function getController();
	
	public function getEnvironment(): EnvironmentInterface;

	public function render(DestinationContextInterface $aDestinationContext) : DestinationContextInterface;

	public function init();
}
