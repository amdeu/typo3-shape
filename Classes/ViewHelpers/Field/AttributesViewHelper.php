<?php

namespace UBOS\Shape\ViewHelpers\Field;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use UBOS\Shape\Form\Model\FieldInterface;

class AttributesViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('field', FieldInterface::class, '', true);
		$this->registerArgument('attributes', 'array', '', false, []);
	}

	public function getContentArgumentName(): string
	{
		return 'field';
	}

	public function render(): array
	{
		$field = $this->renderChildren();

		$name = "{$this->templateVariableContainer->get('namespace')}[{$field->get('name')}]";
		$id = "{$this->templateVariableContainer->get('idPrefix')}{$name}";
		$attributes = [
			'data-shape-control' => $field->get('name'),
			'id' => $id,
			'name' => $name
		];
		if ($field->has('validation_message') && $field->get('validation_message')) {
			$attributes['data-shape-validation-message'] = $field->get('validation_message');
		}
		if ($field->has('datalist') && $field->get('datalist') && $field->get('type') !== 'country-select') {
			$attributes['list'] = "{$id}-datalist";
		}
		foreach (['required', 'readonly', 'disabled', 'multiple'] as $attribute) {
			if (!$field->has($attribute)) continue;
			$val = $field->get($attribute);
			if ($val) {
				$attributes[$attribute] = '1';
			}
		}
		foreach (['pattern', 'maxlength', 'placeholder', 'accept', 'min', 'max'] as $attribute) {
			if (!$field->has($attribute)) continue;
			$val = $field->get($attribute);
			if ($val || $val === 0) {
				$attributes[$attribute] = (string)$val;
			}
		}
		if ($field->has('step') && $field->get('step')) {
			$attributes['step'] = (float)$field->get('step') ?: 'any';
		}
		if ($field->has('autocomplete') && $field->get('autocomplete')) {
			$attributes['autocomplete'] = $field->get('autocomplete_modifier') . $field->get('autocomplete');
		}

		// if the field is a file upload field and there are already filenames (names of files that have been validated and uploaded), the field is not required
		if ($field->get('type') == 'file' && $field->getSessionValue()) {
			unset($attributes['required']);
		}

		return array_merge($attributes, $this->arguments['attributes']);
	}
}
