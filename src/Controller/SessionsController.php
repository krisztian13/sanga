<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Sessions Controller
 *
 * @property App\Model\Table\SessionsTable $Sessions
 */
class SessionsController extends AppController
{

    /**
 * Index method
 *
 * @return void
 */
    public function index()
    {
        $this->set('sessions', $this->paginate($this->request->session()s));
    }

    /**
 * View method
 *
 * @param  string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
    public function view($id = null)
    {
        $session = $this->request->session()s->get(
            $id,
            [
            'contain' => []
            ]
        );
        $this->set('session', $session);
    }

    /**
 * Add method
 *
 * @return void
 */
    public function add()
    {
        $session = $this->request->session()s->newEntity($this->request->data);
        if ($this->request->is('post')) {
            if ($this->request->session()s->save($session)) {
                $this->Flash->success('The session has been saved.');
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('The session could not be saved. Please, try again.');
            }
        }
        $this->set(compact('session'));
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
        $session = $this->request->session()s->get(
            $id,
            [
            'contain' => []
            ]
        );
        if ($this->request->is(['patch', 'post', 'put'])) {
            $session = $this->request->session()s->patchEntity($session, $this->request->data);
            if ($this->request->session()s->save($session)) {
                $this->Flash->success('The session has been saved.');
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('The session could not be saved. Please, try again.');
            }
        }
        $this->set(compact('session'));
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
        $session = $this->request->session()s->get($id);
        $this->request->allowMethod(['post', 'delete']);
        if ($this->request->session()s->delete($session)) {
            $this->Flash->success('The session has been deleted.');
        } else {
            $this->Flash->error('The session could not be deleted. Please, try again.');
        }
        return $this->redirect(['action' => 'index']);
    }
}
