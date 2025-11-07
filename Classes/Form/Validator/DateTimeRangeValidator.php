<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

final class DateTimeRangeValidator extends AbstractValidator
{
    protected $supportedOptions = [
        'minimum' => ['', 'The minimum date formatted like format option', 'string'],
        'maximum' => ['', 'The maximum date formatted like format option', 'string'],
        'format' => ['Y-m-d', 'The format of the minimum and maximum option', 'string'],
    ];

    public function isValid(mixed $value): void
    {
		$format = $this->options['format'];
		if (is_numeric($value)) {
			$value = date($format, $value);
		} else if ($value instanceof \DateTime) {
			$value = $value->format($format);
		} else if (!is_string($value)) {
			// Handle non-string, non-DateTime, non-numeric values
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.date_time_range.type',
					'shape',
					[gettype($value)]
				),
				1739104740
			);
			return;
		}

		$minimum = $this->createDateFromFormat($this->options['minimum'], 'minimum');
		$maximum = $this->createDateFromFormat($this->options['maximum'], 'maximum');
		$valueDate = $this->createDateFromFormat($value);

		if (!$valueDate) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.date_time_range.type',
					'shape',
					[$value]
				),
				1739104740
			);
			return;
		}

        if ($minimum && $valueDate < $minimum) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.date_time_range.minimum',
                    'shape',
                    [$minimum->format($format)]
                ),
				1739104741,
                [$minimum->format($format)]
            );
        }

        if ($maximum && $valueDate > $maximum) {
            $this->addError(
                $this->translateErrorMessage(
					'validation.error.date_time_range.maximum',
                    'shape',
                    [$maximum->format($format)]
                ),
				1739104742,
                [$maximum->format($format)]
            );
        }
    }

	protected function createDateFromFormat(string $value, string $optionName = ''): \DateTime|false
	{
		if (empty($value)) {
			return false;
		}
		$format = $this->options['format'];
		// Special handling for week format
		if ($format === 'Y-\WW' && preg_match('/^(\d{4})-W(\d{1,2})$/', $value, $matches)) {
			$year = (int)$matches[1];
			$week = (int)$matches[2];
			$date = new \DateTime();
			$date->setISODate($year, $week, 1)->setTime(0, 0, 0);
		} else {
			$date = \DateTime::createFromFormat('!'. $format, $value);
		}
		if (!$date && $optionName) {
			throw new InvalidValidationOptionsException(
				'The option "'. $optionName .'" ('. $value .') could not be converted to \DateTime from format "'. $this->options['format'] .'".'
				, 1739104743);
		}
		return $date;
	}
}
