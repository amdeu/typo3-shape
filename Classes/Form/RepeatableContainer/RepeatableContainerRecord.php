<?php

namespace Amdeu\Shape\Form\RepeatableContainer;

use Amdeu\Shape\Form\Model\FieldRecord;

class RepeatableContainerRecord extends FieldRecord
{
	protected ?array $createdFieldsets = null;

	protected function initialize(): void
	{
		parent::initialize();
		if (!$this->get('display_condition')) {
			$this->properties['display_condition'] = 'true';
		}
	}

	public function setSessionValue(mixed $value): void
	{
		$this->sessionValue = $value;
		$this->createdFieldsets = null;
	}

	/** @return FieldRecord[][] */
	public function getCreatedFieldsets(): array
	{
		if ($this->createdFieldsets !== null) {
			return $this->createdFieldsets;
		}
		if (!$this->getSessionValue()) {
			return [];
		}
		$index = 0;
		foreach ($this->getSessionValue() as $values) {
			foreach($this->get('fields') as $childField) {
				$newField = clone $childField;
				$name = $newField->getName();
				if (isset($values[$name])) {
					$newField->setSessionValue($values[$name]);
				}
				$this->createdFieldsets[$index][] = $newField;
			}
			$index++;
		}
		return $this->createdFieldsets;
	}
}
