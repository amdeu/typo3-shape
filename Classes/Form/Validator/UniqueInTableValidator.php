<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
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
		$table = (string)$this->options['table'];
		$column = (string)$this->options['column'];

		if (!isset($GLOBALS['TCA'][$table])) {
			throw new InvalidValidationOptionsException(
				sprintf('UniqueInTableValidator: unknown table "%s".', $table),
				1739105517
			);
		}
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
			throw new InvalidValidationOptionsException(
				sprintf('UniqueInTableValidator: invalid column name "%s".', $column),
				1739105518
			);
		}

		$count = $this->genericRepositoryFactory
			->forTable($table)
			->reset()
			->setIgnoreEnableFields(true)
			// uniqueness is checked across every language, not just the visitor's current one
			->setRespectSysLanguage(false)
			->countBy([$column => $value]);

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
