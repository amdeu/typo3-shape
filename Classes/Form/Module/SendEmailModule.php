<?php

namespace Amdeu\Shape\Form\Module;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core;
use Amdeu\Shape\Form;

class SendEmailModule extends AbstractModule
{
	protected array $settings = [
		'subject' => '',
		'body' => '',
		'attachUploads' => false,
		'template' => '',
		'senderAddress' => '',
		'senderName' => '',
		'recipientAddresses' => '',
		'ccRecipientAddresses' => '',
		'bccRecipientAddresses' => '',
		'replyToAddresses' => '',
	];

	public function __construct(
		protected Core\Mail\MailerInterface $mailer,
		protected Core\Resource\ResourceFactory $resourceFactory,
	) {}

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void	{
		$recipients = $this->getAddresses($this->settings['recipientAddresses']);
		if (!$recipients) {
			$this->logger->warning('No valid recipients', $this->getLogContext());
			return;
		}

		$subject = $this->parseWithValues($this->settings['subject']);
		if (!$subject) {
			$this->logger->warning('Subject is empty', $this->getLogContext());
			return;
		}

		$senderAddressString = $this->settings['senderAddress']
			?: ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '');
		if (!filter_var($senderAddressString, FILTER_VALIDATE_EMAIL)) {
			$this->logger->error('No valid sender address configured', $this->getLogContext());
			return;
		}

		$email = new Core\Mail\FluidEmail($this->getView()->getRenderingContext()->getTemplatePaths());
		$senderAddress = new Address(
			$senderAddressString,
			$this->settings['senderName'] ?: ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? '')
		);
		$template = $this->settings['template'] ?: 'Module/SendEmail/Default';
		$templateConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape']['modules']['sendEmail']['templates'][$template] ?? [];
		$format = $templateConfig['format'] ?? Core\Mail\FluidEmail::FORMAT_BOTH;

		$formValues = $this->getFormValues();
		$variables = [
			'formValues' => $formValues,
			'settings' => $this->settings,
			'runtime' => $this->runtime,
			'parsed' => [
				'body' => $this->parseWithValues($this->settings['body'], true)
			]
		];
		foreach ($templateConfig['fields'] ?? [] as $key => $config) {
			$variables['parsed'][$key] = $this->parseWithValues($this->settings[$key] ?? '');
		}

		$email
			->from($senderAddress)
			->to(...$recipients)
			->subject($subject)
			->setRequest($this->getRequest())
			->format($format)
			->setTemplate($template)
			->assignMultiple($variables);

		if ($cc = $this->getAddresses($this->settings['ccRecipientAddresses'])) {
			$email->cc(...$cc);
		}
		if ($bcc = $this->getAddresses($this->settings['bccRecipientAddresses'])) {
			$email->bcc(...$bcc);
		}
		if ($replyTo = $this->getAddresses($this->settings['replyToAddresses'])) {
			$email->replyTo(...$replyTo);
		}

		if ($this->settings['attachUploads']) {
			foreach ($this->getForm()->get('pages') as $page) {
				foreach ($page->getFields() as $field) {
					if ($field->get('type') === 'file' && isset($formValues[$field->get('name')])) {
						foreach ($formValues[$field->get('name')] as $fileIdentifier) {
							try {
								$file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($fileIdentifier);
								if ($file && $file->exists()) {
									$email->attach($file->getContents(), $file->getName(), $file->getMimeType());
								}
							} catch (\Exception $e) {
								$this->logger->warning('Could not attach file', $this->getLogContext([
									'file' => $fileIdentifier,
									'error' => $e->getMessage(),
								]));
							}
						}
					}
				}
			}
		}

		try {
			$this->mailer->send($email);
			$this->logger->info('Email sent', $this->getLogContext([
				'recipients' => count($recipients),
			]));
		} catch (\Exception $e) {
			$this->logger->error('Failed to send email', $this->getLogContext([
				'error' => $e->getMessage(),
			]));
		}
	}

	/**
	 * Resolves a comma-separated list of address templates to validated Address objects.
	 *
	 * Each list entry is parsed on its own and must resolve to exactly one RFC-valid address
	 * (a bare address or a "Name <address>" form). This is deliberate: entries commonly contain
	 * {{ field }} placeholders pointing at submitted values (autoresponders etc.), and a submitted
	 * value must not be able to smuggle in additional recipients via commas or header-injection
	 * newlines - such a value fails to parse and is dropped.
	 *
	 * @return Address[]
	 */
	protected function getAddresses(string $addressList): array
	{
		$addresses = [];
		foreach (Core\Utility\GeneralUtility::trimExplode(',', $addressList, true) as $addressTemplate) {
			$parsed = trim($this->parseWithValues($addressTemplate));
			if ($parsed === '' || str_starts_with($parsed, '{{')) {
				continue;
			}
			try {
				$addresses[] = Address::create($parsed);
			} catch (\Exception $e) {
				$this->logger->warning('Skipped invalid e-mail recipient', $this->getLogContext(['value' => $parsed]));
			}
		}
		return $addresses;
	}
}