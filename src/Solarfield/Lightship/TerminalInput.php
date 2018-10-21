<?php
namespace Solarfield\Lightship;

use Solarfield\Ok\StructUtils;

class TerminalInput implements InputInterface {
	private $data = [];

	static public function fromGlobals(): InputInterface {
		$items = [];

		// remove the first arg, which is the script-name
		$args = $_SERVER['argv'];
		array_shift($args);

		if (count($args) > 0) {
			// if the first arg does not have leading hyphens, consider it an alias for --module
			if (preg_match('/^[[:alnum:]]+$/i', $args[0])) {
				$items[] = ['--module', $args[0]];
				array_shift($args);
			}

			$index = 0;
			foreach ($args as $arg) {
				// if the argument has hyphen prefixes
				if (preg_match('/^(-{1,2})([[:alnum:]\-]+)(?:\=(.+))?$/', $arg, $matches) == 1) {
					$hyphens = $matches[1];
					$name = $matches[2];

					// the optional value, e.g. the 'bar' in --foo=bar
					$value = count($matches) == 4 ? (string)$matches[3] : null;

					// if the arg is in long form, e.g. --long-arg
					if ($hyphens == '--') {
						$childNames = [$name];
					}

					// else the arg is in short form, e.g. -s, or -lah
					else {
						// split the name into each character, treating each as an arg name
						$childNames = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
					}

					foreach ($childNames as $childName) {
						$items[] = [$hyphens . $childName, $value];
					}

					$index++;
				}

				// else the arg does not have a hyphen prefix
				else {
					// if the previous arg already specified a value, consider input malformed
					if ($index == 0 || $items[$index][1] !== null) {
						throw new \Exception(
							"Malformed argument: '" . $arg . "'."
						);
					}

					// else consider the argument the value of the previous argument
					// e.g. the 'foo' in 'command -f foo'
					$items[$index][1] = (string)$arg;
				}
			}
		}

		// restructure the items into key-value pairs,
		// defaulting any args without a value, to '1'.
		$data = [];
		foreach ($items as $item) {
			$data[$item[0]] = $item[1] === null ? '1' : $item[1];
		}

		return new static($data);
	}
	
	public function getAsString($aPath) {
		$value = StructUtils::get($this->data, $aPath);
		return is_array($value) ? '' : (string)$value;
	}

	public function getAsArray($aPath) {
		$value = StructUtils::get($this->data, $aPath);
		return is_array($value) ? $value : [];
	}
	
	public function has($aPath) {
		return StructUtils::scout($this->data, $aPath)[0];
	}
	
	public function toArray() {
		return $this->data;
	}

	public function set($aPath, $aValue) {
		StructUtils::set($this->data, $aPath, $aValue);
	}

	public function merge($aData) {
		$incomingData = StructUtils::toArray($aData, true);
		$this->data = StructUtils::merge($this->data, $incomingData);
	}

	public function mergeReverse($aData) {
		$incomingData = StructUtils::toArray($aData, true);
		$this->data = StructUtils::merge($incomingData, $this->data);
	}
	
	public function __construct($aData = null) {
		$this->merge($aData?:[]);
	}
}
