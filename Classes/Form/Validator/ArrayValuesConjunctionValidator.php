<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractCompositeValidator;

/**
 * Validator to chain many validators in a conjunction (logical and), and apply them to each value in a given array.
 */
final class ArrayValuesConjunctionValidator extends AbstractCompositeValidator
{
    public function __construct()
    {
        $this->validators = new \SplObjectStorage();
        $this->validatedInstancesContainer = new \SplObjectStorage();
    }

    /**
     * Checks if all values in the given array are valid according to the validators of the conjunction.
     * Every validator has to be valid for every value to make the whole conjunction valid.
     *
     * @param mixed $value The value that should be validated
     */
    public function validate(mixed $value): Result
    {
		if (!is_array($value)) {
			throw new \InvalidArgumentException('Value must be an array.', 1741367481);
		}
        $result = new Result();
		foreach ($value as $item) {
			/** @var AbstractValidator $validator */
			foreach ($this->getValidators() as $validator) {
				$result->merge($validator->validate($item));
			}
		}
        return $result;
    }
}
