<?php

namespace Amdeu\Shape\Form\Module;

use TYPO3\CMS\Core;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Amdeu\Shape\Form;

class RedirectModule extends AbstractModule
{
	protected array $settings = [
		'uri' => '',
		'statusCode' => 303,
	];

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
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

		$event->response = new Core\Http\RedirectResponse($url, $this->settings['statusCode']);

		$this->logger->info('Redirect created', $this->getLogContext([
			'url' => $url,
		]));
	}
}