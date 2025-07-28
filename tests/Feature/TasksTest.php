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
use Tobento\App\Task\Feature\Tasks;

class TasksTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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
        return \Tobento\App\Task\Controller\TaskCrudController::class;
    }
    
    public function testIndexAction()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Tasks')
            ->assertCrudIndexHeaderColumnsExists(columns: ['id', 'name', 'status', 'registry_id', 'app_ids', 'actions'])
            ->assertCrudIndexEntityCount(2);
    }
    
    public function testIndexActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks" permission.');
    }
    
    public function testCreateAction()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('New Task')
            ->assertCrudFormFieldExists(field: 'name')
            ->assertCrudFormFieldExists(field: 'registry_id');
    }
    
    public function testCreateActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.create" permission.');
    }

    public function testStoreAction()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'name' => 'Foo',
            'registry_id' => 'prune.auth.tokens',
        ]);

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);

        $this->assertSame(1, $this->getCrudRepository()->count());
        
        $this->assertSame('Foo', $this->getCrudRepository()->findById(1)->name());
    }
    
    public function testStoreActionFailsIfRegistryNotExists()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'name' => 'Foo',
            'registry_id' => 'unknown',
        ]);

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'registry_id', errorText: 'The registry_id items are invalid.');
    }
    
    public function testStoreActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'name' => 'Foo',
            'registry_id' => 'prune.auth.tokens',
        ]);

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.create" permission.');
    }

    public function testEditAction()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Edit Task')
            ->assertCrudFormFieldExists(field: 'name')
            ->assertCrudFormFieldExists(field: 'registry_id')
            ->assertCrudFormFieldExists(field: 'status')
            ->assertCrudFormFieldExists(field: 'data.timezone')
            ->assertCrudFormFieldExists(field: 'data.cron')
            ->assertCrudFormFieldExists(field: 'data.frequency.time')
            ->assertCrudFormFieldExists(field: 'data.task_before')
            ->assertCrudFormFieldExists(field: 'data.task_after')
            ->assertCrudFormFieldExists(field: 'data.task_failed');
    }
    
    public function testEditActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(1)->create();
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.edit" permission.');
    }
    
    public function testUpdateAction()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'name' => 'Foo',
            'status' => 'active',
            'app_ids' => ['root'],
            'data' => [
                'timezone' => 'Europe/Berlin',
                'monitor' => '1',
            ]
        ]);
        
        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(1)->create();
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);
        
        $this->assertSame('Foo', $this->getCrudRepository()->findById(1)->name());
    }
    
    public function testUpdateActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'name' => 'Foo',
            'status' => 'active',
            'app_ids' => ['root'],
            'data' => [
                'timezone' => 'Europe/Berlin',
                'monitor' => '1',
            ]
        ]);
        
        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(1)->create();
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.edit" permission.');
    }
    
    public function testShowActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1));
        
        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testCopyActionIsDisabled()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testDeleteAction()
    {
        $this->fakeConfig()->with('task.features', [
            new Tasks(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(2)->create();

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

        $this->getSeedFactory(['registry_id' => 'prune.auth.tokens'])->times(2)->create();

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "tasks.delete" permission.');
    }
}