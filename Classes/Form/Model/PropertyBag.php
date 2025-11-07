<?php

namespace Amdeu\Shape\Form\Model;

use Psr\Container\ContainerInterface;

class PropertyBag implements ContainerInterface
{
	public function __construct(
		protected array $properties = []
	) {}

	public function get(string $id): mixed
	{
		return $this->properties[$id] ?? null;
	}

	public function has(string $id): bool
	{
		return array_key_exists($id, $this->properties);
	}

	public function set(string $key, mixed $value): self
	{
		$this->properties[$key] = $value;
		return $this;
	}

	public function toArray(): array
	{
		return $this->properties;
	}
}