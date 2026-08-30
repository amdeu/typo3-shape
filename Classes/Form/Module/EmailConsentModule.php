<?php

namespace Amdeu\Shape\Form\Module;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use Amdeu\Shape\Form;
use Amdeu\Shape\Enum;
use Amdeu\Shape\Repository;

class EmailConsentModule extends AbstractModule
{
	protected array $settings = [
		'subject' => '',
		'body' => '',
		'template' => 'Module/EmailConsent',
		'consentPage' => '',
		'senderAddress' => '',
		'senderName' => '',
		'recipientAddress' => '',
		'replyToAddress' => '',
		'expirationPeriod' => 86400,
		'storagePage' => 0,
		'splitModuleExecution' => true,
	];

	public function __construct(
		protected Core\Crypto\HashService $hashService,
		protected Core\Mail\MailerInterface $mailer,
		protected Extbase\Configuration\ConfigurationManagerInterface $configurationManager,
		protected Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder,
		protected Repository\EmailConsentRepository $consentRepository,
	) {}

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
	{
		$recipientAddress = trim($this->parseWithValues($this->settings['recipientAddress']));

		if (!$recipientAddress) {
			$this->logger->warning('Recipient address is empty', $this->getLogContext());
			return;
		}

		// Must resolve to exactly one RFC-valid address - a submitted value used here (double opt-in on
		// the user's own address) must not be able to inject extra recipients via commas/newlines.
		if (!filter_var($recipientAddress, FILTER_VALIDATE_EMAIL)) {
			$this->logger->warning('Recipient address is not a valid e-mail address', $this->getLogContext(['value' => $recipientAddress]));
			return;
		}

		if (!$this->settings['subject']) {
			$this->logger->warning('Subject is empty', $this->getLogContext());
			return;
		}

		if (!$this->settings['consentPage']) {
			$this->logger->warning('Consent page not configured', $this->getLogContext());
			return;
		}

		$senderAddressString = $this->settings['senderAddress']
			?: ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '');
		if (!filter_var($senderAddressString, FILTER_VALIDATE_EMAIL)) {
			$this->logger->error('No valid sender address configured', $this->getLogContext());
			return;
		}

		$storagePage = (int)($this->settings['storagePage'] ?: $this->getPlugin()->getPid() ?? $this->getForm()->getPid());
		$formValues = $this->getFormValues();
		$timestamp = time();
		$serializedSession = Form\FormSession::serialize($this->runtime->session);

		$consentData = [
			'crdate' => $timestamp,
			'tstamp' => $timestamp,
			'pid' => $storagePage,
			'status' => Enum\ConsentStatus::Pending->value,
			'email' => $recipientAddress,
			'form' => $this->getForm()->getIdentifier(),
			'plugin' => $this->getPlugin()->getUid(),
			'session' => $serializedSession,
			'module_settings' => json_encode($this->settings),
			'valid_until' => $timestamp + $this->settings['expirationPeriod'],
		];

		$consentData['validation_hash'] = $this->hashService->hmac(
			$consentData['session'] . '_' . $consentData['crdate'],
			$consentData['email']
		);

		$consentUid = $this->consentRepository->create($consentData);

		$subject = $this->parseWithValues($this->settings['subject']);
		$template = $this->settings['template'];
		$format = Core\Mail\FluidEmail::FORMAT_BOTH;
		$senderAddress = new Address(
			$senderAddressString,
			$this->settings['senderName'] ?: ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? '')
		);

		$replyToAddress = null;
		if ($this->settings['replyToAddress']) {
			$parsedReplyTo = trim($this->parseWithValues($this->settings['replyToAddress']));
			if (filter_var($parsedReplyTo, FILTER_VALIDATE_EMAIL)) {
				$replyToAddress = $parsedReplyTo;
			} else {
				$this->logger->warning('Skipped invalid reply-to address', $this->getLogContext(['value' => $parsedReplyTo]));
			}
		}

		$approveLink = $this->uriBuilder
			->reset()
			->setTargetPageUid($this->settings['consentPage'])
			->setRequest($this->getRequest())
			->setCreateAbsoluteUri(true)
			->uriFor(
				'consentVerification',
				[
					'status' => Enum\ConsentStatus::Approved->value,
					'verify' => (bool)($this->settings['requireApproveVerification'] ?? false),
					'uid' => $consentUid,
					'hash' => $consentData['validation_hash']
				],
				'Consent',
				'shape',
				'Consent'
			);
		$dismissLink = $this->uriBuilder
			->uriFor(
				'consentVerification',
				[
					'status' => Enum\ConsentStatus::Dismissed->value,
					'verify' => (bool)($this->settings['requireDismissVerification'] ?? false),
					'uid' => $consentUid,
					'hash' => $consentData['validation_hash']
				],
				'Consent',
				'shape',
				'Consent'
			);

		$this->consentRepository->update($consentUid, [
			'approve_link' => $approveLink,
		]);

		$variables = [
			'formValues' => $formValues,
			'settings' => $this->settings,
			'runtime' => $this->runtime,
			'approveLink' => $approveLink,
			'dismissLink' => $dismissLink,
			'parsed' => [
				'body' => $this->parseWithValues($this->settings['body'], true)
			]
		];

		$email = new Core\Mail\FluidEmail($this->getView()->getRenderingContext()->getTemplatePaths());
		$email
			->from($senderAddress)
			->to($recipientAddress)
			->subject($subject)
			->setRequest($this->getRequest())
			->format($format)
			->setTemplate($template)
			->assignMultiple($variables);
		if ($replyToAddress) {
			$email->replyTo($replyToAddress);
		}

		try {
			$this->mailer->send($email);
			$this->logger->info('Consent email sent', $this->getLogContext([
				'consentUid' => $consentUid,
			]));
		} catch (\Exception $e) {
			$this->logger->error('Failed to send consent email', $this->getLogContext([
				'consentUid' => $consentUid,
				'error' => $e->getMessage(),
			]));
			return;
		}

		if ($this->settings['splitModuleExecution']) {
			$event->stopPropagation();
		}
	}
}