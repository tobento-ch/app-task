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
use Tobento\App\Task\Controller\TaskResultCrudController;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use function Tobento\App\Translation\trans;

class TaskResults extends Boot
{
    public const INFO = [
        'boot' => [
            'Tasks results CRUD',
        ],
    ];

    public const BOOT = [
        \Tobento\App\User\Boot\Acl::class,
        \Tobento\App\User\Boot\User::class,
        \Tobento\App\User\Boot\HttpUserErrorHandler::class,
        Crud::class,
    ];
    
    /**
     * Create a new TaskResults instance.
     *
     * @param null|string $menu The menu name or null if none.
     * @param string $menuLabel The menu label.
     * @param null|string $menuParent The menu parent or null if none.
     * @param bool $withAcl
     */
    public function __construct(
        protected null|string $menu = 'main',
        protected string $menuLabel = 'Task Results',
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
        // Acl:
        $acl = $app->get(AclInterface::class);
        $acl->rule('tasks.results')->description('User can access task results.');
        
        if ($this->withAcl === false) {
            $acl->addPermissions(['tasks.results']);
        }
        
        // Routes:
        $crud->routeController(
            TaskResultCrudController::class,
            middleware: [
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'task-results.index' => 'tasks.results',
                        'task-results.show' => 'tasks.results',
                        'task-results.delete' => 'tasks.results',
                        'task-results.bulk' => 'tasks.results',
                    ],
                ]
            ],
            except: ['store', 'create', 'update', 'edit', 'copy'],
            localized: true,
        );
        
        // Menu:
        if ($this->menu) {
            $app->on(
                MenusInterface::class,
                function(MenusInterface $menus, AclInterface $acl, RouterInterface $router) {
                    if ($acl->can('tasks.results')) {
                        $menus->menu($this->menu)
                            ->link($router->url('task-results.index'), trans($this->menuLabel))
                            ->parent($this->menuParent)
                            ->id('task.results.index');
                    }
                }
            );
        }
    }
}