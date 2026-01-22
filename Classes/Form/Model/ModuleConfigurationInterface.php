<?php

namespace Amdeu\Shape\Form\Model;

interface ModuleConfigurationInterface
{
	public function getIdentifier(): int|string;

	public function getModuleClassName(): string;

	public function getSettings(): array;

	public function getCondition(): ?string;
}