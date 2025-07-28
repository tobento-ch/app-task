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

namespace Tobento\App\Task\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Task\TaskEntity;
use Tobento\App\Task\TaskEntityInterface;

class TaskEntityTest extends TestCase
{
    public function testThatImplementsTaskEntityInterface()
    {
        $this->assertInstanceof(TaskEntityInterface::class, new TaskEntity());
    }
    
    public function testIdMethod()
    {
        $this->assertSame(0, (new TaskEntity([]))->id());
        $this->assertSame(3, (new TaskEntity(['id' => 3]))->id());
        $this->assertSame(3, (new TaskEntity(['id' => '3']))->id());
    }
    
    public function testTaskIdMethod()
    {
        $this->assertSame('0:', (new TaskEntity([]))->taskId());
        $this->assertSame('3:', (new TaskEntity(['id' => 3, 'registry_id' => '']))->taskId());
        $this->assertSame('2:foo', (new TaskEntity(['id' => 2, 'registry_id' => 'foo']))->taskId());
    }
    
    public function testStatusMethod()
    {
        $this->assertSame('', (new TaskEntity([]))->status());
        $this->assertSame('foo', (new TaskEntity(['status' => 'foo']))->status());
    }
    
    public function testRegistryIdMethod()
    {
        $this->assertSame('', (new TaskEntity([]))->registryId());
        $this->assertSame('foo', (new TaskEntity(['registry_id' => 'foo']))->registryId());
    }
    
    public function testDataMethod()
    {
        $entity = new TaskEntity([
            'data' => [
                'foo' => 'Foo',
                'bar' => ['baz' => 'Baz'],
            ],
        ]);
        
        $this->assertSame('Foo', $entity->data(name: 'foo'));
        $this->assertSame('Baz', $entity->data(name: 'bar.baz'));
        $this->assertSame(null, $entity->data(name: 'baz'));
        $this->assertSame('default', $entity->data(name: 'baz', default: 'default'));
    }
    
    public function testAppIdsMethod()
    {
        $this->assertSame([], (new TaskEntity([]))->appIds());
        $this->assertSame([], (new TaskEntity(['app_ids' => 'foo']))->appIds());
        $this->assertSame(['foo'], (new TaskEntity(['app_ids' => ['foo']]))->appIds());
    }

    public function testHasMethod()
    {
        $entity = new TaskEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertTrue($entity->has(name: 'foo'));
        $this->assertTrue($entity->has(name: 'bar.baz'));
        $this->assertFalse($entity->has(name: 'baz'));
    }
    
    public function testGetMethod()
    {
        $entity = new TaskEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertSame('Foo', $entity->get(name: 'foo'));
        $this->assertSame('Baz', $entity->get(name: 'bar.baz'));
        $this->assertSame(null, $entity->get(name: 'baz'));
        $this->assertSame('default', $entity->get(name: 'baz', default: 'default'));
    }
    
    public function testToArrayMethod()
    {
        $entity = new TaskEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertSame([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
            'task_id' => '0:',
        ], $entity->toArray());
    }
}