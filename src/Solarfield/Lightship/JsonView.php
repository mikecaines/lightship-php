<?php
namespace Solarfield\Lightship;

use Solarfield\Lightship\Events\CreateJsonDataEvent;
use Solarfield\Lightship\Events\ResolveJsonDataRulesEvent;
use Solarfield\Lightship\Flags;

use Solarfield\Ok\JsonUtils;
use Solarfield\Ok\StructUtils;

class JsonView extends View {
	private $rules;

	protected function resolveDataRules() {
		$event = new ResolveJsonDataRulesEvent('resolve-data-rules', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onResolveDataRules'],
		]);

		$this->dispatchEvent($event);
	}

	protected function onResolveDataRules(ResolveJsonDataRulesEvent $aEvt) {
		$rules = $this->getDataRules();
		$rules->set('app.standardOutput');
	}

	protected function onCreateJsonData(CreateJsonDataEvent $aEvt) {
		$model = $this->getModel();
		$rules = $this->getDataRules()->toArray();

		foreach ($rules as $k) {
			$s = StructUtils::scout($model, $k);

			if ($s[0]) {
				$aEvt->getJsonData()->set($k, $s[1]);
			}
		}
	}

	public function getDataRules() {
		if (!$this->rules) {
			$this->rules = new Flags();
		}

		return $this->rules;
	}

	public function createJsonData() {
		$event = new CreateJsonDataEvent('create-json-data', ['target' => $this]);

		$this->dispatchEvent($event, [
			'listener' => [$this, 'onCreateJsonData'],
		]);

		$this->dispatchEvent($event);

		return $event->getJsonData()->getData();
	}

	public function createJson() {
		return JsonUtils::toJson($this->createJsonData());
	}

	public function render() {
		header('Content-Type: application/json');
		echo($this->createJson());
	}

	public function init() {
		parent::init();
		$this->resolveDataRules();
	}
	
	public function __construct(EnvironmentInterface $aEnvironment, string $aCode, $aOptions = []) {
		$this->type = 'Json';
		parent::__construct($aEnvironment, $aCode, $aOptions);
	}
}
