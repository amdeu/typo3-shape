<?php

namespace Amdeu\Shape\Form\Module;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use Amdeu\Shape\Form;
use Amdeu\Shape\Form\Model;

#[Autoconfigure(public: false, shared: true)]
class ModuleInvoker
{
	protected array $formModules = [];

	public function __construct(
		protected readonly Core\EventDispatcher\EventDispatcher $eventDispatcher,
	)
	{
	}

	/**
	 * Initialize modules based on form configuration and conditions.
	 */
	public function initializeModules(Form\FormRuntime $runtime): void
	{
		$configurations = $runtime->form->getModuleConfigurations();
		$expressionResolver = $runtime->createExpressionResolver();
		$formModules = [];
		foreach ($configurations as $configuration) {

			// Evaluate module condition
			$conditionEvent = new ModuleConditionResolutionEvent(
				$runtime,
				$configuration,
				$expressionResolver
			);

			$this->eventDispatcher->dispatch($conditionEvent);
			if ($conditionEvent->isPropagationStopped()) {
				if ($conditionEvent->result === false) {
					continue;
				}
			} else if (
				$configuration->getCondition()
				&& !$expressionResolver->evaluate($configuration->getCondition())
			) {
				continue;
			}

			/** @var Model\ModuleConfigurationInterface $configuration */
			$module = Core\Utility\GeneralUtility::makeInstance($configuration->getModuleClassName());
			if (!$module instanceof ModuleInterface) {
				throw new \RuntimeException("Module class " . $configuration->getModuleClassName() . " must implement ModuleInterface");
			}

			/** @var ModuleInterface $module */
			$module->configure($runtime, $configuration);
			$formModules[] = [
				'configuration' => $configuration,
				'instance' => $module,
				'methodEventMap' => $this->buildMethodEventMap($module)
			];
		}
		$this->formModules[$runtime->form->getIdentifier()] = $formModules;
	}


	/**
	 * Build a map of event class names to module method names for the given module
	 * based on the AsModuleEventListener attributes and method parameter types.
	 */
	protected function buildMethodEventMap(ModuleInterface $module): array
	{
		$map = [];
		$reflection = new \ReflectionClass($module);
		foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			$attributes = $method->getAttributes(AsModuleEventListener::class);
			if (empty($attributes)) {
				continue;
			}
			$params = $method->getParameters();
			if (count($params) !== 1) {
				throw new \RuntimeException("Module event listener methods must have exactly one parameter");
			}
			$paramType = (string)$params[0]->getType();
			if (!class_exists($paramType)) {
				throw new \RuntimeException("Module event listener method parameter type must be an event class, got: " . $paramType);
			}
			if (!isset($map[$paramType])) {
				$map[$paramType] = [];
			}
			$map[$paramType][] = $method->getName();
		}
		return $map;
	}

	/**
	 * Handle an event by invoking the appropriate methods on all modules
	 * that have registered listeners for the event's class.
	 */
	protected function handleEvent(object $event): void
	{
		// check if event has runtime property
		$runtime = $event->runtime ?? null;
		if (!$runtime instanceof Form\FormRuntime) {
			return;
		}
		$modules = $this->formModules[$runtime->form->getIdentifier()] ?? [];
		$className = get_class($event);
		foreach ($modules as $moduleData) {
			if (method_exists($event, 'isCancelled') && $event->isCancelled()) {
				break;
			}
			$module = $moduleData['instance'];
			$map = $moduleData['methodEventMap'];
			if (isset($map[$className])) {

				foreach ($map[$className] as $methodName) {
					$module->$methodName($event);
				}
			}
		}
	}

	#[AsEventListener]
	public function onFormRuntimeCreation(Form\FormRuntimeCreationEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onFormCreation(Form\FormCreationEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onExpressionResolverCreation(Form\Condition\ExpressionResolverCreationEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onModuleConditionResolution(Form\Condition\ModuleConditionResolutionEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onBeforeFormRender(Form\Rendering\BeforeFormRenderEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onFieldConditionResolution(Form\Condition\FieldConditionResolutionEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onValueValidation(Form\Validation\ValueValidationEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onValueSerialization(Form\Serialization\ValueSerializationEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onValueProcessing(Form\Processing\ValueProcessingEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onSpamAnalysis(Form\SpamProtection\SpamAnalysisEvent $event): void
	{
		$this->handleEvent($event);
	}

	#[AsEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
	{
		$this->handleEvent($event);
	}
}