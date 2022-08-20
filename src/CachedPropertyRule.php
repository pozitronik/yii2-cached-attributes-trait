<?php
declare(strict_types = 1);

namespace pozitronik\cached_properties;

use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\base\Component;

/**
 * Class CachedAttributeRule
 * @property string|string[]|bool $propertiesNames
 * @property string|string[]|bool|null $setterPropertiesNames
 * @property string|string[] $propertiesTags
 */
class CachedPropertyRule extends Component {

	private string|array|bool $_propertiesNames;
	private string|array|bool|null $_setterPropertiesNames = [];
	private string|array $_propertiesTags = [];

	/**
	 * @param string $attributeName
	 * @return bool
	 */
	public function isGetterAcceptable(string $attributeName):bool {
		return in_array($attributeName, $this->_propertiesNames, true) || in_array(true, $this->_propertiesNames, true);
	}

	/**
	 * @param string $attributeName
	 * @return bool
	 */
	public function isSetterAcceptable(string $attributeName):bool {
		return in_array($attributeName, $this->_setterPropertiesNames, true) || in_array(true, $this->_setterPropertiesNames, true);
	}

	/**
	 * Парсит правило кеширования для атрибута
	 * @param string|array|bool $cachedAttributeRule
	 * @return static|null Объект правила, null при ошибке/недопустимой конфигурации
	 * @throws Throwable
	 */
	public static function fromRule(string|array|bool $cachedAttributeRule):?static {
		$rule = new static();
		if (is_array($cachedAttributeRule)) {
			if (empty($attributeName = array_filter((array)ArrayHelper::getValue($cachedAttributeRule, 0)))) return null;
			$rule->_propertiesNames = $attributeName;
			$rule->_setterPropertiesNames = match ($rule->_setterPropertiesNames = ArrayHelper::getValue($cachedAttributeRule, 1, [])) {
				null => $rule->_propertiesNames,
				false => [],
				default => (array)$rule->_setterPropertiesNames
			};
			$rule->_propertiesTags = (array)ArrayHelper::getValue($cachedAttributeRule, 2, []);
		} else {
			$rule->_propertiesNames = (array)$cachedAttributeRule;
			$rule->_setterPropertiesNames = (array)$cachedAttributeRule;
		}
		return $rule;
	}

	/**
	 * @return string[]
	 */
	public function getPropertiesNames():array {
		return $this->_propertiesNames;
	}

	/**
	 * @param string[]|bool|string $propertiesNames
	 */
	public function setPropertiesNames(bool|array|string $propertiesNames):void {
		$this->_propertiesNames = (array)$propertiesNames;
	}

	/**
	 * @return string[]
	 */
	public function getSetterPropertiesNames():array {
		return $this->_setterPropertiesNames;
	}

	/**
	 * @param string[]|bool|string|null $setterPropertiesNames
	 */
	public function setSetterPropertiesNames(bool|array|string|null $setterPropertiesNames):void {
		$this->_setterPropertiesNames = $setterPropertiesNames;
	}

	/**
	 * @return string[]
	 */
	public function getPropertiesTags():array {
		return $this->_propertiesTags;
	}

	/**
	 * @param string[]|string $_propertiesTags
	 */
	public function setPropertiesTags(array|string $_propertiesTags):void {
		$this->_propertiesTags = $_propertiesTags;
	}

}