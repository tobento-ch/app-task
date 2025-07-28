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
 
namespace Tobento\App\Task\Schedule;

use Butschster\CronExpression\Generator;
use Butschster\CronExpression\Parts\DaysOfWeek\SpecificDaysOfWeek;
use Butschster\CronExpression\Parts\Hours\BetweenHours;
use Butschster\CronExpression\Parts\Minutes\EveryMinute;
use Butschster\CronExpression\Parts\Months\SpecificMonths;
use Stringable;
use Tobento\App\Task\TaskEntityInterface;

class EntityCronExpression implements Stringable
{
    /**
     * Create a new EntityCronExpression instance.
     *
     * @param TaskEntityInterface $entity
     */
    final public function __construct(
        private TaskEntityInterface $entity,
    ) {}

    /**
     * Returns the cron expression.
     *
     * @return string
     */
    public function __toString(): string
    {
        $generator = Generator::create();
        
        if ($time = $this->entity->data('frequency.time', '')) {
            [$h, $m] = array_pad(explode(':', $time, 2), 2, 0);
            $h = (int)$h;
            $h = $h > 23 || $h < 0 ? 0 : $h;
            $m = (int)$m;
            $m = $m > 59 || $m < 0 ? 0 : $m;
            $generator = $generator->dailyAt($h, $m);
        }
        
        $hourly = $this->entity->data('frequency.hourly', '');
        
        if ($hourly !== '') {
            $h = (int)$hourly;
            $h = $h > 59 || $h < 0 ? 0 : $h;
            $generator = $generator->hourlyAt($h);
        }
        
        $minutely = (int)$this->entity->data('frequency.minutely', '');
        
        if ($minutely > 0) {
            $generator = $generator->set(new EveryMinute((int)$this->entity->get('data.frequency.minutely')));
        }
        
        $from = $this->entity->data('frequency.between.from', '');
        $to = $this->entity->data('frequency.between.to', '');
        
        if ($from && $to) {
            [$fh] = explode(':', $from);
            [$th] = explode(':', $to);
            $generator = $generator->set(new BetweenHours((int)$fh, (int)$th));
        }
        
        if ($days = $this->entity->data('frequency.days', [])) {
            $days = array_map(fn (mixed $d): int => (int)$d, $days);
            $generator = $generator->set(new SpecificDaysOfWeek(...$days));
        }
        
        if ($months = $this->entity->data('frequency.months', [])) {
            $months = array_map(fn (mixed $m): int => (int)$m, $months);
            $generator = $generator->set(new SpecificMonths(...$months));
        }
        
        return (string)$generator;
    }
}