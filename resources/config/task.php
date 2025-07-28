<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Task\Feature;
use Tobento\App\Task\Hook;
use Tobento\App\Task\Registry;
use Tobento\App\Task\TaskEntityFactory;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\App\Task\TaskResultRepositoryInterface;
use Tobento\App\Task\TaskResultStorageRepository;
use Tobento\App\Task\TaskStorageRepository;
use Tobento\Service\Database\DatabasesInterface;
use function Tobento\App\{directory};

return [

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Configure the features you wish to use or remove uneeded.
    |
    | See: https://github.com/tobento-ch/app-task#features
    |
    */
    
    'features' => [
        Feature\Tasks::class,
        Feature\ScheduleTasks::class,
        Feature\TaskResults::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Registries
    |--------------------------------------------------------------------------
    |
    | Configure tasks you wish to be scheduable.
    |
    | See: https://github.com/tobento-ch/app-task#available-registries
    |
    */
    
    'registries' => [
        'prune.auth.tokens' => new Registry\CommandTask(
            name: 'Prune Auth Tokens',
            command: 'auth:purge-tokens',
            supportedAppIds: ['root'],
        ),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    |
    | Configure the available hooks.
    |
    | See: https://github.com/tobento-ch/app-task#available-hooks
    |
    */
    
    'hooks' => [
        'save.result' => new Hook\SaveTaskResult(name: 'Save To Task Results'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names as it may be used in other app bundles!
    |
    */
    
    'interfaces' => [
        TaskRepositoryInterface::class =>
        static function(DatabasesInterface $databases, TaskEntityFactory $entityFactory): TaskRepositoryInterface {
            return new TaskStorageRepository(
                storage: $databases->default('storage')->storage()->new(),
                table: 'tasks',
                entityFactory: $entityFactory,
            );
        },
        
        TaskResultRepositoryInterface::class =>
        static function(DatabasesInterface $databases): TaskResultRepositoryInterface {
            return new TaskResultStorageRepository(
                storage: $databases->default('storage')->storage()->new(),
                table: 'task_results',
            );
        },
    ],
    
];