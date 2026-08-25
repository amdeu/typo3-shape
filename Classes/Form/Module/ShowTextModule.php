<?php

namespace Amdeu\Shape\Form\Module;

use TYPO3\CMS\Core;
use Amdeu\Shape\Form;

class ShowTextModule extends AbstractModule
{
	protected array $settings = [
		'bodytext' => ''
	];

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
	{
		$event->finishedTemplate = 'Module/ShowText';
		$event->addFinishedVariables([
			'parsedBody' => $this->parseWithValues($this->settings['bodytext'])
		]);
	}
}