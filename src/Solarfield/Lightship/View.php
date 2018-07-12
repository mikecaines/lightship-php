<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Lightship\Events\ResolveHintsEvent;
use Solarfield\Lightship\Events\ResolveInputEvent;
use Solarfield\Lightship\Events\ResolveOptionsEvent;
use Solarfield\Ok\EventTargetTrait;

abstract class View implements ViewInterface {
	use EventTargetTrait;
	
	private $code;
	private $model;
	private $input;
	private $hints;
	private $controller;
	private $plugins;
	private $options;
	
	protected $type;
	
	protected function resolvePlugins() {
		foreach ($this->getController()->getPlugins()->getRegistrations() as $registration) {
			$this->getPlugins()->register($registration['componentCode']);
		}
	}
	
	protected function resolveOptions() {
		$event = new ResolveOptionsEvent('resolve-options', ['target' => $this]);
		
		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveOptions'],
		]);
		
		$this->dispatchEvent($event);
	}
	
	protected function onResolveOptions(ResolveOptionsEvent $aEvt) {
	
	}
	
	protected function resolveInput() {
		$event = new ResolveInputEvent('resolve-input', ['target' => $this]);
		
		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveInput'],
		]);
		
		$this->dispatchEvent($event);
	}
	
	protected function onResolveInput(ResolveInputEvent $aEvt) {
	
	}
	
	protected function resolveHints() {
		$event = new ResolveHintsEvent('resolve-hints', ['target' => $this]);
		
		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveHints'],
		]);
		
		$this->dispatchEvent($event);
	}
	
	protected function onResolveHints(ResolveHintsEvent $aEvt) {
	
	}
	
	public function getCode() {
		return $this->code;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getOptions() {
		if (!$this->options) {
			$this->options = new Options();
		}
		
		return $this->options;
	}
	
	public function getPlugins() {
		if (!$this->plugins) {
			$this->plugins = new ViewPlugins($this);
		}
		
		return $this->plugins;
	}
	
	public function setModel(ModelInterface $aModel) {
		$this->model = $aModel;
	}
	
	public function getModel() {
		return $this->model;
	}
	
	public function getInput() {
		if (!$this->input) {
			if ($this->getController()) {
				$this->input = $this->getController()->createInput();
			}
		}
		
		return $this->input;
	}
	
	public function getHints() {
		if (!$this->hints) {
			if ($this->getController()) {
				$this->hints = $this->getController()->createHints();
			}
		}
		
		return $this->hints;
	}
	
	public function setController(ControllerProxyInterface $aController) {
		$this->controller = $aController;
	}
	
	public function getController() {
		return $this->controller;
	}
	
	public function render() {
	
	}
	
	public function init() {
		//this method provides a hook to resolve plugins, options, etc.
		
		$this->resolvePlugins();
		$this->resolveOptions();
		$this->resolveInput();
		$this->resolveHints();
	}
	
	public function __construct($aCode) {
		if (Env::getVars()->get('logComponentLifetimes')) {
			Env::getLogger()->debug(get_class($this) . "[code=" . $aCode . "] was constructed");
		}
		
		$this->code = (string)$aCode;
		
		if ((string)$this->type == '') {
			throw new \Exception(
				"Subclasses of " . __CLASS__ . " must set protected member \$type before calling " . __METHOD__ . "()."
			);
		}
	}
	
	public function __destruct() {
		if (Env::getVars()->get('logComponentLifetimes')) {
			Env::getLogger()->debug(get_class($this) . "[code=" . $this->getCode() . "] was destructed");
		}
	}
}
