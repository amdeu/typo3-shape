<?php

namespace Amdeu\Shape\Form;

use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;

class FormRuntime
{
	public function __construct(
		readonly protected Core\EventDispatcher\EventDispatcher $eventDispatcher,
		readonly protected Core\Service\FlexFormService         $flexFormService,
		readonly protected Condition\FieldConditionResolver     $fieldConditionResolver,
		readonly protected Processing\FieldValueProcessor       $fieldValueProcessor,
		readonly protected Serialization\FieldValueSerializer   $fieldValueSerializer,
		readonly protected Validation\FieldValueValidator       $fieldValueValidator,
		readonly public Extbase\Mvc\RequestInterface			$request,
		readonly public array                                   $settings,
		readonly public Core\View\ViewInterface                 $view,
		readonly public Core\Domain\Record                      $plugin,
		readonly public Model\FormInterface                     $form,
		readonly public FormSession                             $session,
		readonly public array                                   $postValues,
		readonly public Core\Resource\ResourceStorageInterface  $uploadStorage,
		readonly public string                                  $parsedBodyKey,
		readonly public bool                                    $isStepBack = false,
		protected ?array                                        $spamReasons = null,
		protected array                                         $messages = [],
		protected bool                                          $hasErrors = false,
	)
	{
		$this->setFieldSessionValues();
		$event = new FormRuntimeCreationEvent($this);
		$this->eventDispatcher->dispatch($event);
	}

	/**
	 * Sets the session values of all form fields from the form session
	 */
	public function setFieldSessionValues(): self
	{
		foreach ($this->form->getPages() as $page) {
			foreach ($page->getFields() as $field) {
				if ($field->isFormControl()) {
					$field->setSessionValue($this->session->values[$field->getName()] ?? null);
				}
			}
		}
		return $this;
	}

	/**
	 * Analyzes the form submission for spam indicators by dispatching a SpamAnalysisEvent
	 */
	public function findSpamReasons(): array
	{
		$event = new SpamProtection\SpamAnalysisEvent($this);
		$this->eventDispatcher->dispatch($event);
		$this->spamReasons = $event->spamReasons;
		return $this->spamReasons;
	}

	/**
	 * Checks if the current request is intended for this plugin instance; necessary in case of multiple plugins on one page
	 */
	public function isRequestedPlugin(): bool
	{
		if (!isset($this->request->getArguments()['pluginUid'])) {
			return true;
		}
		$uid = $this->settings['pluginUid'] ?: $this->request->getAttribute('currentContentObject')?->data['uid'] ?? $this->request->getArguments()['pluginUid'] ?? null;
		return $this->request->getArguments()['pluginUid'] == $uid;
	}

	/**
	 * Checks if the current request is a form POST request for this form
	 */
	public function isFormPostRequest(): bool
	{
		return $this->request->getMethod() === 'POST' && array_key_exists($this->parsedBodyKey, $this->request->getParsedBody());
	}

	/**
	 * Adds messages to be displayed on the form page
	 */
	public function addMessages(array $messages): void
	{
		$this->messages = array_merge($this->messages, $messages);
	}

	/**
	 * Renders the form page for the given page index
	 * Resolves field display conditions before rendering
	 */
	public function renderPage(int $pageIndex = 1): string
	{
		$pages = $this->form->getPages();
		$lastPageIndex = count($pages);
		$currentPageRecord = $pages[$pageIndex - 1];
		$this->session->returnPageIndex = $pageIndex;

		// Resolve display conditions with "stepType" of page to be rendered
		$expressionResolver = $this->createExpressionResolver(['stepType' => $currentPageRecord->get('type')]);
		foreach ($pages as $page) {
			foreach ($page->getFields() as $field) {
				$field->setConditionResult($this->fieldConditionResolver->evaluate($this, $field, $expressionResolver));
			}
		}

		$viewVariables = [
			'session' => $this->session,
			'serializedSession' => FormSession::serialize($this->session),
			'namespace' => $this->form->getName(),
			'action' => 'run',
			'plugin' => $this->plugin,
			'form' => $this->form,
			'settings' => $this->settings,
			'messages' => $this->messages,
			'spamReasons' => $this->spamReasons,
			'currentPage' => $currentPageRecord,
			'pageIndex' => $pageIndex,
			'isFirstPage' => $pageIndex === 1,
			'isLastPage' => $pageIndex === $lastPageIndex,
			'backStepPageIndex' => $pageIndex - 1 ?: null,
			'forwardStepPageIndex' => $lastPageIndex === $pageIndex ? null : $pageIndex + 1,
		];

		$event = new Rendering\BeforeFormRenderEvent($this, $viewVariables);
		$this->eventDispatcher->dispatch($event);
		$viewVariables = $event->getVariables();

		$this->view->assignMultiple($viewVariables);
		return $this->view->render('Form');
	}

	/**
	 * Validates all fields on the given page index
	 */
	public function validatePage(int $pageIndex): void
	{
		$page = $this->form->getPages()[$pageIndex - 1] ?? null;
		if (!$page || !$page->has('fields')) {
			return;
		}

		// Resolve display conditions with "stepType" of the page fields are on, necessary before validation for required fields
		$expressionResolver = $this->createExpressionResolver(['stepType' => $page->get('type')]);
		foreach ($page->getFields() as $field) {
			$field->setConditionResult($this->fieldConditionResolver->evaluate($this, $field, $expressionResolver));
			$field->setValidationResult($this->fieldValueValidator->validate($this, $field, $this->getFieldValue($field)));
			if ($field->getValidationResult()->hasErrors()) {
				$this->hasErrors = true;
			}
		}
	}

	/**
	 * Serializes all field values on the given page index
	 */
	public function serializePage(int $pageIndex): void
	{
		$page = $this->form->getPages()[$pageIndex - 1] ?? null;
		if (!$page || !$page->has('fields')) {
			return;
		}
		foreach ($page->getFields() as $field) {
			if (!$field->isFormControl()) {
				continue;
			}
			$serializedValue = $this->fieldValueSerializer->serialize($this, $field, $this->getFieldValue($field));
			$this->setFieldValue($field, $serializedValue);
		}
	}

	/**
	 * Validates all pages and their fields in the form
	 * Stops at the first page with validation errors
	 */
	public function validateForm(): void
	{
		foreach ($this->form->getPages() as $index => $page) {
			$pageIndex = $index + 1;
			$this->validatePage($pageIndex);
			if ($this->hasErrors) {
				$this->session->returnPageIndex = $pageIndex;
				break;
			}
		}
	}

	/**
	 * Serializes all pages and their fields in the form
	 */
	public function serializeForm(): void
	{
		foreach ($this->form->getPages() as $index => $page) {
			$this->serializePage($index + 1);
		}
	}

	/**
	 * Processes all field values in the form
	 */
	public function processForm(): void
	{
		foreach ($this->form->getPages() as $page) {
			foreach ($page->getFields() as $field) {
				if (!$field->isFormControl()) {
					continue;
				}
				$processedValue = $this->fieldValueProcessor->process($this, $field, $this->getFieldValue($field));
				$this->setFieldValue($field, $processedValue);
			}
		}
	}

	/**
	 * Executes finishers configured for the form
	 * Finishers are executed in the order they are defined
	 * Considers finisher conditions and calls finisher validation which can prevent execution if errors occur
	 * Finishers can also cancel further execution of subsequent finishers
	 */
	public function finishForm(array $conditionVariables = []): Finisher\FinisherExecutionContext
	{
		$context = new Finisher\FinisherExecutionContext($this);
		$expressionResolver = $this->createExpressionResolver($conditionVariables);

		$executableFinishers = [];
		foreach ($this->form->getFinisherConfigurations() as $configuration) {

			$conditionEvent = new Condition\FinisherConditionResolutionEvent(
				$this,
				$configuration,
				$expressionResolver
			);
			$this->eventDispatcher->dispatch($conditionEvent);
			if ($conditionEvent->isPropagationStopped()) {
				if ($conditionEvent->result === false) {
					continue;
				}
			} else if ($configuration->getCondition() && !$expressionResolver->evaluate($configuration->getCondition())) {
				continue;
			}

			$finisher = $this->createFinisherInstance($configuration);

			// todo: add finisher validation event?
			$validationResult = $finisher->validate();

			if ($validationResult->hasErrors()) {
				$this->hasErrors = true;

				// todo: rework messages to use message objects instead of arrays
				$this->addMessages(
					array_map(
						function (Extbase\Validation\Error $error) {
							return ['message' => $error->getMessage(), 'type' => 'error'];
						},
						$validationResult->getErrors()
					)
				);
				return $context;
			}
			$executableFinishers[] = $finisher;
		}

		foreach ($executableFinishers as $finisher) {
			$finisher->execute($context);
			if ($context->isCancelled()) {
				break;
			}
		}

		$context->finishedActionArguments['pluginUid'] = $this->plugin->getUid();
		return $context;
	}

	/**
	 * Creates an instance of a finisher based on the given configuration
	 */
	public function createFinisherInstance(Model\FinisherConfigurationInterface $configuration): Finisher\FinisherInterface
	{
		// todo: maybe add "finisherDefaults". Problem is there's no good way to merge. ArrayUtility::mergeRecursiveWithOverrule either overwrites everything or discards empty values ('' and '0'), but we want to keep '0', otherwise checkboxes can't overwrite with false. Extbase has "ignoreFlexFormSettingsIfEmpty" but that doesn't really solve the problem either. To have booleans with default values, we'd need to render them as selects with values '', '0', '1' and then only ignore ''.
		$event = new Finisher\BeforeFinisherCreationEvent(
			$this,
			$configuration,
			$configuration->getFinisherClassName(),
			$configuration->getSettings()
		);
		$this->eventDispatcher->dispatch($event);
		$finisher = Core\Utility\GeneralUtility::makeInstance($event->finisherClassName);
		if (!($finisher instanceof Finisher\FinisherInterface)) {
			throw new \InvalidArgumentException('Argument "finisherClassName" must the name of a class that implements Amdeu\Shape\Form\Finisher\FinisherInterface.', 1741369249);
		}
		$finisher->setSettings($event->settings);
		return $finisher;
	}

	/**
	 * Creates an expression resolver with the given additional variables
	 */
	public function createExpressionResolver(array $variables): Core\ExpressionLanguage\Resolver
	{
		$variables = array_merge([
			'formRuntime' => $this,
			'formValues' => $this->session->values,
			'request' => new Core\ExpressionLanguage\RequestWrapper($this->request),
			'site' => $this->request->getAttribute('site'),
			'frontendUser' => $this->request->getAttribute('frontend.user'),
		], $variables);
		$event = new Condition\ExpressionResolverCreationEvent($this, $variables);
		$this->eventDispatcher->dispatch($event);
		return Core\Utility\GeneralUtility::makeInstance(
			Core\ExpressionLanguage\Resolver::class,
			'tx_shape', $event->getVariables()
		);
	}

	/**
	 * Gets the value of a field from the form session
	 */
	public function getFieldValue(Model\FieldInterface $field): mixed
	{
		return $this->session->values[$field->getName()] ?? null;
	}

	/**
	 * Sets the value of a field in the form session
	 */
	public function setFieldValue(Model\FieldInterface $field, mixed $value): void
	{
		$field->setSessionValue($value);
		$name = $field->getName();
		$this->session->values[$name] = $value;
		if (isset($this->session->values[$name . '__CONFIRM'])) {
			$this->session->values[$name . '__CONFIRM'] = $value;
		}
	}

	/**
	 * Returns whether the form runtime has validation errors
	 */
	public function getHasErrors(): bool
	{
		return $this->hasErrors;
	}

	/**
	 * Returns the upload folder path for the current session
	 */
	public function getSessionUploadFolder(): string
	{
		return explode(':', $this->settings['uploadFolder'])[1] . $this->session->getId() . '/';
	}
}