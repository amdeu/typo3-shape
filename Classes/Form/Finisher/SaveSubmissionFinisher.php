<?php

namespace Amdeu\Shape\Form\Finisher;

use TYPO3\CMS\Core;
use Amdeu\Shape\Repository;

class SaveSubmissionFinisher extends AbstractFinisher
{
	protected array $settings = [
		'storagePage' => '',
		'connectToLanguageParentForm' => false,
		'saveUserData' => false,
		'excludedFields' => '',
	];

	public function __construct(
		protected Repository\FormSubmissionRepository $submissionRepository
	) {}

	public function executeInternal(): void
	{
		$formValues = $this->getFormValues();

		if ($this->settings['excludedFields']) {
			$excludeFields = Core\Utility\GeneralUtility::trimExplode(',', $this->settings['excludedFields'], true);
			$formValues = array_filter($formValues, function($key) use ($excludeFields) {
				return !in_array($key, $excludeFields);
			}, ARRAY_FILTER_USE_KEY);
		}

		$values = [
			'crdate' => time(),
			'tstamp' => time(),
			'pid' => (int)($this->settings['storagePage'] ?: $this->getPlugin()->getPid() ?? $this->getForm()->getPid()),
			'fe_user' => $this->getRequest()->getAttribute('frontend.user')->getUserId() ?: 0,
			'site_lang' => $this->getRequest()->getAttribute('language')->getLanguageId(),
			'form_values' => json_encode($formValues),
		];

		if ($this->settings['saveUserData']) {
			$values['user_agent'] = $this->getRequest()->getHeaderLine('User-Agent');
			$values['user_ip'] = $this->getRequest()->getServerParams()['REMOTE_ADDR'];
		}

		if ($this->settings['connectToLanguageParentForm']) {
			$values['form'] = $this->getForm()->getRawRecord()->get('l10n_parent') ?: $this->getForm()->getUid();
			$values['plugin'] = $this->getPlugin()->getRawRecord()->get('l18n_parent') ?: $this->getPlugin()->getUid();
		} else {
			$values['form'] = $this->getForm()->getUid();
			$values['plugin'] = $this->getPlugin()->getUid();
		}

		try {
			$newUid = $this->submissionRepository->create($values);
			$this->logger->info('Submission saved', $this->getLogContext([
				'uid' => $newUid,
			]));
		} catch (\Exception $e) {
			$this->logger->error('Failed to save submission', $this->getLogContext([
				'error' => $e->getMessage(),
			]));
		}
	}
}