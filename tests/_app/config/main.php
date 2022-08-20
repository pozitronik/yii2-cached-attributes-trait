<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

use yii\caching\DummyCache;

return [
	'id' => 'basic',
	'basePath' => dirname(__DIR__),
	'bootstrap' => ['log'],
	'aliases' => [
		'@vendor' => './vendor',
		'@tests' => './tests'
	],
	'components' => [
		'request' => [
			'cookieValidationKey' => 'sosijopu',
		],
		'cache' => [
			'class' => DummyCache::class,
		],
	],
];