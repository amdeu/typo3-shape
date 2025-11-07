<?php

namespace Amdeu\Shape\Form\Validation;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core;
use Amdeu\Shape\Form;

final class UniqueInSubmissionValidationConfigurator
{
	#[AsEventListener]
	public function __invoke(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if (! $field->has('unique_in_submission') || ! $field->get('unique_in_submission')) {
			return;
		}
		$validator = Core\Utility\GeneralUtility::makeInstance(Form\Validator\UniqueInSubmissionsValidator::class);
		$validator->setOptions([
			'fieldName' => $field->getName(),
			'pluginUid' => $event->runtime->plugin->getUid(),
			'formUid' => $event->runtime->form->getUid(),
		]);
		$event->addValidator($validator);
	}
}