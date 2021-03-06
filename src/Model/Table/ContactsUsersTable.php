<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ContactsUsers Model
 */
class ContactsUsersTable extends Table
{

    /**
 * Initialize method
 *
 * @param  array $config The configuration for the Table.
 * @return void
 */
    public function initialize(array $config)
    {
        $this->setTable('contacts_users');
        $this->setDisplayField('contact_id');
        $this->setPrimaryKey(['contact_id', 'user_id']);

        $this->belongsTo(
            'Contacts',
            [
            'foreignKey' => 'contact_id',
            ]
        );
        $this->belongsTo(
            'Users',
            [
            'foreignKey' => 'user_id',
            ]
        );
    }

    /**
 * Default validation rules.
 *
 * @param  \Cake\Validation\Validator $validator
 * @return \Cake\Validation\Validator
 */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('contact_id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('contact_id', 'create')
            ->add('user_id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('user_id', 'create');

        return $validator;
    }
}
