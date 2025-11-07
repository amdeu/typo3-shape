<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use Amdeu\Shape\Repository;

final class UniqueInTableValidator extends AbstractValidator
{
	protected $supportedOptions = [
		'table' => ['', 'Name of the table', 'string', true],
		'column' => ['', 'Name of the column to look for value in', 'string', true],
	];

	public function __construct(
		protected Repository\GenericRepositoryFactory $genericRepositoryFactory,
	)
	{
	}

	public function isValid(mixed $value): void
	{
		$repository = $this->genericRepositoryFactory
			->forTable($this->options['table'])
			->reset()
			->setIgnoreEnableFields(true);
		$count = $repository->countBy([$this->options['column'] => $value]);
		if ($count) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.unique_in_table',
					'shape',
				),
				1739105516
			);
		}
	}
}
