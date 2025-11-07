<?php

declare(strict_types=1);

namespace Amdeu\Shape\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Amdeu\Shape\Form;
use Amdeu\Shape\Enum;
use Amdeu\Shape\Repository;

class ConsentController extends ActionController
{
	public function __construct(
		protected Repository\EmailConsentRepository $consentRepository,
		protected Form\FormRuntimeFactory $formRuntimeFactory,
	)
	{
	}

	public function consentVerificationAction(
		Enum\ConsentStatus $status,
		int $uid = 0,
		string $hash = '',
		bool $verify = false
	): ResponseInterface
	{
		if (!$uid || !$hash || $status === Enum\ConsentStatus::Pending) {
			return $this->messageResponse([['key' => 'label.invalid_consent_request', 'type' => 'warning']]);
		}
		$consent = $this->consentRepository
			->reset()
			->setReturnRawQueryResult(true)
			->findByUid($uid);

		if (!$consent) {
			return $this->messageResponse([['key' => 'label.consent_not_found', 'type' => 'error']]);
		}
		if ($hash !== $consent['validation_hash']) {
			return $this->messageResponse([['key' => 'label.invalid_consent_hash', 'type' => 'error']]);
		}
		if ($consent['status'] !== Enum\ConsentStatus::Pending->value) {
			return $this->messageResponse([['key' => 'label.consent_not_pending', 'type' => 'info']]);
		}
		if (time() > $consent['valid_until']) {
			return $this->messageResponse([['key' => 'label.consent_expired', 'type' => 'info']]);
		}

		// If verify is set, just show the verification page
		if ($verify) {
			$this->view->assign('plugin', $this->request->getAttribute('currentContentObject')->data);
			$this->view->assign('status', $status);
			$this->view->assign('verificationLink', $this->uriBuilder->uriFor('consent', [
				'status' => $status,
				'uid' => $uid,
				'hash' => $hash,
			]));
			return $this->htmlResponse();
		}

		// Otherwise, re-finish the form
		$consentSettings = json_decode($consent['finisher_settings'], true);

		$request = $this->request->withArgument(
			'splitFinisherExecution',
			$consentSettings['splitFinisherExecution']
		);

		$runtime = $this->formRuntimeFactory->recreateFromRequestAndConsent(
			$request,
			$this->view,
			$consent
		);
		$finishResult = $runtime->finishForm(['consentStatus' => $status->value]);

		if ($consentSettings['deleteAfterConfirmation']) {
			$this->consentRepository->remove($uid, false);
		} else {
			$this->consentRepository->update(
				$uid,
				['status' => $status->value, 'valid_until' => null]
			);
		}

		if ($finishResult->response) {
			return $finishResult->response;
		}
		$redirectUri = $this->uriBuilder
			->reset()
			->setCreateAbsoluteUri(true)
			->setSection("c" . $runtime->plugin->getUid())
			->setTargetPageUid($runtime->plugin->getPid())
			->uriFor(
				'finished',
				$finishResult->finishedActionArguments,
				'Form',
				'Shape',
				'Form'
		);
		return $this->redirectToUri($redirectUri);
	}

	protected function messageResponse(array $messages): ResponseInterface
	{
		$this->view->assign('messages', $messages);
		$this->view->assign('plugin', $this->request->getAttribute('currentContentObject')->data);
		return $this->htmlResponse();
	}
}
