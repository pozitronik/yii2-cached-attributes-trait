<?php
declare(strict_types = 1);

namespace app\models;

use pozitronik\references\models\ArrayReference;

/**
 * Class RefUserPositions
 */
class RefUserPositions extends ArrayReference {

	public array $items = [
		1 => 'Manager',
		2 => 'CEO'
	];

}