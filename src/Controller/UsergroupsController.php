<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Utility\Hash;

/**
 * Usergroups Controller
 *
 * @property App\Model\Table\UsergroupsTable $Usergroups
 */
class UsergroupsController extends AppController
{

    public function isAuthorized($user = null)
    {
        return true;
    }

    /**
     * Index method
     *
     * @param $filter
     * @return void
     */
    public function index($filter = null)
    {
        $owned = $this->Usergroups->find(
            'ownedBy',
            [
                'User.id' => $this->Auth->user('id'),
            ]
        );

        if ($filter == 'month') {
            $filter = date('Y-m') . '-01';
        } elseif ($filter == 'week') {
            $filter = date('Y-m-d', strtotime('-1 weeks'));
        } else {
            $filter = '1900-01-01';
        }
        $query = $this->Usergroups->Users->find()
            ->where(
                [
                    'Users.id IN' => Hash::extract($owned->toArray(), '{n}.users.{n}.id')
                ]
            );
        $query->select(['Users.id', 'total_contacts' => $query->func()->count('Contacts.id'), 'total_histories' => $query->func()->count('Histories.id')])
            ->where(['Contacts.created >=' => $filter])
            ->orWhere(['Histories.created >=' => $filter])
            ->leftJoinWith('Contacts')
            ->leftJoinWith('Histories')
            ->group(['Users.id']);
        $totalsByUsers = Hash::combine($query->toArray(), '{n}.id', '{n}');
        $this->set(compact('totalsByUsers'));

        $memberships = $this->Usergroups->find(
            'memberships',
            [
                'User.id' => $this->Auth->user('id')
            ]
        );

        $this->set('ownedBy', $owned);
        $this->set('memberships', $memberships);
    }

    /**
 * Add method
 *
 * @return \Cake\Network\Response|void
     */
    public function add()
    {
        $this->request->withData('admin_user_id', $this->Auth->user('id'));
        $usergroup = $this->Usergroups->newEntity($this->request->getData());
        if ($this->request->is('post')) {
            if ($this->Usergroups->save($usergroup)) {
                $this->Flash->success('The usergroup has been saved.');
                //send invitations via event system
                $event = new Event(
                    'Controller.Usergroups.afterUserAdded',
                    $this,
                    ['usergroup' => $usergroup]
                );
                $this->eventManager()->dispatch($event);

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('The usergroup could not be saved. Please, try again.');
            }
        }
        $users = $this->Usergroups->Users->find('list');
        $this->set(compact('usergroup', 'users'));
    }

    /**
     * @param int $id
     * @return \Cake\Network\Response|null
     */
    public function join(int $id)
    {
        $joined = $this->Usergroups->join($id, $this->Auth->user('id'));
        if ($joined) {
            $this->Flash->success('You have successfully joined');
            //TODO notify admin
        } else {
            $this->Flash->error('You were not able to join this group');
        }
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Edit method
     *
     * @param  string $id
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException
     */
    public function edit($id = null)
    {
        //TODO only admin can edit
        $usergroup = $this->Usergroups->get(
            $id,
            [
                'contain' => ['Users']
            ]
        );
        if ($this->request->is(['patch', 'post', 'put'])) {
            $usergroup = $this->Usergroups->patchEntity($usergroup, $this->request->getData());
            if ($this->Usergroups->save($usergroup)) {
                $this->Flash->success('The usergroup has been saved.');
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('The usergroup could not be saved. Please, try again.');
            }
        }
        $users = $this->Usergroups->Users->find('list');
        $this->set(compact('usergroup', 'users'));
    }

    /**
 * Delete method
 *
 * @param  string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
    public function delete($id = null)
    {
        $usergroup = $this->Usergroups->get($id);
        $this->request->allowMethod(['post', 'delete']);
        if ($this->Usergroups->delete($usergroup)) {
            $this->Flash->success('The usergroup has been deleted.');
        } else {
            $this->Flash->error('The usergroup could not be deleted. Please, try again.');
        }
        return $this->redirect(['action' => 'index']);
    }
}
