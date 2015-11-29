<?php
namespace Solarfield\Lightship;

use App\Environment as Env;
use Exception;
use Solarfield\Ok\MiscUtils;

require_once \App\DEPENDENCIES_FILE_PATH . '/solarfield/ok-kit-php/src/Solarfield/Ok/MiscUtils.php';

class ClassLoader {
	/**
	 * Imports any Composer PSR4 rules and adds a rule for the App namespace.
	 * References to Composer's vendor directory, are replaced with the App's dependencies directory.
	 * All slashes in paths are normalized to forward-slashes.
	 * @return array
	 * @throws Exception
	 */
	protected function getPsr4Rules() {
		static $rules;

		if ($rules === null) {
			$rules = [];
			$composerDirPath = str_replace('\\', '/', Env::getVars()->get('composerVendorFilePath'));
			$composerFilePath = $composerDirPath . '/composer/autoload_psr4.php';

			if (file_exists($composerFilePath)) {
				$composerRules = MiscUtils::extractInclude($composerFilePath);

				if (!is_array($composerRules)) {
					throw new Exception(
						"Composer file 'autoload_psr4.php' contains unexpected contents."
					);
				}

				$depsPath = str_replace('\\', '/', Env::getVars()->get('appDependenciesFilePath'));

				//import the composer rules
				foreach ($composerRules as $ns => $paths) {
					if (!array_key_exists($ns, $rules)) {
						$rules[$ns] = [];
					}

					foreach ($paths as $k => $path) {
						//replace references to the composer vendor dir path, with the app dependencies path
						$rules[$ns][$k] = preg_replace('/^' . preg_quote($composerDirPath, '/') . '/', $depsPath, str_replace('\\', '/', $path));
					}
				}
			}

			//add a rule for App
			$rules = array_merge_recursive($rules, array(
				'App\\' => array(Env::getVars()->get('appPackageFilePath') . '/App')
			));
		}

		return $rules;
	}

	/**
	 * Class load handler to be used with spl_autoload_register().
	 * @param $aClassName
	 * @throws Exception
	 */
	public function handleClassAutoload($aClassName) {
		$rules = static::getPsr4Rules();

		preg_match('/^(.+\\\\)(.+)$/', $aClassName, $matches);
		$namespace = $matches[1];
		$className = $matches[2];

		$nsStart = $namespace;
		$nsEnd = '';

		//search for a matching rule accounting for namespace, sub-namespace, sub-directory, etc.
		while (true) {
			if (array_key_exists($nsStart, $rules)) {
				foreach ($rules[$nsStart] as $dirPath) {
					$filePath = $dirPath . '/' . str_replace('\\', '/', $nsEnd) . $className . '.php';

					if (file_exists($filePath)) {
						/** @noinspection PhpIncludeInspection */
						include_once $filePath;
						break;
					}
				}
			}

			//move a sub-namespace from $nsStart to $nsEnd.
			$i = strrpos($nsStart, '\\', -2);
			if ($i === false) {
				break;
			}
			else {
				$nsEnd = substr($nsStart, $i + 1) . $nsEnd;
				$nsStart = substr($nsStart, 0, $i + 1);
			}
		}
	}
}
