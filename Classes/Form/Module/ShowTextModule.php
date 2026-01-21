<?php

namespace Amdeu\Shape\Form\Module;

use TYPO3\CMS\Core;
use Amdeu\Shape\Form;

class ShowTextModule extends AbstractModule
{
	protected array $settings = [
		'bodytext' => ''
	];

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
	{
		$this->getView()->assignMultiple([
			'parsedText' => $this->parseWithValues($this->settings['bodytext']),
			'settings' => $this->settings,
			'plugin' => $this->getPlugin(),
			'form' => $this->getForm(),
		]);
		$html = $this->getView()->render('Module/ShowText');
		$event->response = new Core\Http\HtmlResponse($html);
	}
}