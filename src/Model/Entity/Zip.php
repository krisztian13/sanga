<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Zip Entity.
 */
class Zip extends Entity {

/**
 * Fields that can be mass assigned using newEntity() or patchEntity().
 *
 * @var array
 */
	protected $_accessible = [
		'zip' => true,
		'name' => true,
		'contacts' => true,
	];

}
