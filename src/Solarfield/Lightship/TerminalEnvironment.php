<?php
namespace Solarfield\Lightship;

use Throwable;

class TerminalEnvironment extends Environment {
	public function bail(Throwable $aException): DestinationContextInterface {
		$this->getLogger()->error($aException->getMessage(), [
			'exception' => $aException,
		]);

		$this->getStandardOutput()->error('FATAL ERROR: ' . $aException->getMessage());

		return new TerminalDestinationContext(1);
	}

	public function route(SourceContextInterface $aContext): SourceContextInterface {
		$context = parent::route($aContext);

		if (($inputModule = $context->getRoute()->getNextStep()) !== null) {
			$availableModules = array_filter(
				scandir($this->getVars()->get('appPackageFilePath') . '/App/Modules'),
				function ($name) {
					return preg_match('/[a-z0-9]+/i', $name) == true;
				}
			);

			if (in_array($inputModule, $availableModules, true)) {
				$context->setRoute([
					'moduleCode' => (string)$inputModule,
				]);
			}
		}

		return $context;
	}
}
