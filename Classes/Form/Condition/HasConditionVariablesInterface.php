<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Condition;

/**
 * Optional companion to FormEventInterface. An event that carries extra, event-specific variables
 * (e.g. FormFinishEvent's consent status) implements this so ModuleInvoker::handleEvent() can feed
 * them into the resolver used to evaluate a module's `condition` field for that particular dispatch.
 */
interface HasConditionVariablesInterface
{
	public function getConditionVariables(): array;
}
