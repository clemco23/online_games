<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    protected bool $requiresAuth = false;

    protected mixed $currentUser = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Sets the current user and protects controllers using $requiresAuth.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->currentUser = $this->request->getAttribute('identity')
            ?? $this->request->getSession()->read('Auth');

        if ($this->currentUser !== null) {
            $this->set('currentUser', $this->currentUser);
        }

        if ($this->requiresAuth && $this->currentUser === null) {
            $response = $this->jsonResponse([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);

            if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                $event->stopPropagation();
                $event->setResult($response);

                return $response;
            }

            $this->Flash->error('Vous devez etre connecte.');

            $response = $this->redirect(['controller' => 'Users', 'action' => 'login']);
            $event->stopPropagation();
            $event->setResult($response);

            return $response;
        }
    }

    /**
     * Shares the authenticated identity with templates.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->set('auth', $this->request->getAttribute('identity'));
    }

    /**
     * Builds a JSON response payload.
     *
     * @param array<string, mixed> $payload Response payload.
     * @param int $status HTTP status code.
     * @return \Cake\Http\Response
     */
    protected function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStatus($status)
            ->withStringBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
