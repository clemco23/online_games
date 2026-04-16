<?php 

namespace App\Controller;

class BoardgameInstructionsController extends AppController
{
    public function view($id = null)
    {
        $boardgameInstruction = $this->BoardgameInstructions->get($id, ['contain' => ['Boardgames']]);
        $this->set(compact('boardgameInstruction'));
    }
}