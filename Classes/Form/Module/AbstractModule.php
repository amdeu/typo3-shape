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
	protected ?Form\Model\ModuleConfigurationInterface $configuration = null;

	public function configure(
		Form\FormRuntime $runtime,
		?Form\Model\ModuleConfigurationInterface $configuration
	): void
	{
		$this->runtime = $runtime;
		if ($configuration) {
			$this->configuration = $configuration;
			$this->overrideSettings($configuration->getSettings());
		}
	}

	public function overrideSettings(array $settings): void
	{
		Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->settings, $settings);
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

	/**
	 * @param bool $escapeHtml Must be true whenever the result is rendered as HTML (e.g. via f:format.html())
	 *                         without further escaping, so that submitted form values can't inject markup into
	 *                         admin-authored RTE content. Leave false for plain-text targets (subjects, addresses,
	 *                         database columns), where HTML-escaping would corrupt the stored/sent value.
	 */
	protected function parseWithValues(string $string, bool $escapeHtml = false): string
	{
		return Utility\TemplateVariableParser::parse($string, $this->getFormValues(), $escapeHtml);
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