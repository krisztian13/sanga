<?php

namespace App\Model\Table;

use App\Model\Entity\Contact;
use Cake\Core\Exception\Exception;
use Cake\Filesystem\File;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Collection\Collection;

use Cake\Event\Event;
use Cake\Event\EventManager;

use Cake\Datasource\ConnectionManager;

/**
 * Contacts Model.
 *
 * @property \Cake\ORM\Association\BelongsTo $Zips
 * @property \Cake\ORM\Association\BelongsTo $WorkplaceZips
 * @property \Cake\ORM\Association\BelongsTo $Families
 * @property \Cake\ORM\Association\BelongsTo $Contactsources
 * @property \Cake\ORM\Association\HasMany $Documents
 * @property \Cake\ORM\Association\HasMany $Histories
 * @property \Cake\ORM\Association\BelongsToMany $Groups
 * @property \Cake\ORM\Association\BelongsToMany $Skills
 * @property \Cake\ORM\Association\BelongsToMany $Users
 */
class ContactsTable extends Table
{
    /**
     * Initialize method.
     *
     * @param array $config The configuration for the Table.
     */
    public function initialize(array $config)
    {
        $this->setTable('contacts');
        $this->setDisplayField('contactname');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo(
            'Zips',
            [
            'foreignKey' => 'zip_id',
            ]
        );
        $this->belongsTo(
            'WorkplaceZips',
            [
            'className' => 'Zips',
            'foreignKey' => 'workplace_zip_id',
            ]
        );
        $this->belongsTo(
            'Contactsources',
            [
            'foreignKey' => 'contactsource_id',
            ]
        );
        $this->hasMany(
            'Histories',
            [
            'foreignKey' => 'contact_id',
            'sort' => ['Histories.date' => 'DESC', 'Histories.id' => 'DESC'],
            ]
        );
        $this->hasMany(
            'Documents',
            [
            'foreignKey' => 'contact_id',
            'sort' => ['Documents.name' => 'ASC'],
            ]
        );

        $this->hasOne('ProfileImage', ['className' => 'Documents'])
            ->setProperty('profile_image')
            ->setConditions(['ProfileImage.name' => 'profile'])
            ->setDependent(true);

        $this->belongsToMany(
            'Groups',
            [
            'foreignKey' => 'contact_id',
            'targetForeignKey' => 'group_id',
            'joinTable' => 'contacts_groups',
            'sort' => 'Groups.name',
            ]
        );
        $this->belongsToMany(
            'Skills',
            [
            'foreignKey' => 'contact_id',
            'targetForeignKey' => 'skill_id',
            'joinTable' => 'contacts_skills',
            'sort' => 'Skills.name',
            ]
        );
        $this->belongsToMany(
            'Users',
            [
            'foreignKey' => 'contact_id',
            'targetForeignKey' => 'user_id',
            'joinTable' => 'contacts_users',
            'sort' => 'Users.name',
            ]
        );
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator
     *
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
          ->add('id', 'valid', ['rule' => 'numeric'])
          ->allowEmpty('id', 'create');

        $validator
          ->allowEmpty('contactname');

        $validator
          ->allowEmpty('legalname');

        $validator
          ->allowEmpty('address');

        $validator
          ->add('lat', 'valid', ['rule' => 'numeric'])
          ->allowEmpty('lat');

        $validator
          ->add('lng', 'valid', ['rule' => 'numeric'])
          ->allowEmpty('lng');

        $validator
          ->allowEmpty('phone');

        $validator
          ->add('email', 'valid', ['rule' => 'email'])
          ->allowEmpty('email');

        $validator
          ->add('birth', 'valid', ['rule' => 'date'])
          ->allowEmpty('birth');

        $validator
          ->add(
              'sex',
              'valid',
              ['rule' => ['inList', [1, 2]],
                                 'message' => __('Sex is 1 for male and 2 for female or empty'), ]
          )
          ->allowEmpty('sex');

        $validator
          ->allowEmpty('workplace');

        $validator
          ->allowEmpty('workplace_address');

        $validator
          ->allowEmpty('workplace_phone');

        $validator
          ->add('workplace_email', 'valid', ['rule' => 'email'])
          ->allowEmpty('workplace_email');

        $validator
          ->allowEmpty('family_id');

        $validator
          ->add('active', 'valid', ['rule' => 'boolean'])
          ->allowEmpty(
              'active',
              ['rule' => ['inList', [0, 1]],
                                 'message' => __('Active is 0 for inactive and 1 for active'), ]
          );
        $validator
          ->allowEmpty('comment');

        $validator
          ->allowEmpty('google_id');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     *
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['zip_id'], 'Zips'));
        $rules->add($rules->existsIn(['workplace_zip_id'], 'WorkplaceZips'));
        $rules->add($rules->existsIn(['contactsource_id'], 'Contactsources'));

        return $rules;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            if ((!empty($entity->contactname) + !empty($entity->legalname) + !empty($entity->zip_id)
                + !empty($entity->address) + !empty($entity->phone) + !empty($entity->email)
                + !empty($entity->birth->time) + !empty($entity->workplace)
                + !empty($entity->workplace_address)  + !empty($entity->workplace_phone)
                + !empty($entity->workplace_email) + !empty($entity->workplace->zip_id)
                + !empty($entity->contactsource_id) + !empty($entity->family_id)) >= 2
            ) {
                return true;
            }

            $entity->setErrors(['contactname' => __('At least 2 info should be filled')]);
            return false;
        }

        return true;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //debug($entity);
        if (!$entity->isNew()) {        //update
            $loggedInUser = $entity->loggedInUser;
            $addr = ['zip_id', 'address'];
            $toLog = ['legalname', 'contactname', 'phone', 'email', 'birth', 'workplace', 'comment',
                    'groups', 'skills', 'users', ];
            $toLog = array_merge($toLog, $addr);

            $oldEntity = $entity->extractOriginal($entity->visibleProperties());

            $details = [];

            foreach ($toLog as $prop) {
                if (isset($oldEntity[$prop])) {        //we had some data in this property
                    if ($entity->$prop != $oldEntity[$prop]) {    //and we changed it
                        if (!is_array($oldEntity[$prop])) {
                            if ($oldEntity[$prop] && $entity->$prop) {
                                $details[] = __('{0} changed from {1}  to {2}', [$prop, $oldEntity[$prop], $entity->$prop]);
                            } elseif ($oldEntity[$prop]) {
                                $details[] = __('{0}: {1} removed', [$prop, $oldEntity[$prop]]);
                            } else {
                                $details[] = __('{0}: {1} added', [$prop, $entity->$prop]);
                            }
                        } else {
                            $newEntityProp = $oldEntityProp = [];
                            foreach ($entity->$prop as $ep) {
                                $ep = $ep->toArray();
                                unset($ep['_joinData']);
                                $newEntityProp[] = $ep;
                            }
                            foreach ($oldEntity[$prop] as $op) {
                                $op = $op->toArray();
                                unset($op['_joinData']);
                                $oldEntityProp[] = $op;
                            }

                            foreach ($oldEntityProp as $oep) {
                                if (!in_array($oep, $newEntityProp)) {
                                    $details[] = __('{0} removed from {1}', [$oep['name'], $prop]);
                                }
                            }
                            foreach ($newEntityProp as $nep) {
                                if (!in_array($nep, $oldEntityProp)) {
                                    $details[] = __('{0} added to {1}', [$nep['name'], $prop]);
                                }
                            }
                        }
                        if (in_array($prop, $addr)) {    //the address or zip changed or both
                            $this->setGeo($entity->id);
                        }
                    }
                }
            }
            // TODO change this to an event
            $history = TableRegistry::get('Histories');
            foreach ($details as $detail) {
                $data = [
                  'id' => null,
                  'contact_id' => $entity->id,
                  'date' => date('Y-m-d'),
                  'create' => date('Y-m-d'),
                  'user_id' => $loggedInUser,
                  'event_id' => 1,
                  'detail' => $detail,
                 ];
                //debug($data);
                $newHistory = $history->newEntity($data);
                //debug($newHistory);//die();
                $history->save($newHistory);
            }
        } else {    //insert
            $this->setGeo($entity->id);
        }
    }

    public function beforeFind(Event $event, Query $query)
    {
        $query->where(['Contacts.active' => 1]);
        return $query;
    }

    private function setGeo($contactId)
    {
        exec(WWW_ROOT.'../bin/cake geo set_geo_for_user '.$contactId.' > /dev/null &');
    }

    /**
     * Check duplicates over the database
     *
     * @return array
     */
    public function checkDuplicates($owner = 0)
    {
        $onMail = $this->checkDuplicatesOnEmail($owner);
        $onBirth = $this->checkDuplicatesOnBirth($owner);
        $onPhone = $this->checkDuplicatesOnPhone($owner);
        $onGeo = $this->checkDuplicatesOnGeo($owner);
        $onNames = $this->checkDuplicatesOnNames($owner);
        $duplicatesBase = array_merge($onMail, $onBirth, $onPhone, $onGeo, $onNames);

        //there can be multiple fields what are similar, lets merge them
        $duplicates = $ids = [];
        foreach ($duplicatesBase as $d) {
            $index = array_search($d['id1'] . ':' . $d['id2'], $ids);
            if ($index === false) {
                $ids[] = $d['id1'] . ':' . $d['id2'];
                $index = count($ids) - 1;
                $duplicates[] = $d;
            } else {
                $field = [$duplicates[$index]['field'], $d['field']];
                if (is_array($duplicates[$index]['field'])) {
                    array_push($duplicates[$index]['field'], $d['field']);
                    $field = $duplicates[$index]['field'];
                }

                $data = [$duplicates[$index]['data'], $d['data']];
                if (is_array($duplicates[$index]['data'])) {
                    array_push($duplicates[$index]['data'], $d['data']);
                    $data = $duplicates[$index]['data'];
                }

                $duplicates[$index] = [
                    'id1' => $d['id1'],
                    'id2' => $d['id2'],
                    'field' => $field,
                    'data' => $data
                ];
            }
        }

        //write duplicates to a file
        $file = new File('../logs/' . Text::uuid(), true, 0644);
        $file->write(json_encode($duplicates));

        //dispatch Notification table about the event
        $event = new Event(
            'Model.Contact.afterDuplicates',
            $this,
            [
                'data' => [
                    'duplicates' => count($duplicates),
                    'file' => $file->name,
                    'owner' => $owner
                ]
            ]
        );
        $this->eventManager()->dispatch($event);

        $file->close();

        return $duplicates;
    }

  /*
  * Searching for duplicates: checkDuplicatesOn()
  *
  * email                same
  * birth                same
  * phone                remove non numeric, if not start with 00 or +, suppose it is +36 and add it
  * lat, lng                near (SQL float equality) - handles address
  * legalname, contactname    similar [legalname, contactname]
  *
  */
    public function checkDuplicatesOnEmail($owner = 0)
    {
        $duplicates = [];
        $duplicatesTemp = $this->find()
          ->innerJoin(
              ['c' => 'contacts'], //alias
              [//conditions
                 'Contacts.email = c.email',
                 'Contacts.id < c.id',
                 'Contacts.email != ' => '',
              ]
          )
          ->select(['Contacts.id', 'Contacts.email', 'c.id']);
        if ($owner) {
            $duplicatesTemp->innerJoinWith(
                'Users',
                function ($q) use ($owner) {
                    return $q->where(['Users.id' => $owner]);
                }
            );
        }
        foreach ($duplicatesTemp as $d) {
            $duplicates[] = [
                'id1' => $d->id,
                'id2' => (int) $d->c['id'],
                'field' => 'email',
                'data' => $d->email,
            ];
        }

        return $duplicates;
    }

    public function checkDuplicatesOnBirth($owner = 0)
    {
        $duplicates = [];
        $duplicatesTemp = $this->find()
          ->innerJoin(
              ['c' => 'contacts'], //alias
              [//conditions
                 'Contacts.birth = c.birth',
                 'Contacts.id < c.id',
                 'Contacts.birth != ' => '',
              ]
          )
          ->select(['Contacts.id', 'Contacts.birth', 'c.id']);
        if ($owner) {
            $duplicatesTemp->innerJoinWith(
                'Users',
                function ($q) use ($owner) {
                    return $q->where(['Users.id' => $owner]);
                }
            );
        }
        foreach ($duplicatesTemp as $d) {
            $duplicates[] = [
                'id1' => $d->id,
                'id2' => (int) $d->c['id'],
                'field' => 'birth',
                'data' => $d->birth,
            ];
        }

        return $duplicates;
    }

    private function huPhoneReformat($table)
    {
        $removes = 'REPLACE(
                      REPLACE(
                          REPLACE(
                              REPLACE(
                                  REPLACE(
                                      REPLACE('.$table.'.phone, "+", ""),
                                  "-", ""),
                              " ", ""),
                          "/", ""),
                      "(", ""),
                  ")", "")';

        $removes = 'CONCAT(
                      REPLACE(
                          SUBSTRING('.$removes.',    1, 4), "0036", "36"
                      ),
                      SUBSTRING('.$removes.', 5)
                  )';

        $tPhone = 'CONCAT(
                      REPLACE(
                          SUBSTRING('.$removes.',    1, 2), "06", "36"
                      ),
                      SUBSTRING('.$removes.', 3)
                  )';

        return $tPhone;
    }

    public function checkDuplicatesOnPhone($owner = 0)
    {
        $duplicates = [];
        $duplicatesTemp = $this->find()
          ->innerJoin(
              ['c' => 'contacts'], //alias
              [//conditions
                 $this->huPhoneReformat('Contacts').' = '.$this->huPhoneReformat('c'),
                 'Contacts.id < c.id',
                 'Contacts.phone != ' => '',
              ]
          )
          ->select(
              [
                  'Contacts.id', 'Contacts.phone', 'c.id',
                  'tCPhone' => $this->huPhoneReformat('Contacts'),
                  'tcPhone' => $this->huPhoneReformat('c'),
              ]
          );
        if ($owner) {
            $duplicatesTemp->innerJoinWith(
                'Users',
                function ($q) use ($owner) {
                    return $q->where(['Users.id' => $owner]);
                }
            );
        }
        foreach ($duplicatesTemp as $d) {
            $duplicates[] = [
                'id1' => $d->id,
                'id2' => (int) $d->c['id'],
                'field' => 'phone',
                'data' => $d->phone,
            ];
        }

        return $duplicates;
    }

    public function checkDuplicatesOnGeo($owner = 0)
    {
        $duplicates = [];
        $delta = 0.0001;    //10m
        $duplicatesTemp = $this->find()
          ->innerJoin(
              ['c' => 'contacts'], //alias
              [//conditions
                 'ABS(Contacts.lat - c.lat) < '.$delta,
                 'ABS(Contacts.lng - c.lng) < '.$delta,
                 'Contacts.id < c.id',
                 'Contacts.lat != ' => 0,
              ]
          )
          ->select(
              [
                  'Contacts.id', 'Contacts.zip_id', 'Contacts.address',
                  'c.id', 'c.zip_id', 'c.address',
              ]
          );
        if ($owner) {
            $duplicatesTemp->innerJoinWith(
                'Users',
                function ($q) use ($owner) {
                    return $q->where(['Users.id' => $owner]);
                }
            );
        }
        foreach ($duplicatesTemp as $d) {
            $duplicates[] = [
                'id1' => $d->id,
                'id2' => (int) $d->c['id'],
                'field' => 'geo',
                'data' => $d->zip_id.' & '.$d->address.
                      ' : '.$d->c['zip_id'].' & '.$d->c['address'],
                ];
        }

        return $duplicates;
    }

    public function checkDuplicatesOnNames($owner = 0, $distance = 3)
    {
        $duplicates = [];
        $rows = $this->find()
          ->select(['id', 'contactname', 'legalname']);
        if ($owner) {
            $rows->innerJoinWith(
                'Users',
                function ($q) use ($owner) {
                    return $q->where(['Users.id' => $owner]);
                }
            );
        }
        $rows = $rows->toArray();

        foreach ($rows as $r) {
            foreach ($rows as $r2) {
                if ($r->id < $r2->id) { //compare every row only ones
                    $lcc = levenshtein(utf8_decode($r->contactname), utf8_decode($r2->contactname));
                    $lcl = levenshtein(utf8_decode($r->contactname), utf8_decode($r2->legalname));
                    $llc = levenshtein(utf8_decode($r->legalname), utf8_decode($r2->contactname));
                    $lll = levenshtein(utf8_decode($r->legalname), utf8_decode($r2->legalname));

                    if (($lcc <= $distance && ($r->contactname && $r2->contactname))
                      || ($lcl <= $distance && ($r->contactname && $r2->legalname))
                      || ($llc <= $distance && ($r->legalname && $r2->contactname))
                      || ($lll <= $distance && ($r->legalname && $r2->legalname))
                    ) {
                        $duplicates[] = [
                            'id1' => $r->id,
                            'id2' => $r2->id,
                            'field' => 'name',
                            'data' => $r->contactname.' & '.$r->legalname.' : '.
                              $r2->contactname.' & '.$r2->legalname,
                            'levenshtein' => [
                                'lcc' => $lcc,
                                'lcl' => $lcl,
                                'llc' => $llc,
                                'lll' => $lll,
                            ],
                        ];
                    }
                }
            }
        }
        return $duplicates;
    }

    /**
     * Find contacts owned by given user(s)
     * The given users are the contact persons for the contact.
     *
     * @param Query $query   The query object
     * @param array $options Options for filter matching, 'User.id' should be present
     *
     * @return Query $query
     */
    public function findOwnedBy(Query $query, array $options)
    {
        return $query->innerJoinWith(
            'Users',
            function ($q) use ($options) {
                return $q->where(['Users.id IN ' => $options['User.id']]);
            }
        );
    }

    /**
     * Find contacts accessible by given user(s)
     * The given users are the contact persons for the contacts
     *                  or has access to a group to where the contacts belongs to
     *                      TODO dev-question: https://github.com/rrd108/sanga/issues/163
     *                  or are an admin of a usergroup and the contact's contact person
     *                      belongs to that usergroup.
     *
     * @param Query $query   The query object
     * @param array $options Options for filter matching, 'User.id' should be present
     *
     * @return array
     */
    public function findAccessibleBy(Query $query, array $options)
    {
        //as $query is a reference it's value will change after every find, but we need the original one
        $queryTemp1 = $query->cleanCopy();
        $queryTemp2 = $query->cleanCopy();
        $queryTemp3 = $query->cleanCopy();

        list($contain, $hasMany, $belongsToMany) = $this->getAssociationsArrays($options);

        //ONLY_FULL_GROUP_BY miatt a GROUP BY-hoz kell kapcsolni minden kapcsolódó
        //táblából származó select értéket, ami nincs aggregalva
        $groupBy = 'Contacts.id';
        if($contain) {
            foreach($contain as $key => $value) {
                if (is_string($key)) {
                    $groupBy .= ', ' . $key;
                }
            }
        } else {
            $contain = [];
        }

        if($hasMany) {
            foreach($hasMany as $key => $value) {
                //remove first part if there are more dots
                if (substr_count($key, '.') > 1) {
                    $key = substr($key, strpos($key, '.') + 1);
                }
                $groupBy .= ', ' . $key;
            }
        }

        //select értékek összeállítasa
        //belongsToMany esetén MySQL group_concat() függvényt kell majd használni,
        //így külön kell szedni -> select és groupConcats tömbökre
        $groupConcats = array();
        if ($belongsToMany) {
            foreach ($options['_select'] as $field) {
                if (array_key_exists($field, $belongsToMany)) {
                    $groupConcats[] = $field;
                }
                else {
                    $select[] = $field;
                }
            }
        }
        else {
            $select = isset($options['_select']) ? $options['_select'] : null;
       }

        //az options tömbből csaka User.id lesz használva ezekben a hívásokban
        $owned = $this->findOwnedBy($queryTemp1, $options);
        $accessibleViaGroups = $this->findAccessibleViaGroupBy($queryTemp2, $options);
        $accessibleViaUsergroups = $this->findAccessibleViaUsergroupBy($queryTemp3, $options);

        if($select) {
            $owned->select($select);
            $accessibleViaGroups->select($select);
            $accessibleViaUsergroups->select($select);
        }

        //ha van belongsToMany tablara vonatkozo kereses, akkor itt tesszuk hozza a select reszhez group_concat-tal
        if(!empty($groupConcats)) {
            foreach($groupConcats as $item) {
                $itemName = str_replace('.', '__', $item);
                $owned->select([$itemName => 'GROUP_CONCAT('.$item.' SEPARATOR \'|\')']);
                $accessibleViaGroups->select([$itemName => 'GROUP_CONCAT('.$item.' SEPARATOR \'|\')']);
                $accessibleViaUsergroups->select([$itemName => 'GROUP_CONCAT('.$item.' SEPARATOR \'|\')']);
            }
        }

        //in the where() call we need only conditions belongs to contacts
        //and where array from $contain
        $whereContacts = isset($options['_where']) ? $this->getPart('Contacts', $options['_where']) : [];
        $where = array_merge($whereContacts, $contain);

        if ($whereContacts) {
            $where = $this->buildWhere($where);

            $owned->where($where);
            $accessibleViaGroups->where($where);
            $accessibleViaUsergroups->where($where);
        }

        if ($contain) {
            //getting tableNames from $contain
            $containTables = [];
            foreach ($contain as $i => $v) {
                if (is_string($i)) {
                    $v = $this->getTableName($i);
                }
                if (!in_array($v, $containTables)) {
                    $containTables[] = $v;
                }
            }
            //TODO if we have exactly 2 tables here and ask for histories the results are dupliated
            //if we have only 1 or 3 we get no duplication
            $owned->contain($containTables);
            $accessibleViaGroups->contain($containTables);
            $accessibleViaUsergroups->contain($containTables);
        }

        //debug($accessibleViaGroups);
        //TODO the next innerJoin creates only
        //INNER JOIN histories Histories ON Contacts.id = (Histories.contact_id)
        //without group filter because $accessibleViaGroups called an innerJoin upper
        //and Cake requires to start from the deeper (more dotted) associations
        //that is why Histories.groups search drops filter
         /*$accessibleViaGroups->innerJoinWith(
            'Histories.Groups',
            function ($q) {
                return $q-where(['Groups.name' => 'seva']);
            }
        );*/
        //debug($accessibleViaGroups->sql());

        if ($hasMany) {
            foreach ($hasMany as $field => $conditions) {
                $callback = $this->getCallback($field, $conditions);
                $tableName = $this->getTableName($field);
                $owned->innerJoinWith($tableName, $callback);
                $accessibleViaGroups->innerJoinWith($tableName, $callback);
                $accessibleViaUsergroups->innerJoinWith($tableName, $callback);
            }
        }

        if ($belongsToMany) {
            foreach ($belongsToMany as $field => $conditions) {
                $callback = $this->getCallback($field, $conditions);
                $tableName = $this->getTableName($field);
                $owned->innerJoinWith($tableName, $callback)->group($groupBy);
                $accessibleViaGroups->innerJoinWith($tableName, $callback)->group($groupBy);
                $accessibleViaUsergroups->innerJoinWith($tableName, $callback)->group($groupBy);
            }
        }
        //$accessibleViaGroups->all();
        //die();

        $accessible = $owned
            ->union($accessibleViaGroups)
            ->union($accessibleViaUsergroups);

        //we need this custom counter as we use epilog here
        $accessibleCount = $accessible->count();
        $accessible->counter(function ($query) use ($accessibleCount) {
            return $accessibleCount;
        });

        //we should add the order by and pagination to the end - after the union.
        //For this we  have to use epilog, ORM call will not work
        //http://stackoverflow.com/questions/29379579/how-do-you-modify-a-union-query-in-cakephp-3/29386189#29386189
        $order = '';
        if (isset($options['_order'])) {
            foreach ($options['_order'] as $field => $ascdesc) {
                $order .= ' ' . str_replace('.', '__', $field) . ' ' . $ascdesc . ',';
            }
            if ($order) {
                $order = 'ORDER BY' . rtrim($order, ',');
            }
        }

        $limit = 20;
        if (isset($options['_limit'])) {
            if ($options['_limit'] !== false) {
                $limit = $options['_limit'];
            } else {
                $limit = '';
            }
        }
        if ($limit) {
            $page = isset($options['_page']) ? $options['_page'] : 1;
            $offset = $limit * ($page - 1);
            $limit = ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        $accessible->epilog($order . $limit);

        return $accessible;
    }

    /**
     * Find contacts accessible by given user(s) by a contacts' group memberships
     * accessible by group admins and users who has acess to the group.
     *
     * @param Query $query   The query object
     * @param array $options Options for filter matching, 'User.id' should be present
     *
     * @return Cake\ORM\Query $query
     */
    private function findAccessibleViaGroupBy(Query $query, array $options)
    {
        //accessible groups for the user
        $groupIds = $this->Groups
            ->find('accessible', $options)
            ->select('Groups.id');

        $query = $this->findInGroups($query, ['Group._ids' => $groupIds]);
        return $query;
    }

    /**
     * Find contacts accessible by given user(s) by a user groups
     * ie the users are an admin of a usergroup and the contact's contact person
     * belongs to that usergroup.
     *
     * @param Query $query   The query object
     * @param array $options Options for filter matching, 'User.id' should be present
     *
     * @return Cake\ORM\Query $query
     */
    private function findAccessibleViaUsergroupBy(Query $query, array $options)
    {
        //get users who are in any user groups where the given user(s) are admin
        $userIds = $this->Users->getUnderAdminOf($options['User.id'])->select('Users.id');
        if($userIds->count()) {
            //who are their contacts
            return $this->findOwnedBy($query, ['User.id' => $userIds]);
        }
        return $query->where(['Contacts.id < ' => 0]);  //forcing return value to a Query object
    }

    /**
     * Find contacts who are members of the given groups.
     *
     * @param Query $query   The query object
     * @param array $options Options for filter matching, 'groupIds' should be present
     *
     * @return Cake\ORM\Query $query
     */
    public function findInGroups(Query $query, array $options)
    {
        return $query->innerJoinWith(
            'Groups',
            function ($q) use ($options) {
                return $q->where(['Groups.id IN ' => $options['Group._ids']]); //TODO drop IN as we got a query? not an array?
            }
        );
    }

    /**
     * Is the contact accessible for the user because
     *         the user is a contact person for the contact, or
     *         the contact is in a group what is accessible by the user, or
     *         the contact person of the contact is a member of a usergroup what is created by the user.
     */
    public function isAccessible($contactId, $userId)
    {
        if ($this->Users->isAdminUser($userId)) {
            return true;
        }
        if ($this->isAccessibleAsContactPerson($contactId, $userId)) {
            return true;
        }
        if ($this->isAccessibleAsGroupMember($contactId, $userId)) {
            return true;
        }
        if ($this->isAccessibleAsUsergroupMember($contactId, $userId)) {
            return true;
        }

        return false;
    }

    /**
     * Is the contact accessible for the user because
     *         the user is a contact person for the contact.
     */
    public function isAccessibleAsContactPerson($contactId, $userId)
    {
        $contact = $this->find()
            ->select('id')
            ->where(['Contacts.id' => $contactId])
            ->matching(
                'Users',
                function ($q) use ($userId) {
                    return $q->where(['Users.id' => $userId]);
                }
            )
            ->toArray();
        //debug($contact);
        if (isset($contact[0]) && $contact[0]['id'] == $contactId) {
            //Log::write('debug', 'Accessibel as contact person ' . $contactId . ' :: ' . $userId);
            return true;
        }

        return false;
    }

    /**
     * Is the contact accessible for the user because
     *         the contact is in a group what is accessible by the user.
     */
    private function isAccessibleAsGroupMember($contactId, $userId)
    {
        $groupIds = $this->getGroupMemberships($contactId);
        if (count($groupIds)) {
            //user has access for the group as a member or admin
            $userAsMember = $this->Users->find()
                ->where(['Users.id' => $userId])
                ->matching(
                    'Groups',
                    function ($q) use ($groupIds) {
                        return $q->where(['Groups.id IN ' => $groupIds]);
                    }
                )
                ->toArray();
            if (count($userAsMember)) {
                //Log::write('debug', 'Accessibel as group member ' . $contactId . ' :: ' . $userId);
                return true;
            }

            $userAsAdmin = $this->Users->find()
                ->where(['Users.id' => $userId])
                ->matching(
                    'AdminGroups',
                    function ($q) use ($userId, $groupIds) {
                        return $q->where(
                            [
                                'AdminGroups.admin_user_id' => $userId,
                                'AdminGroups.id IN' => $groupIds,
                            ]
                        );
                    }
                )
                ->toArray();
            if (count($userAsAdmin)) {
                //Log::write('debug', 'Accessibel as group admin ' . $contactId . ' :: ' . $userId);
                return true;
            }
        }

        return false;
    }

    /**
     * Is the contact accessible for the user because
     *         the contact person of the contact is a member of a usergroup what is created by the user.
     */
    private function isAccessibleAsUsergroupMember($contactId, $userId)
    {
        //get contact persons
        $contactUserTemp = $this->get($contactId, ['contain' => 'Users']);
        foreach ($contactUserTemp->users as $u) {
            $userIds[] = $u->id;
        }
        //get their usergroup memberships
        $usergroupMembershipsTemp = $this->Users->find()
            ->matching(
                'Usergroups',
                function ($q) use ($userIds) {
                    return $q->where(['Users.id IN ' => $userIds]);
                }
            );
        foreach ($usergroupMembershipsTemp as $uId) {
            if (isset($uId->_matchingData['Usergroups']->admin_user_id)) {
                $userIds[] = $uId->_matchingData['Usergroups']->admin_user_id;
            }
            if (isset($uId->_matchingData['UserUsergroups']->user_id)) {
                $userIds[] = $uId->_matchingData['UserUsergroups']->user_id;
            }
        }
        if (in_array($userId, $userIds)) {
            return true;
        }

        return false;
    }

    //group memberships of the contact
    private function getGroupMemberships($contactId)
    {
        $contactGroups = $this->find()
          ->contain(['Groups'])
          ->where(['Contacts.id' => $contactId]);
        $groupIds = [];
        foreach ($contactGroups as $c) {
            foreach ($c->groups as $g) {
                $groupIds[] = $g->id;
            }
        }

        return $groupIds;
    }

    /**
     *
     * Which users has access to this contact
     *
     * @param $contactId
     * @return array
     */
    public function hasAccess($contactId)
    {
        $access = ['contactPersons' => [], 'groupMembers' => [], 'usergroupMembers' => []];

        //has access as contact person
        $contact = $this->get($contactId, ['contain' => ['Users']]);
        $access['contactPersons'] = $contact->users;

        //has access as group member
        $groupIds = $this->getGroupMemberships($contactId);
        if (count($groupIds)) {
            //user has access for the group as a member or admin
            $userAsMember = $this->Users->find()
                ->matching(
                    'Groups',
                    function ($q) use ($groupIds) {
                        return $q->where(['Groups.id IN ' => $groupIds]);
                    }
                )
                ->toArray();
            //debug($userAsMember);
            $access['groupMembers'] = $userAsMember;

            $userAsAdmin = $this->Users->find()
                ->matching(
                    'AdminGroups',
                    function ($q) use ($groupIds) {
                        return $q->where(['AdminGroups.id IN' => $groupIds]);
                    }
                )
                ->toArray();
            //debug($userAsAdmin);
            $access['groupMembers'][] = $userAsAdmin[0];        //only 1 user could be the admin fro a group
            //debug($access);
        }

        //has access as usergroup member
        //get contact persons ids
        foreach ($contact->users as $u) {
            $userIds[] = $u->id;
        }
        //debug($userIds);
        //get their usergroup memberships
        $usergroupMemberships = $this->Users->find()
            ->matching(
                'Usergroups',
                function ($q) use ($userIds) {
                    return $q->where(['Users.id IN ' => $userIds]);
                }
            )
            ->toArray();
        foreach ($usergroupMemberships as $u) {
            //get the usergroup admin
            $usergroupAdmin = $this->Users->get($u->_matchingData['Usergroups']->admin_user_id);
            array_unshift($usergroupMemberships, $usergroupAdmin);
        }
        $access['usergroupMembers'] = $usergroupMemberships;

        return $access;
    }

    /**
     * Transform the array created by searcquery form
     * into a query object for where() calls
     *
     * @param $options : it is something like this
     *      [
     *          'Contacts.contactname' => [
     *              'condition' => ['&%'],
     *              'value' => ['a']
     *          ],
     *          'Contacts.legalname' => [
     *              'connect' => '&',
     *              'condition' => ['&%'],
     *              'value' => ['']
     *          ],
     *          'Zips.name' => [
     *              'connect' => '&',
     *              'condition' => ['&%'],
     *              'value' => ['b']
     *          ],
     *          'Histories.Groups.name' => [
     *              'connect' => '&',
     *              'condition' => ['&%'],
     *              'value' => ['seva']
     *          ]
     *      ]
     * @return \Cake\Database\Expression\QueryExpression|null
     */
    private function buildWhere($where)
    {
        $conditions = $this->removeEmptyConditions($where);

        if (!count($conditions)) {
            return null;
        } else {
            return $this->getWhereQueryExpressionObject($conditions);
        }
    }

    /**
     * Translate
     *      '=', 'contactname', 'Gábor'
     *     to
     *      'contactname = "Gábor"' for SQL
     *
     * @param $conditionCode
     * @param $field
     * @param $value
     * @return string
     */
    private function translateCode2SQL($conditionCode, $field, $value)
    {
        switch ($conditionCode) {
            case "%":
                $key = $field . ' LIKE ';
                $val = '"%' . $value . '%"';
                break;
            case "=":
                $key = $field . ' = ';
                $val = is_string($value) ? '"' . $value . '"' : $value;
                break;
            case "!":
                $key = $field . ' != ';
                $val = $val = is_string($value) ? '"' . $value . '"' : $value;
                break;
            case "<":
                $key = $field . ' < ';
                $val = $val = is_string($value) ? '"' . $value . '"' : $value;
                break;
            case ">":
                $key = $field . ' > ';
                $val = $val = is_string($value) ? '"' . $value . '"' : $value;
                break;
        }
        return $key . $val;
    }


    /**
     * Get the part of $where array identified by $tableName
     *
     * @param string $tableName
     * @param array $where
     * @return array
     */
    private function getPart($tableName, array $where)
    {
        $ownedWhere = [];
        if (isset($where)) {
            foreach ($where as $field => $data) {
                $tblName = $this->getTableName($field);
                if ($tblName == $tableName) {
                    $ownedWhere[$field] = $data;
                }
            }
        }
        return $ownedWhere;
    }

    private function getAssociationsArrays(array $options)
    {
        $associations = $belongsToMany = $hasMany = [];
        $contain = isset($options['_contain']) ? $options['_contain'] : [];
        if (isset($options['_where'])) {
            //on belongsToMany $association->type() is manyToMany
            foreach ($this->associations() as $association) {
                $associations[$association->name()] = $association->type();
            }
            foreach ($options['_where'] as $field => $data) {
                $tableName = $this->getTableName($field);
                //$baseTableName will we be the first associated table in case of
                //tableName.otherTableName format (deeper association)
                $baseTableName = $this->getTableName($tableName);
                if (isset($associations[$baseTableName])) {
                    if ($associations[$baseTableName] == 'manyToOne') {
                        $contain[$field] = $data;
                    } elseif ($associations[$baseTableName] == 'oneToMany') {
                        $hasMany[$field] = $data;
                    } elseif ($associations[$baseTableName] == 'manyToMany') {
                        $belongsToMany[$field] = $data;
                    } else {
                        //oneToOne
                    }
                }
            }
        }

        $hasMany = $this->sortByDots($hasMany);
        $belongsToMany = $this->sortByDots($belongsToMany);

        return [
            $contain,
            $hasMany,
            $belongsToMany
        ];
    }

    /**
     * Returns the table name (Histories) or
     * the table name with the associated table (Histories.Events)
     *
     * @param $name
     * @return string
     */
    private function getTableName($name)
    {
        $dotPosition = strrpos($name, '.'); //last dot
        $tableName = substr($name, 0, $dotPosition);
        if ($tableName) {
            return $tableName;
        } else {
            return $name;
        }
    }

    /**
     * Returns the field name (Histories.date)
     *
     * @param $name
     * @return string
     */
    private function getFieldName($name)
    {
        if (substr_count($name, '.') > 1) {
            $dotPosition = strpos($name, '.');  //first dot
            $name = substr($name, $dotPosition + 1);
        }
        return $name;
    }

    /**
     * From the $condtions array (created by searchquery form)
     * we create an SQL string for WHERE and than transform it
     * to a Query object
     *
     * @param array $conditions
     * @return \Cake\Database\Expression\QueryExpression
     */
    private function getWhereQueryExpressionObject(array $conditions)
    {
        // TODO security of values!
        $where = '';
        $bracketOpened = false;
        foreach ($this->associations() as $association) {
            $associations[$association->name()] = $association->type();
        }

        foreach ($conditions as $field => $data) {
            $field = $this->getFieldName($field);
            if (!empty($data['value'])) {
                if (!isset($data['connect'])) {    //this is the first line of the conditions
                    $where .= '( ';
                    $bracketOpened = true;
                } elseif ($data['connect'] == '&' && strlen($where)) {
                    $where .= ' AND ( ';
                    $bracketOpened = true;
                } elseif ($data['connect'] == '|' && strlen($where)) {
                    $where .= ' OR ( ';
                    $bracketOpened = true;
                }

                $conditionCount = count($data['condition']) - 1;
                foreach ($data['condition'] as $i => $condition) {
                    if ($i > 0) {
                        if ($condition[0] == '&') {
                            $where .= ' AND ';
                        } else {
                            $where .= ' OR ';
                        }
                    }
                    $where .= $this->translateCode2SQL($condition[1], $field, $data['value'][$i]);
                    if ($i == $conditionCount && $bracketOpened) {
                        $where .= ')';
                    }
                }
            }
        }
        $query = new Query($this->getConnection(), $this);
        return $query->newExpr()->add('(' . $where . ')');
    }

    /**
     * Remove items where the value is empty
     * these items generated by searchquery field, we selected them
     * but there are no values for where, eg they are there
     * in SELECT but they are not there in WHERE
     *
     * @param $conditions
     * @return array
     */
    private function removeEmptyConditions($conditions)
    {
        foreach ($conditions as $field => $data) {
            if (isset($data['value'])) {
                foreach ($data['value'] as $i => $value) {
                    //remove only if type of select is contain, leave others
                    if ($data['condition'][$i] == '&%' && $value == '') {
                        unset($conditions[$field]['condition'][$i]);
                        unset($conditions[$field]['value'][$i]);
                    }
                }
            }
        }

        foreach ($conditions as $field => $data) {
            if (!isset($data['value']) || !$data['value']) {
                unset($conditions[$field]);
            }
        }

        return $conditions;
    }

    /**
     * @param $arr
     * @param $dotsNum
     * @return mixed
     */
    private function sortByDots($arr)
    {
        //hasMany should be sorted
        //we should have entries with dots at the beginning to avoid duplicate calls
        //on matching conditions
        //however there is a good chance, that condition duplication is
        //dropped by MySQL optimizer
        $dotsNum = [];
        foreach ($arr as $field => $condition) {
            $dotsNum[$field] = substr_count($field, '.');
        }
        arsort($dotsNum);
        $sortedArr = [];
        foreach ($dotsNum as $field => $num) {
            $sortedArr[$field] = $arr[$field];
        }
        return $sortedArr;
    }

    /**
     * @param $field
     * @param $conditions
     * @return Clousure
     */
    private function getCallback($field, $conditions)
    {
        if ($conditions['value']) {
            $queryExp = $this->buildWhere([$field => $conditions]);
            $callback = function ($q) use ($queryExp) {
                return $q->where($queryExp);
            };
        } else {
            $callback = null;
        }
        return $callback;
    }
}
