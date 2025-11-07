<?php

namespace Amdeu\Shape\Form\Consent;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use Amdeu\Shape\Form;

final class ConsentFinisherExecutionHandler
{
	#[AsEventListener]
	public function __invoke(Form\Condition\FinisherConditionResolutionEvent $event): void
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
		if ($event->finisherConfiguration->getFinisherClassName() === Form\Finisher\EmailConsentFinisher::class) {
			$event->result = false;
			return;
		}
		// If splitFinisherExecution is false, execute finishers normally
		if (!($request->hasArgument('splitFinisherExecution') && $request->getArgument('splitFinisherExecution'))) {
			return;
		}
		// Check if there is an EmailConsentFinisher before this finisher, if not, skip this finisher
		foreach ($event->runtime->form->getFinisherConfigurations() as $configuration) {
			if ($configuration->getFinisherClassName() === Form\Finisher\EmailConsentFinisher::class) {
				return;
			}
			if ($configuration === $event->finisherConfiguration) {
				$event->result = false;
				return;
			}
		}
	}
}
