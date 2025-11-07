<?php

namespace Amdeu\Shape\Form\Finisher;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core;

class SendEmailFinisher extends AbstractFinisher
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

	public function executeInternal(): void
	{
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

		$email = new Core\Mail\FluidEmail($this->getView()->getRenderingContext()->getTemplatePaths());
		$senderAddress = new Address(
			$this->settings['senderAddress'] ?: $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'],
			$this->settings['senderName'] ?: $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
		);
		$template = $this->settings['template'] ?: 'Finisher/SendEmail/Default';
		$templateConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape']['finishers']['sendEmail']['templates'][$template] ?? [];
		$format = $templateConfig['format'] ?? Core\Mail\FluidEmail::FORMAT_BOTH;

		$formValues = $this->getFormValues();
		$variables = [
			'formValues' => $formValues,
			'settings' => $this->settings,
			'runtime' => $this->getRuntime(),
			'parsed' => [
				'body' => $this->parseWithValues($this->settings['body'])
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

		if ($this->settings['ccRecipientAddresses']) {
			$email->cc(...$this->getAddresses($this->settings['ccRecipientAddresses']));
		}
		if ($this->settings['bccRecipientAddresses']) {
			$email->bcc(...$this->getAddresses($this->settings['bccRecipientAddresses']));
		}
		if ($this->settings['replyToAddresses']) {
			$email->replyTo(...$this->getAddresses($this->settings['replyToAddresses']));
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

	protected function getAddresses(string $addressList): array
	{
		$addressList = $this->parseWithValues($addressList);
		$addresses = [];
		foreach (Core\Utility\GeneralUtility::trimExplode(',', $addressList, true) as $address) {
			if (str_starts_with($address, '{{')) {
				continue;
			}
			$addresses[] = $address;
		}
		return $addresses;
	}
}