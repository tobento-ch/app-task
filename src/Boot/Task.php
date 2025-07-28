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
 
namespace Tobento\App\Task\Boot;

use Psr\Container\ContainerInterface;
use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Task\Hooks;
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\Registries;
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\Service\Schedule\ScheduleInterface;

class Task extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads task config',
            'implements tasks interface',
            'boots features',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
        \Tobento\App\Database\Boot\Database::class,
        \Tobento\App\Schedule\Boot\Schedule::class,
    ];

    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(
        Config $config,
        Migration $migration,
    ): void {
        // Migration:
        $migration->install(\Tobento\App\Task\Migration\Task::class);
        
        // Load the config:
        $config = $config->load('task.php');
        
        // Interfaces:
        foreach($config['interfaces'] ?? [] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
        
        $this->app->set(RegistriesInterface::class, static function (ContainerInterface $container) use ($config) {
            $registries = new Registries(container: $container);
            
            foreach($config['registries'] ?? [] as $id => $registry) {
                $registries->add(id: $id, registry: $registry);
            }
            
            return $registries;
        });
        
        $this->app->set(HooksInterface::class, static function () use ($config) {
            $hooks = new Hooks();
            
            foreach($config['hooks'] ?? [] as $id => $hook) {
                $hooks->add(id: $id, hook: $hook);
            }
            
            return $hooks;
        });
        
        // Migrate task repositories:
        $migration->install(\Tobento\App\Task\Migration\TaskRepositories::class);
        
        // Features:
        foreach($config['features'] ?? [] as $feature) {
            if (is_string($feature)) {
                $feature = $this->app->make($feature);
            }
            
            $this->app->boot($feature);
        }
    }
}