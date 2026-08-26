<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * A message shown on a form page (e.g. "spam suspected", "session invalid"), either an LLL key to be
 * translated at render time, or an already-resolved literal string. Exactly one of $key/$text is set.
 */
final readonly class FormMessage
{
	private function __construct(
		public ?string $key,
		public array $arguments,
		public ?string $text,
		public ContextualFeedbackSeverity $type,
	) {}

	public static function fromKey(string $key, ContextualFeedbackSeverity $type, array $arguments = []): self
	{
		return new self($key, $arguments, null, $type);
	}

	public static function text(string $text, ContextualFeedbackSeverity $type = ContextualFeedbackSeverity::ERROR): self
	{
		return new self(null, [], $text, $type);
	}

	/**
	 * Lowercase severity name (e.g. "warning", "error") for use as a CSS class modifier.
	 * Deliberately not TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::getCssClass() - that maps
	 * ERROR to "danger", which doesn't match this extension's existing `-error` CSS convention.
	 */
	public function getCssModifier(): string
	{
		return strtolower($this->type->name);
	}
}
