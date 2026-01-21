<?php

namespace Amdeu\Shape\Form\Module;

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
abstract class AbstractModule implements ModuleInterface, LoggerAwareInterface
{
	use LoggerAwareTrait;

	protected array $settings = [];
	protected Form\FormRuntime $runtime;

	public function setRuntime(Form\FormRuntime $runtime): void
	{
		$this->runtime = $runtime;
	}

	public function setSettings(array $settings): void
	{
		Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->settings, $settings);
	}

	public function validate(): Result
	{
		return new Result();
	}

	protected function getRequest(): RequestInterface
	{
		return $this->runtime->request;
	}

	protected function getPlugin(): Core\Domain\Record
	{
		return $this->runtime->plugin;
	}

	protected function getForm(): Form\Model\FormInterface
	{
		return $this->runtime->form;
	}

	protected function getFormValues(): array
	{
		return $this->runtime->session->values;
	}

	protected function getPluginSettings(): array
	{
		return $this->runtime->settings;
	}

	protected function getView(): Core\View\ViewInterface
	{
		return $this->runtime->view;
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