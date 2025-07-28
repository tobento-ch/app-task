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

class TaskResultsTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Task\Boot\Task::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Task\Controller\TaskResultCrudController::class;
    }
    
    public function testIndexAction()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Task Results')
            ->assertCrudIndexHeaderColumnsExists(columns: [
                'task_id', 'status', 'run_at', 'runtime_seconds', 'memory_usage_bytes', 'actions',
            ])
            ->assertCrudIndexEntityCount(2);
    }
    
    public function testIndexActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.results" permission.');
    }
    
    public function testCreateActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()->assertStatus(404);
    }
    
    public function testStoreActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([]);

        $http->response()->assertStatus(404);
    }

    public function testEditActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([]);
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testShowAction()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1));
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Task Result')
            ->assertCrudFormFieldExists(field: 'task_id')
            ->assertCrudFormFieldExists(field: 'status')
            ->assertCrudFormFieldExists(field: 'run_at')
            ->assertCrudFormFieldExists(field: 'runtime_seconds')
            ->assertCrudFormFieldExists(field: 'memory_usage_bytes')
            ->assertCrudFormFieldExists(field: 'result');
    }
    
    public function testCopyActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testDeleteAction()
    {
        $this->fakeConfig()->with('task.features', [
            new TaskResults(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

        $this->getSeedFactory()->times(2)->create();

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testDeleteActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

        $this->getSeedFactory()->times(2)->create();

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.results" permission.');
    }
}