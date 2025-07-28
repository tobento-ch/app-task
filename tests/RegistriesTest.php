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
use Tobento\App\Task\Exception\RegistryNotFoundException;
use Tobento\App\Task\Registry\CommandTask;
use Tobento\App\Task\Registry\Task;
use Tobento\App\Task\Registries;
use Tobento\App\Task\RegistriesInterface;
use Tobento\Service\Container\Container;
use Tobento\Service\Schedule\Task\AbstractTask;
use Tobento\Service\Schedule\Task\CommandTask as Command;

class RegistriesTest extends TestCase
{
    public function testConstructorMethod()
    {
        $registries = new Registries(new Container());
        $this->assertInstanceof(RegistriesInterface::class, $registries);
    }

    public function testAddMethod()
    {
        $registries = new Registries(new Container());
        
        $this->assertSame(0, count($registries->all()));

        $registries->add(id: 'foo', registry: new CommandTask(name: 'Foo', command: 'command'));
        
        $this->assertSame(1, count($registries->all()));
    }
    
    public function testAddMethodUsingClassString()
    {
        $registries = new Registries(new Container());

        $registries->add(id: 'foo', registry: FooTask::class);
        
        $this->assertInstanceof(FooTask::class, $registries->get(id: 'foo'));
    }
    
    public function testHasMethod()
    {
        $registries = new Registries(new Container());
        $this->assertFalse($registries->has(id: 'foo'));
        
        $registries->add(id: 'foo', registry: new CommandTask(name: 'Foo', command: 'command'));
        $this->assertTrue($registries->has(id: 'foo'));
    }
    
    public function testGetMethod()
    {
        $registries = new Registries(new Container());
        $registry = new CommandTask(name: 'Foo', command: 'command');
        $registries->add(id: 'foo', registry: $registry);
        $this->assertTrue($registry === $registries->get('foo'));
    }
    
    public function testGetMethodThrowsRegistryNotFoundException()
    {
        $this->expectException(RegistryNotFoundException::class);
        
        (new Registries(new Container()))->get('foo');
    }
    
    public function testNamesMethod()
    {
        $registries = new Registries(new Container());
        $registries->add(id: 'foo', registry: new CommandTask(name: 'Foo', command: 'command'));
        
        $this->assertSame(['foo' => 'Foo'], $registries->names());
    }
    
    public function testAllMethod()
    {
        $registries = new Registries(new Container());
        $registry = new CommandTask(name: 'Foo', command: 'command');
        $registries->add(id: 'foo', registry: $registry);
        
        $this->assertSame(['foo' => $registry], $registries->all());
    }
}

class FooTask extends Task
{
    protected string $name = 'Foo';
    protected array $supportedAppIds = ['root'];
    protected array $parameters = [];

    public function __construct()
    {}
    
    protected function getTask(): AbstractTask
    {
        return new Command(command: 'foo');
    }
}