<?php

declare(strict_types=1);

namespace Amdeu\Shape\Backend;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

final readonly class FlexformConfigurationModifier
{
	#[AsEventListener]
	public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
	{
		$identifier = $event->getIdentifier();
		if (
			$identifier['type'] === 'tca'
			&& $identifier['tableName'] === 'tx_shape_finisher'
			&& $identifier['dataStructureKey'] === 'Amdeu\Shape\Form\Finisher\SendEmailFinisher'
		) {
			$dataStructure = $event->getDataStructure();
			$dataStructure['sheets']['mail']['ROOT']['el']['template']['config']['items'] = [];
			$mailTemplates = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape']['finishers']['sendEmail']['templates'];
			foreach ($mailTemplates as $template => $config) {
				$dataStructure['sheets']['mail']['ROOT']['el']['template']['config']['items'][] = [
					'label' => $config['label'],
					'value' => $template,
					'group' => $config['group'] ?? null,
					'icon' => $config['icon'] ?? null,
				];
				if ($config['fields'] ?? false) {
					foreach ($config['fields'] as $field => $fieldConfig) {
						if (!isset($dataStructure['sheets']['template'])) {
							$dataStructure['sheets']['template'] = [
								'ROOT' => [
									'sheetTitle' => 'LLL:EXT:shape/Resources/Private/Language/locallang_db.xlf:tab.template',
									'type' => 'array',
									'el' => [],
								],
							];
						}
						$fieldConfig['displayCond'] = 'FIELD:mail.template:=:' . $template;
						$dataStructure['sheets']['template']['ROOT']['el'][$field] = $fieldConfig;
					}
				}
			}
			$event->setDataStructure($dataStructure);
		}

	}
}