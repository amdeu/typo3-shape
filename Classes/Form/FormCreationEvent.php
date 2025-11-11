<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

final class FormCreationEvent
{
	public function __construct(
		public readonly RequestInterface $request,
		public readonly array $controllerSettings,
		public readonly Record $pluginRecord,
		public ?Model\FormInterface $form = null,
	)
	{
	}

	public function isPropagationStopped(): bool
	{
		return $this->form !== null;
	}
}