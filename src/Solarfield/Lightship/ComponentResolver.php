<?php
namespace Solarfield\Lightship;

use Exception;
use Psr\Log\LogLevel;
use Solarfield\Ok\LoggerInterface;
use Solarfield\Ok\LogUtils;

class ComponentResolver {
	/** @var LoggerInterface */ private $logger;
	private $logLevel;

	public function resolveComponent(ComponentChain $aChain, $aClassNamePart, $aViewTypeCode = null, $aPluginCode = null) {
		// reverse the chain
		/** @var ComponentChainLink[] $chain */ $chain = [];
		foreach ($aChain as $link) {
			$chain[] = $link;
		}
		$chain = array_reverse($chain);
		
		$component = null;

		foreach ($chain as $link) {
			$classNamespace = $link->namespace();
			$className = ($aViewTypeCode ?: '') . $aClassNamePart;
			$classFileName = $className . '.php';
			$includePath = $link->path();
			
			if ($aPluginCode) {
				$pluginNamespace = $aPluginCode;
				$pluginDir = $pluginNamespace;

				$classNamespace .= $link->pluginsNamespace();
				$classNamespace .= '\\' . $pluginNamespace;

				$includePath .= $link->pluginsPath();
				$includePath .= '/' . $pluginDir;
			}
			
			$includePath .= '/' . $classFileName;
			$realIncludePath = realpath($includePath);

			if ($realIncludePath !== false) {
				$component = [
					'className' => $classNamespace . '\\' . $className,
					'includeFilePath' => $realIncludePath,
				];

				break;
			}
		}

		if ($this->logger && LogUtils::includes($this->logLevel, LogLevel::DEBUG)) {
			$this->logger->debug(
				"Resolved component '" . ($component ? $component['className'] : 'NULL') . "'.",
				
				[
					'classNamePart' => $aClassNamePart,
					'viewTypeCode' => $aViewTypeCode,
					'pluginCode' => $aPluginCode,
					'chain' => $chain,
					'component' => $component,
				]
			);
		}

		return $component;
	}
	
	public function __construct(array $aOptions = []) {
		$options = array_replace([
			'logger' => null,
			'logLevel' => null,
		], $aOptions);

		if ($options['logger']) {
			if (!($options['logger'] instanceof LoggerInterface)) throw new Exception(
				"Option 'logger' must be an instance of \Solarfield\Ok\LoggerInterface."
			);
			$this->logger = $options['logger'];
		}

		$this->logLevel = $aOptions['logLevel'] !== null
			? LogUtils::toRfc5424($aOptions['logLevel']) : LogLevel::WARNING;
	}
}
