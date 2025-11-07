<?php

namespace Amdeu\Shape\Form\Finisher;

use TYPO3\CMS\Core;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class RedirectFinisher extends AbstractFinisher
{
	protected array $settings = [
		'uri' => '',
		'statusCode' => 303,
	];

	public function executeInternal(): void
	{
		if (!$this->settings['uri']) {
			$this->logger->warning('URI is empty', $this->getLogContext());
			return;
		}

		/** @var ContentObjectRenderer $contentObject */
		$contentObject = Core\Utility\GeneralUtility::makeInstance(
			ContentObjectRenderer::class,
			$this->getRequest()->getAttribute('frontend.controller')
		);
		$url = $contentObject->createUrl(['parameter' => $this->settings['uri'], 'forceAbsoluteUrl' => true]);

		if (!$url) {
			$this->logger->warning('Could not create URL', $this->getLogContext([
				'parameter' => $this->settings['uri'],
			]));
			return;
		}

		$this->context->response = new Core\Http\RedirectResponse($url, $this->settings['statusCode']);

		$this->logger->info('Redirect created', $this->getLogContext([
			'url' => $url,
		]));
	}
}