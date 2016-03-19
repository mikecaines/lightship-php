<?php
namespace Solarfield\Lightship;

use Solarfield\Batten\Event;
use Solarfield\Batten\Flags;
use Solarfield\Ok\JsonUtils;
use Solarfield\Ok\StructUtils;

class JsonView extends View {
	private $rules;

	protected function resolveDataRules() {
		$rules = $this->getDataRules();
		$rules->set('app.standardOutput');

		$this->dispatchEvent(
			new Event('app-resolve-data-rules', ['target' => $this])
		);
	}

	public function getDataRules() {
		if (!$this->rules) {
			$this->rules = new Flags();
		}

		return $this->rules;
	}

	public function createJsonData() {
		$jsonData = [];
		$model = $this->getModel();
		$rules = $this->getDataRules()->toArray();

		foreach ($rules as $k) {
			$s = StructUtils::scout($model, $k);

			if ($s[0]) {
				StructUtils::set($jsonData, $k, $s[1]);
			}
		}

		$buffer = [];

		$this->dispatchEvent(
			new ArrayBufferEvent('app-create-json-data', ['target' => $this], $buffer)
		);

		if (count($buffer) > 0) {
			$jsonData = StructUtils::merge($jsonData, $buffer);
		}

		return $jsonData;
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

	public function __construct($aCode) {
		$this->type = 'Json';
		parent::__construct($aCode);
	}
}
