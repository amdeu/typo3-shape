<?php

namespace Amdeu\Shape\Form\Module;

use Amdeu\Shape\Form;

class ShowContentElementsModule extends AbstractModule
{
	protected array $settings = [
		'contentElements' => ''
	];

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
	{
		$event->finishedTemplate = 'Module/ShowContentElements';
		$event->addFinishedVariables([
			'contentElements' => explode(',', $this->settings['contentElements']),
		]);
	}
}