<?php

namespace Amdeu\Shape\Form\Model;

use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core;

class FinisherConfigurationRecord extends Record implements FinisherConfigurationInterface
{

	protected ?array $settings = null;

	public function getFinisherClassName(): string
	{
		return $this->properties['type'] ?? '';
	}

	public function getSettings(): array
	{
		if ($this->settings !== null) {
			return $this->settings;
		}
		if (!$this->has('settings')) {
			$this->settings = [];
		} else {

			/**
			 * FlexFormFieldValues are stored as sheets, but we want the "merged" settings array
			 * Maybe we should adapt to the sheet structure, but I kinda think that sheets should be a presentational feature and stay independent of the data structure
			 * Sheets make it impossible to have a flat settings array while also having multiple tabs in the backend form
			 * also causes issues when arrays are defined over multiple sheets, i.e. if we have a structure of settings.options.value2 in sheet1 and settings.options.value2 in sheet2, FlexFormFieldValues::get('settings.options') returns nothing because settings.options is defined in two sheets
			 * FlexFormService supports both convertFlexFormContentToArray and convertFlexFormContentToSheetsArray but for some reason the default for FlexFormFieldValues is to use sheets structure
			 * since all field value transformations for Records are based on TCA, maybe the 'flexform' type in TCA should have an option to decide between sheets or merged structure?
			 */
			$flexFormService = Core\Utility\GeneralUtility::makeInstance(Core\Service\FlexFormService::class);
			$this->settings = $flexFormService->convertFlexFormContentToArray($this->getRawRecord()->get('settings'));
		}
		return $this->settings ?? [];
	}

	public function getCondition(): string
	{
		return $this->properties['condition'] ?? '';
	}

}