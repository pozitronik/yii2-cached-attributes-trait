<?php
/** @noinspection PhpInappropriateInheritDocUsageInspection */
/** @noinspection PhpUndefinedClassInspection */
declare(strict_types = 1);

namespace pozitronik\cached_properties;

use pozitronik\helpers\CacheHelper;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;
use Yii;
use yii\caching\TagDependency;

/**
 * Trait CachedPropertiesTrait
 */
trait CachedPropertiesTrait {
	/** @var CachedPropertyRule[] $rules */
	protected ?array $rules = null;

	/**
	 * @return void
	 * @throws Throwable
	 */
	private function initRules():void {
		foreach ($this->cachedProperties() as $propertyRule) {
			$this->rules[] = CachedPropertyRule::fromRule($propertyRule);
		}
	}

	/**
	 * @param string $propertyName
	 * @param bool $get
	 * @return CachedPropertyRule|null
	 * @throws Throwable
	 */
	private function hasCachingRule(string $propertyName, bool $get = true):?CachedPropertyRule {
		if (null === $this->rules) $this->initRules();
		foreach ($this->rules as $rule) {
			if ($get
				?$rule->isGetterAcceptable($propertyName)
				:$rule->isSetterAcceptable($propertyName)
			) return $rule;
		}
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function __get($name):mixed {
		if (null !== $rule = $this->hasCachingRule($name)) {
			$cacheKey = $this->getPropertyCacheKey($name);
			return Yii::$app->cache->getOrSet($cacheKey, fn() => parent::__get($name), null, new TagDependency([
					'tags' => array_merge([$cacheKey], $rule->getPropertiesTags())
				])
			);
		}
		return parent::__get($name);
	}

	/**
	 * @inheritDoc
	 */
	public function __set($name, $value):void {
		parent::__set($name, $value);
		if (null !== $rule = $this->hasCachingRule($name, false)) {
			foreach ($rule->propertiesNames as $cachedPropertiesName) {
				TagDependency::invalidate(Yii::$app->cache, $this->getPropertyCacheKey($cachedPropertiesName));
			}
		}
	}

	/**
	 * @see Component::__isset()
	 * @inheritDoc
	 */
	public function __isset($name) {
		return parent::__isset($name);
	}

	/**
	 * Format:
	 * [
	 *    'propertyName',//or
	 *    ['Properties', 'setters', 'dependencyTags']//each element can be an string array
	 * ]
	 *
	 * @return array
	 */
	public function cachedProperties():array {
		return [];
	}

	/**
	 * Возвращает ключ в кеше для имени атрибута
	 * @param string $propertyName
	 * @return string
	 */
	protected function getPropertyCacheKey(string $propertyName):string {
		return CacheHelper::ObjectSignature($this, [
			'cachedProperties',
			'name' => $propertyName
		]);
	}

	/**
	 * Кеширован ли атрибут
	 * Проверять нужно через get, потому что exists не реагирует на инвалидацию тегов
	 * @param string $propertyName
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isPropertyCached(string $propertyName):bool {
		return false !== Yii::$app->cache->get($this->getPropertyCacheKey($propertyName));
	}
}