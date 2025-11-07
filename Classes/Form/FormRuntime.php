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

	public function findSpamReasons(): array
	{
		$event = new SpamProtection\SpamAnalysisEvent($this);
		$this->eventDispatcher->dispatch($event);
		$this->spamReasons = $event->spamReasons;
		return $this->spamReasons;
	}

	public function isRequestedPlugin(): bool
	{
		if (!isset($this->request->getArguments()['pluginUid'])) {
			return true;
		}
		$uid = $this->settings['pluginUid'] ?: $this->request->getAttribute('currentContentObject')?->data['uid'] ?? $this->request->getArguments()['pluginUid'] ?? null;
		return $this->request->getArguments()['pluginUid'] == $uid;
	}

	public function isFormPostRequest(): bool
	{
		return $this->request->getMethod() === 'POST' && array_key_exists($this->parsedBodyKey, $this->request->getParsedBody());
	}

	public function addMessages(array $messages): void
	{
		$this->messages = array_merge($this->messages, $messages);
	}

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

	public function serializeForm(): void
	{
		foreach ($this->form->getPages() as $index => $page) {
			$this->serializePage($index + 1);
		}
	}

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

	public function getFieldValue(Model\FieldInterface $field): mixed
	{
		return $this->session->values[$field->getName()] ?? null;
	}

	public function setFieldValue(Model\FieldInterface $field, mixed $value): void
	{
		$field->setSessionValue($value);
		$name = $field->getName();
		$this->session->values[$name] = $value;
		if (isset($this->session->values[$name . '__CONFIRM'])) {
			$this->session->values[$name . '__CONFIRM'] = $value;
		}
	}

	public function getHasErrors(): bool
	{
		return $this->hasErrors;
	}

	public function getSessionUploadFolder(): string
	{
		return explode(':', $this->settings['uploadFolder'])[1] . $this->session->getId() . '/';
	}
}