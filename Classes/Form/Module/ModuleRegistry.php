<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Module;

class ModuleRegistry
{
	private static array $identifierToClass = [];

	public static function register(string $identifier, string $className): void
	{
		self::$identifierToClass[$identifier] = $className;
	}

	/**
	 * Resolve a short identifier to a FQCN.
	 * If $identifier contains a backslash it is assumed to already be a FQCN and is returned as-is,
	 * so records created before the alias system was introduced keep working.
	 */
	public static function resolve(string $identifier): string
	{
		// Registered alias -> class; otherwise return as-is (pre-alias records stored the FQCN directly).
		return self::$identifierToClass[$identifier] ?? $identifier;
	}

	public static function getAll(): array
	{
		return self::$identifierToClass;
	}
}
