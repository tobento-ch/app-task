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
use Tobento\App\Task\Exception\HookNotFoundException;
use Tobento\App\Task\Hook\Mail;
use Tobento\App\Task\Hooks;
use Tobento\App\Task\HooksInterface;

require 'function-trans.php';

class HooksTest extends TestCase
{
    public function testConstructorMethod()
    {
        $hooks = new Hooks();
        $this->assertInstanceof(HooksInterface::class, $hooks);
    }

    public function testAddMethod()
    {
        $hooks = new Hooks();
        
        $this->assertSame(0, count($hooks->all()));

        $hooks->add(id: 'foo', hook: new Mail(name: 'Foo', email: 'foo@example.com'));
        
        $this->assertSame(1, count($hooks->all()));
    }
    
    public function testHasMethod()
    {
        $hooks = new Hooks();
        $this->assertFalse($hooks->has(id: 'foo'));
        
        $hooks->add(id: 'foo', hook: new Mail(name: 'Foo', email: 'foo@example.com'));
        $this->assertTrue($hooks->has(id: 'foo'));
    }
    
    public function testGetMethod()
    {
        $hooks = new Hooks();
        $hook = new Mail(name: 'Foo', email: 'foo@example.com');
        $hooks->add(id: 'foo', hook: $hook);
        $this->assertTrue($hook === $hooks->get('foo'));
    }
    
    public function testGetMethodThrowsHookNotFoundException()
    {
        $this->expectException(HookNotFoundException::class);
        
        (new Hooks())->get('foo');
    }
    
    public function testTypeMethod()
    {
        $hooks = new Hooks();
        $hooks->add(id: 'foo', hook: new Mail(name: 'Foo', email: 'foo@example.com'));
        
        $hooksNew = $hooks->type('before');
        
        $this->assertFalse($hooks === $hooksNew);
        $this->assertSame(1, count($hooks->all()));
        $this->assertSame(1, count($hooksNew->all()));
    }
    
    public function testNamesMethod()
    {
        $hooks = new Hooks();
        $hooks->add(id: 'foo', hook: new Mail(name: 'Foo', email: 'foo@example.com'));
        
        $this->assertSame(['foo' => 'Foo'], $hooks->names());
    }
    
    public function testAllMethod()
    {
        $hooks = new Hooks();
        $hook = new Mail(name: 'Foo', email: 'foo@example.com');
        $hooks->add(id: 'foo', hook: $hook);
        
        $this->assertSame(['foo' => $hook], $hooks->all());
    }
}