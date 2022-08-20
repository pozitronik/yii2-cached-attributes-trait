<?php
declare(strict_types = 1);

namespace app\models;

use yii\base\Model;

/**
 * @property int $id
 * @property string $username Отображаемое имя пользователя
 * @property ?int $position_id
 *
 * @property-read null|RefUserPositions $refUserPositions
 */
class Users extends Model {
	private ?int $_id = 1;
	private ?int $_position_id = null;

	public function __construct(int $id = 1) {
		parent::__construct(['id' => $id]);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return 'users';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['id', 'position_id'], 'int'],
			[['username'], 'string'],
			[['id', 'username'], 'required']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'username' => 'Имя пользователя',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getId() {
		return $this->_id;
	}

	/**
	 * @param int|null $id
	 */
	public function setId(?int $id):void {
		$this->_id = $id;
	}

	/**
	 * @return int|null
	 */
	public function getPosition_id():?int {
		return $this->_position_id;
	}

	/**
	 * @param int|null $position_id
	 */
	public function setPosition_id(?int $position_id):void {
		$this->_position_id = $position_id;
	}

	/**
	 * @return null|RefUserPositions
	 */
	public function getRefUserPositions():?RefUserPositions {
		return null === $this->_position_id
			?null
			:RefUserPositions::getRecord($this->_position_id);
	}

}
