<?php

namespace Amdeu\Shape\Form\Consent;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use Amdeu\Shape\Form;

final class ConsentModuleOnFinishHandler
{
	#[AsEventListener]
	public function __invoke(Form\Condition\ModuleConditionResolutionEvent $event): void
	{
		if ($event->isPropagationStopped()) {
			return;
		}
		$request = $event->runtime->request;

		// Check if we are in Consent plugin execution flow
		if ($request->getControllerExtensionKey() !== 'shape' || $request->getPluginName() !== 'Consent') {
			return;
		}
		// Never execute EmailConsentFinisher in Consent execution flow
		if ($event->moduleConfiguration->getModuleClassName() === Form\Module\EmailConsentModule::class) {
			$event->result = false;
			return;
		}
		// If splitFinisherExecution is false, execute modules normally
		if (!($request->hasArgument('splitFinisherExecution') && $request->getArgument('splitFinisherExecution'))) {
			return;
		}
		// Check if there is an EmailConsentModule before this module, if not, skip this module
		foreach ($event->runtime->form->getModuleConfigurations() as $configuration) {
			if ($configuration->getModuleClassName() === Form\Module\EmailConsentModule::class) {
				return;
			}
			if ($configuration === $event->moduleConfiguration) {
				$event->result = false;
				return;
			}
		}
	}
}
