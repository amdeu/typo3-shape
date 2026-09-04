<?php

declare(strict_types=1);

namespace Amdeu\Shape\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Amdeu\Shape\Form;
use Amdeu\Shape\Enum;
use Amdeu\Shape\Repository;

/**
 * Handles the double-opt-in confirmation reached from the link in an EmailConsentModule email.
 *
 * The click-through is split into a GET and a POST action on purpose: mail security scanners, link
 * prefetchers and webmail previews routinely fetch every link in an email, so opening the link must
 * not change anything.
 *
 *  - consentFormAction()         (GET)  - validates the token, shows a page with a "confirm" button
 *                                         (and, if enabled, a "decline" button).
 *  - consentConfirmationAction() (POST) - a button target. Re-finishes the stored form and writes
 *                                         the new consent status. Any non-POST request bounces back
 *                                         to consentFormAction().
 */
class ConsentController extends ActionController
{
	public function __construct(
		protected Repository\EmailConsentRepository $consentRepository,
		protected Form\FormRuntimeFactory $formRuntimeFactory,
	)
	{
	}

	/**
	 * GET landing page for the email link. Side-effect free: validates the token and renders either
	 * an error message or the confirm / decline buttons (which POST to consentConfirmationAction()).
	 */
	public function consentFormAction(int $uid = 0, string $hash = ''): ResponseInterface
	{
		$consent = $this->resolveConsent($uid, $hash);
		if ($consent instanceof Form\FormMessage) {
			return $this->messageResponse([$consent]);
		}

		$consentSettings = json_decode((string)$consent['module_settings'], true);

		$this->view->assignMultiple([
			'plugin' => $this->request->getAttribute('currentContentObject')?->data,
			'showDismissButton' => (bool)($consentSettings['showDismissButton'] ?? false),
			'approveUri' => $this->confirmationUri(Enum\ConsentStatus::Approved, $uid, $hash),
			'dismissUri' => $this->confirmationUri(Enum\ConsentStatus::Dismissed, $uid, $hash),
		]);
		return $this->htmlResponse();
	}

	/**
	 * POST target of the confirm / decline buttons. Re-finishes the form for the stored session and
	 * writes the new consent status. A non-POST request (prefetch, reload of the URL) or a stale /
	 * invalid token is bounced back to consentFormAction() instead of acting.
	 */
	public function consentConfirmationAction(
		?Enum\ConsentStatus $status = null,
		int $uid = 0,
		string $hash = ''
	): ResponseInterface
	{
		$backToForm = fn(): ResponseInterface => $this->redirectToUri(
			$this->uriBuilder->reset()->uriFor('consentForm', ['uid' => $uid, 'hash' => $hash])
		);

		if (
			$this->request->getMethod() !== 'POST'
			|| $status === null
			|| $status === Enum\ConsentStatus::Pending
		) {
			return $backToForm();
		}

		$consent = $this->resolveConsent($uid, $hash);
		if ($consent instanceof Form\FormMessage) {
			return $backToForm();
		}

		// resolveConsent() has already verified this decodes to an array.
		$consentSettings = json_decode((string)$consent['module_settings'], true);

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

		// Hand off to the Form plugin's "finished" action, exactly as a normal submission does - it
		// renders there, with the Form plugin's template paths and the "c<uid>" anchor on its page.
		$finishToken = Form\FinishedForm::fromFinishEvent($finishResult)->stash($this->request);
		return $this->redirectToUri(
			$this->uriBuilder
				->reset()
				->setCreateAbsoluteUri(true)
				->setSection('c' . $runtime->plugin->getUid())
				->setTargetPageUid($runtime->plugin->getPid())
				->uriFor('finished', ['finishToken' => $finishToken], 'Form', 'Shape', 'Form')
		);
	}

	/**
	 * Shared, side-effect-free validation for both actions.
	 *
	 * @return array<string, mixed>|Form\FormMessage The raw consent row on success, otherwise the
	 *         message to show the visitor.
	 */
	protected function resolveConsent(int $uid, string $hash): array|Form\FormMessage
	{
		if (!$uid || !$hash) {
			return Form\FormMessage::fromKey('label.invalid_consent_request', ContextualFeedbackSeverity::WARNING);
		}

		$consent = $this->consentRepository
			->reset()
			->setReturnRawQueryResult(true)
			->findByUid($uid);

		if (!$consent) {
			return Form\FormMessage::fromKey('label.consent_not_found', ContextualFeedbackSeverity::ERROR);
		}
		if (!hash_equals((string)$consent['validation_hash'], $hash)) {
			return Form\FormMessage::fromKey('label.invalid_consent_hash', ContextualFeedbackSeverity::ERROR);
		}
		if ((int)$consent['status'] !== Enum\ConsentStatus::Pending->value) {
			return Form\FormMessage::fromKey('label.consent_not_pending', ContextualFeedbackSeverity::INFO);
		}
		if (time() > (int)$consent['valid_until']) {
			return Form\FormMessage::fromKey('label.consent_expired', ContextualFeedbackSeverity::INFO);
		}
		if (!is_array(json_decode((string)$consent['module_settings'], true))) {
			return Form\FormMessage::fromKey('label.invalid_consent_request', ContextualFeedbackSeverity::ERROR);
		}
		try {
			// The confirmation re-finishes the stored form; if its session can no longer be restored
			// (encryption key changed, tampering), there is nothing to confirm - fail on the GET
			// rather than 500 when the button is pressed.
			Form\FormSession::validateAndUnserialize((string)$consent['session']);
		} catch (Form\Exception\InvalidSessionException) {
			return Form\FormMessage::fromKey('label.invalid_consent_hash', ContextualFeedbackSeverity::ERROR);
		}

		return $consent;
	}

	protected function confirmationUri(Enum\ConsentStatus $status, int $uid, string $hash): string
	{
		return $this->uriBuilder->reset()->uriFor('consentConfirmation', [
			'status' => $status->value,
			'uid' => $uid,
			'hash' => $hash,
		]);
	}

	/**
	 * Renders ConsentForm.html with just a message (a stale/invalid token). consentConfirmationAction
	 * never lands here - it redirects: to consentForm on failure, to the Form's finished action on
	 * success.
	 *
	 * @param Form\FormMessage[] $messages
	 */
	protected function messageResponse(array $messages): ResponseInterface
	{
		$this->view->assign('messages', $messages);
		$this->view->assign('plugin', $this->request->getAttribute('currentContentObject')?->data);
		return $this->htmlResponse();
	}
}
