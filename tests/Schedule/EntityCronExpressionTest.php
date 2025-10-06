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

namespace Tobento\App\Task\Test\Schedule;

use PHPUnit\Framework\TestCase;
use Tobento\App\Task\Schedule\EntityCronExpression;
use Tobento\App\Task\TaskEntity;

class EntityCronExpressionTest extends TestCase
{
    public function testTime()
    {
        $this->assertSame(
            '34 12 * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['time' => '12:34']]])),
        );
        
        $this->assertSame(
            '0 0 * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['time' => 566]]])),
        );
    }
    
    public function testHourly()
    {
        $this->assertSame(
            '25 * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['hourly' => '25']]])),
        );
        
        $this->assertSame(
            '25 * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['hourly' => 25]]])),
        );
        
        $this->assertSame(
            '* * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['hourly' => []]]])),
        );
    }
    
    public function testMinutely()
    {
        $this->assertSame(
            '*/5 * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['minutely' => '5']]])),
        );
        
        $this->assertSame(
            '*/30 * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['minutely' => 30]]])),
        );
        
        $this->assertSame(
            '* * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['minutely' => []]]])),
        );
    }
    
    public function testBetweenTimes()
    {
        $this->assertSame(
            '* 5-14 * * *',
            (string)new EntityCronExpression(
                new TaskEntity(['data' => ['frequency' => ['between' => ['from' => '05:12', 'to' => '14:12']]]])
            ),
        );
        
        $this->assertSame(
            '* * * * *',
            (string)new EntityCronExpression(
                new TaskEntity(['data' => ['frequency' => ['between' => ['from' => '05:12', 'to' => []]]]])
            ),
        );
    }
    
    public function testDays()
    {
        $this->assertSame(
            '* * * * 2,5',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['days' => [2,5]]]])),
        );
        
        $this->assertSame(
            '* * * * 2,5',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['days' => ['2','5']]]])),
        );
        
        $this->assertSame(
            '* * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['days' => 'foo']]])),
        );
    }
    
    public function testMonths()
    {
        $this->assertSame(
            '* * * 3,8 *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['months' => ['3','8']]]])),
        );
        
        $this->assertSame(
            '* * * 3,8 *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['months' => [3,8]]]])),
        );
        
        $this->assertSame(
            '* * * * *',
            (string)new EntityCronExpression(new TaskEntity(['data' => ['frequency' => ['months' => 'foo']]])),
        );
    }
}