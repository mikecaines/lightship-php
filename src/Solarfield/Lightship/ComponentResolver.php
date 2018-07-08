<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Ok\LoggerInterface;

class ComponentResolver {
	private $logger;
	
	public function resolveComponent($aChain, $aClassNamePart, $aViewTypeCode = null, $aPluginCode = null) {
		$chain = array_reverse($aChain);
		$component = null;

		foreach ($chain as $link) {
			$link = array_replace([
				'id' => null,
				'namespace' => null,
				'path' => null,
				'pluginsSubNamespace' => '\\Plugins',
				'pluginsSubPath' => '/Plugins',
			], $link);

			$classNamespace = $link['namespace'];
			$className = ($aViewTypeCode ?: '') . $aClassNamePart;
			$classFileName = $className . '.php';
			$includePath = $link['path'];
			
			if ($aPluginCode) {
				$pluginNamespace = $aPluginCode;
				$pluginDir = $pluginNamespace;

				$classNamespace .= $link['pluginsSubNamespace'];
				$classNamespace .= '\\' . $pluginNamespace;

				$includePath .= $link['pluginsSubPath'];
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

		if (\App\DEBUG && Env::getVars()->get('debugComponentResolution')) {
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
	
	public function __construct(array $aOptions = null) {
		$options = array_replace([
			'logger' => null,
		], $aOptions ?: []);
		
		if ($options['logger']) {
			if (!$options['logger'] instanceof LoggerInterface) throw new \Exception(
				"Option 'logger' must be an instance of \Solarfield\Ok\LoggerInterface."
			);
			
			$this->logger = $options['logger'];
		}
		else {
			$this->logger = Env::getLogger();
		}
	}
}
