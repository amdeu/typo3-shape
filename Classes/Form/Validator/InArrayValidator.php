<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

final class InArrayValidator extends AbstractValidator
{
	protected $supportedOptions = [
		'array' => [[], 'The array to use for validation', 'array', true],
		'strict' => [true, 'If set to true, the comparison is strict (===)', 'bool', false],
	];

	public function isValid(mixed $value): void
	{
		if (!is_array($this->options['array'])) {
			throw new InvalidValidationOptionsException('Option "array" must be of type array.', 1739105353);
		}
		if (!in_array($value, $this->options['array'], (bool)$this->options['strict'])) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.in_array',
					'shape',
				),
				1739105354
			);
		}
	}
}
