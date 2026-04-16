<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Boardgames Controller
 *
 * @property \App\Model\Table\BoardgamesTable $Boardgames
 */
class BoardgamesController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['index', 'view']);
    }
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $boardgames = $this->Boardgames->find()
            ->orderBy(['Boardgames.id' => 'ASC'])
            ->all();

        $seenLabyrinth = false;
        $filteredBoardgames = [];

        foreach ($boardgames as $boardgame) {
            if (!in_array($boardgame->name, ['Labyrinth', 'Labyrinthe'], true)) {
                $filteredBoardgames[] = $boardgame;
                continue;
            }

            if ($seenLabyrinth) {
                continue;
            }

            $seenLabyrinth = true;
            $filteredBoardgames[] = $boardgame;
        }

        $boardgames = $filteredBoardgames;

        $this->set(compact('boardgames'));
    }

    /**
     * View method
     *
     * @param string|null $id Boardgame id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $boardgame = $this->Boardgames->get($id, contain: []);
        $this->set(compact('boardgame'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $boardgame = $this->Boardgames->newEmptyEntity();
        if ($this->request->is('post')) {
            $boardgame = $this->Boardgames->patchEntity($boardgame, $this->request->getData());
            if ($this->Boardgames->save($boardgame)) {
                $this->Flash->success(__('The boardgame has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The boardgame could not be saved. Please, try again.'));
        }
        $this->set(compact('boardgame'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Boardgame id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $boardgame = $this->Boardgames->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $boardgame = $this->Boardgames->patchEntity($boardgame, $this->request->getData());
            if ($this->Boardgames->save($boardgame)) {
                $this->Flash->success(__('The boardgame has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The boardgame could not be saved. Please, try again.'));
        }
        $this->set(compact('boardgame'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Boardgame id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $boardgame = $this->Boardgames->get($id);
        if ($this->Boardgames->delete($boardgame)) {
            $this->Flash->success(__('The boardgame has been deleted.'));
        } else {
            $this->Flash->error(__('The boardgame could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
    public function infos($id = null)
    { if(empty($id))
            return $this->redirect(['action' => 'index']);
        //on recupere le boardgame voulue 
        $boardgame = $this->Boardgames->findById($id)->contain(['BoardgameInstructions']);

        if($boardgame->count() == 0) :
            $this->Flash->error('sorry, ça n\'existe pas');
            return $this->redirect(['action' => 'index']);
        endif;

        $boardgame = $boardgame->first();


        //on transmet a la vue 
        $this->set(compact('boardgame'));
    }
}
