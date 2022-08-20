# yii2-cached-properties-trait

Support to cache yii2 models properties

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/pozitronik/yii2-cached-properties-trait/CI%20with%20PostgreSQL)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run

```
php composer.phar require pozitronik/yii2-cached-properties-trait "^1.0.0"
```

or add

```
"pozitronik/yii2-cached-properties-trait": "^1.0.0"
```

to the require section of your `composer.json` file and run `composer.phar install`

Requirements
------------

PHP >= 8.0

What is it?
-----------

let's imagine a situation: there is a Yii2 model with some long-to-compute property:

```php
/**
 * @property ?int AnswerToUltimateQuestionOfLifeUniverseAndEverything
 */
class DeepThought extends \yii\base\Model {

	/**
	 * Warning: this method execution time take ~7.5 million years.
	 * @return int|null
	 */
	public function getAnswerToUltimateQuestionOfLifeUniverseAndEverything():?int {
		return $this->multiple(6, 9);//42
	}
}

```

Every time, when ```$deepThoughtObject->AnswerToUltimateQuestionOfLifeUniverseAndEverything``` called, we have
to wait again. Of course, property value can be saved to a temporary variable after first call, or cached (
which is more prefferable, because caching is caching, you know), so next calls will cost nothing.

Usually, add caching support to you code in Yii2 is not so hard. Let's assume something like:

```php
	/**
	 * Warning: the first execution of this method take ~7.5 million years
	 * @return int|null
	 */
	public function getAnswerToUltimateQuestionOfLifeUniverseAndEverything():?int {
		return Yii::$app->cache->getOrSet(
			'DeepThought::AnswerToUltimateQuestionOfLifeUniverseAndEverything',
			function() {
				return $this->multiple(6, 9);//42
			}
		);
	}
```

and it's OK. But what it that property have to recalculated somehow?

```php
/**
 * @property mixed AnswerToUltimateQuestionOfLifeUniverseAndEverything
 * @property-write mixed $alpha
 * @property-write mixed $beta
 * @property-write mixed $base
 */
class DeepThought extends \yii\base\Model {
	private mixed $_alpha = 6;
	private mixed $_beta = 9;
	private mixed $_base = 13;

	/**
	 * Warning: this method execution take... idk, honestly
	 * @return mixed
	 */
	public function getAnswerToUltimateQuestionOfLifeUniverseAndEverything():mixed {
		return $this->multiple($this->_alpha, $this->_beta, $this->_base);//???
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setAlpha(mixed $value):void {
		$this->_alpha = $value;
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setBeta(mixed $value):void {
		$this->_beta = $value;
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setBase(mixed $value):void {
		$this->_base = $value;
	}

}

(new DeepThought())->AnswerToUltimateQuestionOfLifeUniverseAndEverything;//ok, 42?
(new DeepThought([
	'alpha' => 'cheese',
	'beta' => 'moon',
	'base' => 'infinity'
]))->AnswerToUltimateQuestionOfLifeUniverseAndEverything;//???
```

You have to watch for right cache invalidations and write a lot of boring code. This trait tries to simplify
that task:

```php
/**
 * @property mixed AnswerToUltimateQuestionOfLifeUniverseAndEverything
 * @property-write mixed $alpha
 * @property-write mixed $beta
 * @property-write mixed $base
 */
class DeepThought extends \yii\base\Model {
	use CachedPropertiesTrait;
	
    private mixed $_alpha = 6;
	private mixed $_beta = 9;
	private mixed $_base = 13;

	/**
	 * @inheritDoc
	 */
	public function cachedProperties():array {
		return [
			/* property cached, cache invalidates, when alpha/beta/base values are changed */
			['AnswerToUltimateQuestionOfLifeUniverseAndEverything', ['alpha', 'beta', 'base']]
		];
	}

	/**
	 * Warning: this method execution take ~7.5 million years
	 * @return mixed
	 */
	public function getAnswerToUltimateQuestionOfLifeUniverseAndEverything():mixed {
		return $this->multiple($this->_alpha, $this->_beta, $this->_base);//???
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setAlpha(mixed $value):void {
		$this->_alpha = $value;
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setBeta(mixed $value):void {
		$this->_beta = $value;
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setBase(mixed $value):void {
		$this->_base = $value;
	}

}
```

So, that's an idea, see tests and code examples for more information.

License
-------

GNU GPL 3.0
