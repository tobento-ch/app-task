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

namespace Tobento\App\Task\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Task\Feature\TaskResults;
use Tobento\App\Task\Feature\Tasks;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\App\Task\TaskResultRepositoryInterface;

class TaskRunTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Task\Boot\Task::class);
        return $app;
    }
    
    public function testRunTask()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
            new TaskResults(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'tasks/1/run');

        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'registry_id' => 'prune.auth.tokens',
            'app_ids' => ['root'],
            'data' => ['task_after' => ['save.result']],
        ]);
        
        $http->response()->assertStatus(302)->assertLocation('tasks');
        $http->followRedirects()->assertStatus(200);
        
        $result = $app->get(TaskResultRepositoryInterface::class)->findById(1);
        $this->assertSame('root:1:prune.auth.tokens', $result?->get('task_id'));
        $this->assertSame('successful', $result?->get('status'));
    }
    
    public function testRunTaskFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'tasks/1/run');

        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'registry_id' => 'prune.auth.tokens',
            'app_ids' => ['root'],
        ]);
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.run" permission.');
    }
    
    public function testRunTaskFailsIfTaskNotFound()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'tasks/1/run');
        
        $http->response()->assertStatus(404);
    }
    
    public function testRunTaskFailsIfRegistryNotFound()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'tasks/1/run');

        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'registry_id' => 'unknown',
            'app_ids' => ['root'],
        ]);
        
        $http->response()->assertStatus(404);
    }
}