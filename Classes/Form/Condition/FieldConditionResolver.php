<?php

namespace Amdeu\Shape\Form\Condition;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use Amdeu\Shape\Form;

class FieldConditionResolver
{
	public function __construct(
		protected EventDispatcherInterface $eventDispatcher
	)
	{
	}

	public function evaluate(
		Form\FormRuntime $runtime,
		Form\Model\FieldInterface $field,
		Resolver $resolver,
	): mixed
	{
		if (!$field->has('display_condition') || !$field->get('display_condition')) {
			return true;
		}
		$event = new FieldConditionResolutionEvent(
			$runtime,
			$field,
			$resolver
		);
		$this->eventDispatcher->dispatch($event);
		if ($event->isPropagationStopped()) {
			return $event->result;
		}
		return $resolver->evaluate($field->get('display_condition'));
	}

}