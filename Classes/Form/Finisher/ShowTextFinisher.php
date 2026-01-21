<?php

namespace Amdeu\Shape\Form\Finisher;

use TYPO3\CMS\Core;

class ShowTextFinisher extends AbstractFinisher
{
	protected array $settings = [
		'bodytext' => ''
	];

	public function executeInternal(): void
	{
		$this->getView()->assignMultiple([
			'parsedText' => $this->parseWithValues($this->settings['bodytext']),
			'settings' => $this->settings,
			'plugin' => $this->getPlugin(),
			'form' => $this->getForm(),
		]);
		$html = $this->getView()->render('Finisher/ShowText');
		$this->context->response = new Core\Http\HtmlResponse($html);
	}
}