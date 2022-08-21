<?php
declare(strict_types = 1);

use app\models\RefUserPositions;
use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\cached_properties\CachedPropertiesTrait;
use pozitronik\cached_properties\CachedPropertyRule;
use yii\base\InvalidArgumentException;
use yii\caching\FileCache;
use yii\caching\TagDependency;
use yii\db\StaleObjectException;

/**
 * Class CachedPropertiesTraitTest
 */
class CachedPropertiesTraitTest extends Unit {

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
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @covers CachedPropertyRule::__construct
	 */
	public function testCachedAttributeRuleFromArray():void {
		$rule = new CachedPropertyRule('attribute_one');
		self::assertEquals(['attribute_one'], $rule->propertiesNames);
		self::assertEquals(['attribute_one'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['attribute_two', 'attribute_setter_two']);
		self::assertEquals(['attribute_two'], $rule->propertiesNames);
		self::assertEquals(['attribute_setter_two'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['attribute_three', 'attribute_four'], ['attribute_setter_three']]);
		self::assertEquals(['attribute_three', 'attribute_four'], $rule->propertiesNames);
		self::assertEquals(['attribute_setter_three'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['attribute_five', ['attribute_setter_four']]);
		self::assertEquals(['attribute_five'], $rule->propertiesNames);
		self::assertEquals(['attribute_setter_four'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['attribute_six'], 'attribute_setter_five']);
		self::assertEquals(['attribute_six'], $rule->propertiesNames);
		self::assertEquals(['attribute_setter_five'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['attribute_seven', 'attribute_eight'], ['attribute_setter_six', 'attribute_setter_seven', 'attribute_setter_eight']]);
		self::assertEquals(['attribute_seven', 'attribute_eight'], $rule->propertiesNames);
		self::assertEquals(['attribute_setter_six', 'attribute_setter_seven', 'attribute_setter_eight'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['attribute_nine', 'attribute_ten'], null, ['tag_one', 'tag_two']]);
		self::assertEquals(['attribute_nine', 'attribute_ten'], $rule->propertiesNames);
		self::assertEquals(['attribute_nine', 'attribute_ten'], $rule->setterPropertiesNames);
		self::assertEquals(['tag_one', 'tag_two'], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['attribute_eleven', true]);
		self::assertEquals(['attribute_eleven'], $rule->propertiesNames);
		self::assertEquals([true], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['attribute_twelve', false]);
		self::assertEquals(['attribute_twelve'], $rule->propertiesNames);
		self::assertEquals([], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(true);
		self::assertEquals([true], $rule->propertiesNames);
		self::assertEquals([true], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([true, false]);
		self::assertEquals([true], $rule->propertiesNames);
		self::assertEquals([], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([true, false, 'tag_three']);
		self::assertEquals([true], $rule->propertiesNames);
		self::assertEquals([], $rule->setterPropertiesNames);
		self::assertEquals(['tag_three'], $rule->propertiesTags);

		$this->expectExceptionObject(new InvalidArgumentException('Wrong rule set passed to constructor.'));
		new CachedPropertyRule([null]);
		self::assertNull(new CachedPropertyRule([[]]));
		self::assertNull(new CachedPropertyRule([[null]]));
	}

	/**
	 * @return void
	 * @throws Throwable
	 * @covers CachedPropertyRule::isGetterAcceptable
	 * @covers CachedPropertyRule::isSetterAcceptable
	 */
	public function testCachedAttributeRule():void {
		$rule = new CachedPropertyRule('attribute_one');
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertTrue($rule->isSetterAcceptable('attribute_one'));
		self::assertFalse($rule->isSetterAcceptable('any'));

		$rule = new CachedPropertyRule([['attribute_one', 'attribute_two'], ['attribute_setter_one', 'attribute_setter_two', 'attribute_setter_three']]);
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertTrue($rule->isGetterAcceptable('attribute_two'));
		self::assertTrue($rule->isSetterAcceptable('attribute_setter_one'));
		self::assertTrue($rule->isSetterAcceptable('attribute_setter_two'));
		self::assertTrue($rule->isSetterAcceptable('attribute_setter_three'));

		$rule = new CachedPropertyRule(['attribute_one', true]);
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertTrue($rule->isSetterAcceptable('attribute_one'));
		self::assertTrue($rule->isSetterAcceptable('any'));
		self::assertTrue($rule->isSetterAcceptable('whatever'));

		$rule = new CachedPropertyRule(['attribute_one', false]);
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertFalse($rule->isSetterAcceptable('attribute_one'));
		self::assertFalse($rule->isSetterAcceptable('any'));

		$rule = new CachedPropertyRule(['attribute_one', null]);
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertTrue($rule->isSetterAcceptable('attribute_one'));
		self::assertFalse($rule->isSetterAcceptable('any'));

		$rule = new CachedPropertyRule(true);
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertTrue($rule->isGetterAcceptable('attribute_two'));
		self::assertTrue($rule->isGetterAcceptable('any'));
		self::assertTrue($rule->isSetterAcceptable('attribute_one'));
		self::assertTrue($rule->isSetterAcceptable('attribute_two'));
		self::assertTrue($rule->isSetterAcceptable('whatever'));

		$rule = new CachedPropertyRule([true, false]);
		self::assertTrue($rule->isGetterAcceptable('attribute_one'));
		self::assertTrue($rule->isGetterAcceptable('attribute_two'));
		self::assertTrue($rule->isGetterAcceptable('any'));
		self::assertFalse($rule->isSetterAcceptable('attribute_one'));
		self::assertFalse($rule->isSetterAcceptable('attribute_two'));
		self::assertFalse($rule->isSetterAcceptable('whatever'));
	}

	/**
	 * @return void
	 * @throws StaleObjectException
	 * @covers CachedPropertiesTrait::__get
	 * @covers CachedPropertiesTrait::__set
	 */
	public function testCachedProperties():void {
		$managePosition = RefUserPositions::getRecord(1);
		$CEOPosition = RefUserPositions::getRecord(2);

		$user = (new class extends Users {
			use CachedPropertiesTrait;

			/**
			 * @inheritDoc
			 */
			public function cachedProperties():array {
				return [
					['refUserPositions', 'position_id', 'RefUserPositionsTagOne'],
				];
			}
		});

		self::assertNotNull($user->id);

		self::assertFalse($user->isPropertyCached('refUserPositions'));

		/** null in cache */
		self::assertNull($user->refUserPositions);
		self::assertTrue($user->isPropertyCached('refUserPositions'));

		/* Setting position_id property clears refUserPositions property cache */
		$user->position_id = $managePosition->id;

		self::assertFalse($user->isPropertyCached('refUserPositions'));

		self::assertEquals($user->refUserPositions?->name, $managePosition->name);

		$user->position_id = $CEOPosition->id;

		self::assertEquals($user->refUserPositions?->name, $CEOPosition->name);
		self::assertTrue($user->isPropertyCached('refUserPositions'));

		/*There's a tag attached to property, so it's invalidation should clear property cache*/
		TagDependency::invalidate(Yii::$app->cache, 'RefUserPositionsTagOne');
		self::assertFalse($user->isPropertyCached('refUserPositions'));

	}
}