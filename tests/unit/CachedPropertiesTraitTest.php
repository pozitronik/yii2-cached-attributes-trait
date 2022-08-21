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
	public function testCachedpropertyRuleFromArray():void {
		$rule = new CachedPropertyRule('property_one');
		self::assertEquals(['property_one'], $rule->propertiesNames);
		self::assertEquals(['property_one'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['property_two', 'property_setter_two']);
		self::assertEquals(['property_two'], $rule->propertiesNames);
		self::assertEquals(['property_setter_two'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['property_three', 'property_four'], ['property_setter_three']]);
		self::assertEquals(['property_three', 'property_four'], $rule->propertiesNames);
		self::assertEquals(['property_setter_three'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['property_five', ['property_setter_four']]);
		self::assertEquals(['property_five'], $rule->propertiesNames);
		self::assertEquals(['property_setter_four'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['property_six'], 'property_setter_five']);
		self::assertEquals(['property_six'], $rule->propertiesNames);
		self::assertEquals(['property_setter_five'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['property_seven', 'property_eight'], ['property_setter_six', 'property_setter_seven', 'property_setter_eight']]);
		self::assertEquals(['property_seven', 'property_eight'], $rule->propertiesNames);
		self::assertEquals(['property_setter_six', 'property_setter_seven', 'property_setter_eight'], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule([['property_nine', 'property_ten'], null, ['tag_one', 'tag_two']]);
		self::assertEquals(['property_nine', 'property_ten'], $rule->propertiesNames);
		self::assertEquals(['property_nine', 'property_ten'], $rule->setterPropertiesNames);
		self::assertEquals(['tag_one', 'tag_two'], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['property_eleven', true]);
		self::assertEquals(['property_eleven'], $rule->propertiesNames);
		self::assertEquals([true], $rule->setterPropertiesNames);
		self::assertEquals([], $rule->propertiesTags);

		$rule = new CachedPropertyRule(['property_twelve', false]);
		self::assertEquals(['property_twelve'], $rule->propertiesNames);
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

		$rule = new CachedPropertyRule([
			'propertiesNames' => ['property_thirteen', 'property_fourteen'],
			'setterPropertiesNames' => ['property_setter_nine', 'property_setter_ten'],
			'propertiesTags' => ['tag_four', 'tag_five']
		]);
		self::assertEquals(['property_thirteen', 'property_fourteen'], $rule->propertiesNames);
		self::assertEquals(['property_setter_nine', 'property_setter_ten'], $rule->setterPropertiesNames);
		self::assertEquals(['tag_four', 'tag_five'], $rule->propertiesTags);


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
	public function testCachedPropertyRule():void {
		$rule = new CachedPropertyRule('property_one');
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertTrue($rule->isSetterAcceptable('property_one'));
		self::assertFalse($rule->isSetterAcceptable('any'));

		$rule = new CachedPropertyRule([['property_one', 'property_two'], ['property_setter_one', 'property_setter_two', 'property_setter_three']]);
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertTrue($rule->isGetterAcceptable('property_two'));
		self::assertTrue($rule->isSetterAcceptable('property_setter_one'));
		self::assertTrue($rule->isSetterAcceptable('property_setter_two'));
		self::assertTrue($rule->isSetterAcceptable('property_setter_three'));

		$rule = new CachedPropertyRule(['property_one', true]);
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertTrue($rule->isSetterAcceptable('property_one'));
		self::assertTrue($rule->isSetterAcceptable('any'));
		self::assertTrue($rule->isSetterAcceptable('whatever'));

		$rule = new CachedPropertyRule(['property_one', false]);
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertFalse($rule->isSetterAcceptable('property_one'));
		self::assertFalse($rule->isSetterAcceptable('any'));

		$rule = new CachedPropertyRule(['property_one', null]);
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertTrue($rule->isSetterAcceptable('property_one'));
		self::assertFalse($rule->isSetterAcceptable('any'));

		$rule = new CachedPropertyRule(true);
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertTrue($rule->isGetterAcceptable('property_two'));
		self::assertTrue($rule->isGetterAcceptable('any'));
		self::assertTrue($rule->isSetterAcceptable('property_one'));
		self::assertTrue($rule->isSetterAcceptable('property_two'));
		self::assertTrue($rule->isSetterAcceptable('whatever'));

		$rule = new CachedPropertyRule([true, false]);
		self::assertTrue($rule->isGetterAcceptable('property_one'));
		self::assertTrue($rule->isGetterAcceptable('property_two'));
		self::assertTrue($rule->isGetterAcceptable('any'));
		self::assertFalse($rule->isSetterAcceptable('property_one'));
		self::assertFalse($rule->isSetterAcceptable('property_two'));
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

	/**
	 * @return void
	 * @throws StaleObjectException
	 * @covers CachedPropertiesTrait::__get
	 * @covers CachedPropertiesTrait::__set
	 */
	public function testCachedPropertiesRule():void {
		$managePosition = RefUserPositions::getRecord(1);
		$CEOPosition = RefUserPositions::getRecord(2);

		$user = (new class extends Users {
			use CachedPropertiesTrait;

			/**
			 * @inheritDoc
			 */
			public function cachedProperties():array {
				return [
					new CachedPropertyRule([
						'propertiesNames' => 'refUserPositions',
						'setterPropertiesNames' => 'position_id',
						'propertiesTags' => 'RefUserPositionsTagOne'
					])
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