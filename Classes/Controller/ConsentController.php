<?php

declare(strict_types=1);

namespace Amdeu\Shape\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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
			return $this->messageResponse([Form\FormMessage::fromKey('label.invalid_consent_request', ContextualFeedbackSeverity::WARNING)]);
		}
		$consent = $this->consentRepository
			->reset()
			->setReturnRawQueryResult(true)
			->findByUid($uid);

		if (!$consent) {
			return $this->messageResponse([Form\FormMessage::fromKey('label.consent_not_found', ContextualFeedbackSeverity::ERROR)]);
		}
		if (!hash_equals((string)$consent['validation_hash'], $hash)) {
			return $this->messageResponse([Form\FormMessage::fromKey('label.invalid_consent_hash', ContextualFeedbackSeverity::ERROR)]);
		}
		if ($consent['status'] !== Enum\ConsentStatus::Pending->value) {
			return $this->messageResponse([Form\FormMessage::fromKey('label.consent_not_pending', ContextualFeedbackSeverity::INFO)]);
		}
		if (time() > (int)$consent['valid_until']) {
			return $this->messageResponse([Form\FormMessage::fromKey('label.consent_expired', ContextualFeedbackSeverity::INFO)]);
		}

		// If verify is set, just show the verification page
		if ($verify) {
			$this->view->assign('plugin', $this->request->getAttribute('currentContentObject')?->data);
			$this->view->assign('status', $status);
			$this->view->assign('verificationLink', $this->uriBuilder->uriFor('consent', [
				'status' => $status,
				'uid' => $uid,
				'hash' => $hash,
			]));
			return $this->htmlResponse();
		}

		// Otherwise, re-finish the form
		$consentSettings = json_decode((string)$consent['module_settings'], true);
		if (!is_array($consentSettings)) {
			return $this->messageResponse([Form\FormMessage::fromKey('label.invalid_consent_request', ContextualFeedbackSeverity::ERROR)]);
		}

		$request = $this->request->withArgument(
			'splitModuleExecution',
			$consentSettings['splitModuleExecution'] ?? false
		);

		$runtime = $this->formRuntimeFactory->recreateFromRequestAndConsent(
			$request,
			$this->view,
			$consent
		);
		$finishResult = $runtime->finishForm(['consentStatus' => $status->value]);

		if ($consentSettings['deleteAfterConfirmation'] ?? false) {
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
		$nonce = bin2hex(random_bytes(16));
		$feAuth = $this->request->getAttribute('frontend.user');
		$feAuth->setAndSaveSessionData('tx_shape_finish_' . $nonce, [
			'template' => $finishResult->finishedTemplate,
			'variables' => $finishResult->finishedVariables,
			'formValues' => $runtime->session->values,
		]);
		$redirectUri = $this->uriBuilder
			->reset()
			->setCreateAbsoluteUri(true)
			->setSection("c" . $runtime->plugin->getUid())
			->setTargetPageUid($runtime->plugin->getPid())
			->uriFor(
				'finished',
				['finishToken' => $nonce],
				'Form',
				'Shape',
				'Form'
		);
		return $this->redirectToUri($redirectUri);
	}

	/**
	 * @param Form\FormMessage[] $messages
	 */
	protected function messageResponse(array $messages): ResponseInterface
	{
		$this->view->assign('messages', $messages);
		$this->view->assign('plugin', $this->request->getAttribute('currentContentObject')?->data);
		return $this->htmlResponse();
	}
}
