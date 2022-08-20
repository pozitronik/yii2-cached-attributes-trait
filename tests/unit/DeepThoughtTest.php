<?php
declare(strict_types = 1);

namespace app\models;

use Codeception\Test\Unit;
use pozitronik\cached_properties\CachedPropertiesTrait;
use Yii;
use yii\caching\FileCache;
use yii\helpers\Console;

/**
 * Class DeepThoughtTest
 */
class DeepThoughtTest extends Unit {
	/**
	 * @inheritDoc
	 */
	protected function _before():void {
		Yii::$app->set('cache', [
				'class' => FileCache::class,
			]
		);
		self::assertTrue(Yii::$app->cache->flush());
	}

	/**
	 * This test demonstrates the efficiency of properties caching
	 * @return void
	 */
	public function testDeepThought():void {
		/*at first, run without caching*/
		$dtModel = new DeepThought([
			'alpha' => 'cheese',
			'beta' => 'moon',
			'base' => 'infinity'
		]);
		$now = microtime(true);
		for ($i = 0; $i < 10; $i++) {
			Console::output(Console::renderColoredString(sprintf("%%G%s: %%R%s%%n", $i, $dtModel->AnswerToUltimateQuestionOfLifeUniverseAndEverything)));
		}
		$total = microtime(true) - $now;
		self::assertGreaterThan(7.5, $total);

		Console::output(Console::renderColoredString(sprintf("%%BTotal execution time: %s ms.%%n", $total)));

		/*now add some caching possibilities*/
		$dtModel = (new class extends DeepThought {
			use CachedPropertiesTrait;

			/**
			 * @inheritDoc
			 */
			public function cachedProperties():array {
				return [
					'AnswerToUltimateQuestionOfLifeUniverseAndEverything',
				];
			}
		});

		$dtModel->alpha = 'cheese';
		$dtModel->beta = 'moon';
		$dtModel->base = 'infinity';

		$now = microtime(true);
		for ($i = 0; $i < 10; $i++) {
			Console::output(Console::renderColoredString(sprintf("%%G%s: %%R%s%%n", $i, $dtModel->AnswerToUltimateQuestionOfLifeUniverseAndEverything)));
		}
		$total = microtime(true) - $now;
		self::assertGreaterThan(0.75, $total);//first execution time
		self::assertLessThan(7.5, $total);

		Console::output(Console::renderColoredString(sprintf("%%BTotal execution time: %s ms.%%n", $total)));
	}
}