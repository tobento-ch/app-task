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
 
namespace Tobento\App\Task\Crud;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\Service\Dater\DateFormatter;
use function Tobento\App\Translation\trans;

class FrequencyFields extends Field\Group
{
    protected function configure(): void
    {
        $this->group(trans('Frequencies'));
        $this->fields();
    }

    public function fields(FieldInterface ...$fields): static
    {
        $this->fields = [
            new Field\Text(name: 'data.frequency.time', label: trans('At a certain time'))
                ->type('time')
                ->validate([
                    'string',
                    ['dateFormat', ['H:i']],
                ]),
            
            new Field\Text(name: 'data.frequency.hourly', label: trans('Hourly'))
                ->validate('minNum:0|maxNum:59')
                ->infoText(trans('Hourly to the minute: 0-59')),
            
            new Field\Text(name: 'data.frequency.minutely', label: trans('Every x minute(s)'))
                ->validate('minNum:1|maxLen:10'),
            
            new Field\Group(name: 'data.frequency.between', label: trans('Between times'))
                ->fields(
                    new Field\Text(name: 'from', label: trans('From'))
                        ->type('time')
                        ->attributes(['step' => '3600'])
                        ->validate([
                            'string',
                            ['dateFormat', ['H:i']],
                        ]),
                    new Field\Text(name: 'to', label: trans('To'))
                        ->type('time')
                        ->attributes(['step' => '3600'])
                        ->validate([
                            'string',
                            ['dateFormat', ['H:i']],
                        ]),
                )
                ->displayLabel()
                ->prependGroupName()
                ->displayAsField()
                ->displayAsCard(),
            
            new Field\Checkboxes(name: 'data.frequency.days', label: trans('Only on the days'))
                ->options(function(DateFormatter $df): array {
                    return [
                        1 => $df->toWeekday(number: 1, pattern: 'EEEE'),
                        2 => $df->toWeekday(number: 2, pattern: 'EEEE'),
                        3 => $df->toWeekday(number: 3, pattern: 'EEEE'),
                        4 => $df->toWeekday(number: 4, pattern: 'EEEE'),
                        5 => $df->toWeekday(number: 5, pattern: 'EEEE'),
                        6 => $df->toWeekday(number: 6, pattern: 'EEEE'),
                        0 => $df->toWeekday(number: 0, pattern: 'EEEE'),
                    ];
                }),
            
            new Field\Checkboxes(name: 'data.frequency.months', label: trans('Only in the months'))
                ->options(function(DateFormatter $df): array {
                    return [
                        1 => $df->toMonth(number: 1, pattern: 'EEEE'),
                        2 => $df->toMonth(number: 2, pattern: 'EEEE'),
                        3 => $df->toMonth(number: 3, pattern: 'EEEE'),
                        4 => $df->toMonth(number: 4, pattern: 'EEEE'),
                        5 => $df->toMonth(number: 5, pattern: 'EEEE'),
                        6 => $df->toMonth(number: 6, pattern: 'EEEE'),
                        7 => $df->toMonth(number: 7, pattern: 'EEEE'),
                        8 => $df->toMonth(number: 8, pattern: 'EEEE'),
                        9 => $df->toMonth(number: 9, pattern: 'EEEE'),
                        10 => $df->toMonth(number: 10, pattern: 'EEEE'),
                        11 => $df->toMonth(number: 11, pattern: 'EEEE'),
                        12 => $df->toMonth(number: 12, pattern: 'EEEE'),
                    ];
                }),
        ];
        
        return $this;
    }
}