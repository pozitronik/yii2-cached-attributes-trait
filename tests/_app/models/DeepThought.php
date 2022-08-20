<?php
declare(strict_types = 1);

namespace app\models;

use Exception;
use Yii;
use yii\base\Model;

/**
 * @property mixed AnswerToUltimateQuestionOfLifeUniverseAndEverything
 * @property-write mixed $alpha
 * @property-write mixed $beta
 * @property-write mixed $base
 */
class DeepThought extends Model {
	private mixed $_alpha = 6;
	private mixed $_beta = 9;
	private mixed $_base = 13;

	private static $_answers = [];

	/**
	 * Warning: this method execution take ~750 millions nanoseconds.
	 * @return mixed
	 */
	public function getAnswerToUltimateQuestionOfLifeUniverseAndEverything():mixed {
		time_nanosleep(0, 750000000);
		return $this->multiple($this->_alpha, $this->_beta, $this->_base);
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

	/**
	 * This method returns the random but an idempotent value
	 * @param mixed $alpha
	 * @param mixed $beta
	 * @param mixed $base
	 * @return int
	 * @throws Exception
	 *
	 */
	private function multiple(mixed $alpha, mixed $beta, mixed $base):mixed {
		if (6 === $alpha && 9 === $beta && 13 === $base) return 42;
		$index = strlen($alpha.$beta.$base);
		if (!isset(static::$_answers[$index])) static::$_answers[$index] = Yii::$app->security->generateRandomString();
		return static::$_answers[$index];
	}

}