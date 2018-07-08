<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Solarfield\Ok\MiscUtils;

class ComponentResolver {
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
			Env::getLogger()->debug(
				get_called_class() . "::" . __FUNCTION__ . "() resolved '"
				. $aPluginCode . $aViewTypeCode . $aClassNamePart
				. "' component " . MiscUtils::varInfo($component)
				. " from chain " . MiscUtils::varInfo($chain)
			);
		}

		return $component;
	}
}
