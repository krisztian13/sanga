<?php
namespace App\Model\Table;

use App\Model\Entity\Usergroup;
use Cake\Collection\Collection;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\Validation\Validator;

use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Event\Event;

/**
 * Notifications Model
 */
class NotificationsTable extends Table implements EventListenerInterface
{

    /**
 * Initialize method
 *
 * @param  array $config The configuration for the Table.
 * @return void
 */
    public function initialize(array $config)
    {
        $this->table('notifications');
        $this->displayField('id');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo(
            'Users',
            [
            'foreignKey' => 'user_id',
            ]
        );
        $this->belongsTo(
            'Senders',
            [
            'className' => 'Users',
            'foreignKey' => 'sender_id',
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
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('id', 'create')
            ->allowEmpty('sender_id')
            ->add('user_id', 'valid', ['rule' => 'numeric'])
            ->requirePresence('user_id', 'create')
            ->notEmpty('user_id')
            ->allowEmpty('notification')
            ->add('unread', 'valid', ['rule' => 'boolean'])
            ->allowEmpty('unread');

        return $validator;
    }

    public function findUnread(Query $query, array $options)
    {
        $query->where(
            [
                'Notifications.unread' => true,
                'Notifications.user_id' => $options['User.id']
                ]
        );
        return $query;
    }

    public function implementedEvents()
    {
        return [
            'Model.Contact.afterDuplicates' => 'duplicatesNotification',
            'Controller.Usergroup.afterUserAdded' => 'inviteUsersToUsergroup',
        ];
    }

    public function duplicatesNotification(Event $event, array $data)
    {
        //debug($event);
        //debug($data);
    }

    /**
     * @param Event $event
     * @param Usergroup $usergroup
     */
    public function inviteUsersToUsergroup(Event $event, Usergroup $usergroup)
    {
        $invitor = $this->Users->get($usergroup->admin_user_id);
        foreach ($usergroup->users as $user) {
            $data = [
                'user_id' => $user->id,
                'sender_id' => $usergroup->admin_user_id,
                'notification' => __('{0} invited you to join {1} usergroup. ' .
                    'Join by <a href="' .
                    Router::url(['controller' => 'Usergroups', 'action' => 'join', $usergroup->id]) .
                    '">clicking here</a>.',
                    $invitor->name, $usergroup->name),
                'unread' => 1
            ];
            $notification = $this->newEntity($data);
            if ($this->save($notification)) {
                return true;
            } else {
                Log::debug('Notification creation error' . explode(', ', $notification->errors()));
                return false;
            }
        }
    }
}