<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Task\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Task\Controller\TaskCrudController;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use function Tobento\App\Translation\trans;

class Tasks extends Boot
{
    public const INFO = [
        'boot' => [
            'implements task repository interfaces',
            'routes tasks',
            'schedules tasks',
        ],
    ];

    public const BOOT = [
        \Tobento\App\User\Boot\Acl::class,
        \Tobento\App\User\Boot\User::class,
        \Tobento\App\User\Boot\HttpUserErrorHandler::class,
        Crud::class,
    ];
    
    /**
     * Create a new Tasks instance.
     *
     * @param null|string $menu The menu name or null if none.
     * @param string $menuLabel The menu label.
     * @param null|string $menuParent The menu parent or null if none.
     * @param bool $withAcl
     */
    public function __construct(
        protected null|string $menu = 'main',
        protected string $menuLabel = 'Tasks',
        protected null|string $menuParent = null,
        protected bool $withAcl = true,
    ) {}

    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @param Crud $crud
     * @return void
     */
    public function boot(AppInterface $app, Crud $crud): void
    {
        $acl = $app->get(AclInterface::class);
        $acl->rule('tasks')->description('User can access tasks.');
        $acl->rule('tasks.create')->description('User can create tasks.');
        $acl->rule('tasks.edit')->description('User can edit tasks.');
        $acl->rule('tasks.delete')->description('User can delete tasks.');
        $acl->rule('tasks.run')->description('User can run tasks.');
        
        if ($this->withAcl === false) {
            $acl->addPermissions(['tasks', 'tasks.create', 'tasks.edit', 'tasks.delete', 'tasks.run']);
        }

        // Routes:
        $router = $app->get(RouterInterface::class);
        
        $crud->routeController(
            TaskCrudController::class,
            middleware: [
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'tasks.index' => 'tasks',
                        'tasks.show' => 'tasks',
                        'tasks.create' => 'tasks.create',
                        'tasks.store' => 'tasks.create',
                        'tasks.copy' => 'tasks.create',
                        'tasks.edit' => 'tasks.edit',
                        'tasks.update' => 'tasks.edit',
                        'tasks.delete' => 'tasks.delete',
                        'tasks.bulk' => 'tasks.edit|tasks.delete',
                    ],
                ]
            ],
            except: ['show', 'copy'],
            localized: true,
        );
        
        $router->post(
            uri: '{?locale}/tasks/{id}/run',
            handler: \Tobento\App\Task\Action\TaskRunAction::class,
        )->name('tasks.run')
         ->middleware(['can', 'permission' => 'tasks.run']);
        
        // Menu:
        if ($this->menu) {
            $app->on(
                MenusInterface::class,
                function(MenusInterface $menus, AclInterface $acl, RouterInterface $router) {
                    if ($acl->can('tasks')) {
                        $menus->menu($this->menu)
                            ->link($router->url('tasks.index'), trans($this->menuLabel))
                            ->parent($this->menuParent)
                            ->id('tasks.index');
                    }
                }
            );
        }
    }
}