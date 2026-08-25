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
	 * Keyed by plugin UID so the same form record on multiple plugins on one page works correctly.
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
		$this->formModules[(string)$runtime->plugin->getUid()] = $formModules;
	}


	/**
	 * Build a map of event class names to module method names.
	 * Cached per class since the map depends only on the class definition, not the instance.
	 */
	private static array $methodEventMapCache = [];

	protected function buildMethodEventMap(ModuleInterface $module): array
	{
		$className = get_class($module);
		if (isset(self::$methodEventMapCache[$className])) {
			return self::$methodEventMapCache[$className];
		}

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
			$map[$paramType][] = $method->getName();
		}

		return self::$methodEventMapCache[$className] = $map;
	}

	/**
	 * Routes a form event to all module methods that declared #[AsModuleEventListener] for it.
	 *
	 * Every PSR-14 form event that modules should be able to react to must have a corresponding
	 * #[AsEventListener] method below that calls this method. There is no automatic discovery —
	 * new form events must be wired up explicitly.
	 *
	 * Supported events: FormRuntimeCreationEvent, ExpressionResolverCreationEvent,
	 * ModuleConditionResolutionEvent, BeforeFormRenderEvent, FieldConditionResolutionEvent,
	 * ValueValidationEvent, ValueSerializationEvent, ValueProcessingEvent,
	 * SpamAnalysisEvent, FormFinishEvent
	 */
	protected function handleEvent(Form\FormEventInterface $event): void
	{
		$runtime = $event->getRuntime();
		$modules = $this->formModules[(string)$runtime->plugin->getUid()] ?? [];
		$eventClassName = get_class($event);
		foreach ($modules as $moduleData) {
			if ($event instanceof Form\FormFinishEvent && $event->isPropagationStopped()) {
				break;
			}
			$module = $moduleData['instance'];
			$map = $moduleData['methodEventMap'];
			if (isset($map[$eventClassName])) {
				foreach ($map[$eventClassName] as $methodName) {
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
	public function onModuleConditionResolution(ModuleConditionResolutionEvent $event): void
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
