<?php

namespace Amdeu\Shape\Form\Finisher;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Validation\Error;
use Amdeu\Shape\Form;
use Amdeu\Shape\Utility;

#[Autoconfigure(public: true, shared: false)]
abstract class AbstractFinisher implements FinisherInterface, LoggerAwareInterface
{
	use LoggerAwareTrait;

	protected FinisherExecutionContext $context;

	protected array $settings = [];

	public function setSettings(array $settings): void
	{
		Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->settings, $settings);
	}

	public function validate(): Result
	{
		return new Result();
	}

	final public function execute(FinisherExecutionContext $context): void
	{
		if ($context->isCancelled()) {
			return;
		}
		$this->context = $context;
		$this->executeInternal();
	}

	abstract public function executeInternal(): void;

	protected function getRuntime(): Form\FormRuntime
	{
		return $this->context->runtime;
	}

	protected function getRequest(): RequestInterface
	{
		return $this->context->runtime->request;
	}

	protected function getPlugin(): Core\Domain\Record
	{
		return $this->context->runtime->plugin;
	}

	protected function getForm(): Form\Model\FormInterface
	{
		return $this->context->runtime->form;
	}

	protected function getFormValues(): array
	{
		return $this->context->runtime->session->values;
	}

	protected function getPluginSettings(): array
	{
		return $this->context->runtime->settings;
	}

	protected function getView(): Core\View\ViewInterface
	{
		return $this->context->runtime->view;
	}

	protected function addValidationError(
		string $message,
		int $code,
		string $propertyPath = ''
	): Result {
		$result = new Result();

		if ($propertyPath) {
			$result->forProperty($propertyPath)->addError(
				new Error($message, $code)
			);
		} else {
			$result->addError(new Error($message, $code));
		}

		return $result;
	}

	protected function parseWithValues(string $string): string
	{
		return Utility\TemplateVariableParser::parse($string, $this->getFormValues());
	}

	/**
	 * Get minimal log context - only include form UID for correlation
	 */
	protected function getLogContext(array $additionalContext = []): array
	{
		return array_merge([
			'formUid' => $this->getForm()->getUid(),
		], $additionalContext);
	}

}