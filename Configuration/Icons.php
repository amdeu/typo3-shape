<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
	'shape-form' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:shape/Resources/Public/Icons/shape-form.svg',
	],
	'shape-folder' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:shape/Resources/Public/Icons/shape-folder.svg',
	],
	'shape-default' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:shape/Resources/Public/Icons/default.svg',
	],
	'shape-form-option' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:shape/Resources/Public/Icons/form-option.svg',
	],
	'shape-module-default' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:shape/Resources/Public/Icons/module-default.svg',
	],
];