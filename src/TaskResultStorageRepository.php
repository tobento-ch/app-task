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
 
namespace Tobento\App\Task;

use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\StorageRepository;

class TaskResultStorageRepository extends StorageRepository implements TaskResultRepositoryInterface
{
    /**
     * Returns the configured columns.
     *
     * @return iterable<ColumnInterface>|ColumnsInterface
     */
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            Column\Id::new(),
            Column\Text::new('status')->type(length: 100),
            Column\Text::new('task_id'),
            Column\Datetime::new('run_at'),
            Column\FloatCol::new('runtime_seconds')
                ->type(nullable: true, precision: 3)
                ->read(fn (null|float $value): float => is_null($value) ? 0 : round($value, 3)),
            Column\Text::new('memory_usage_bytes')->type(nullable: true),
            Column\Text::new(name: 'result', type: 'text'),
        ];
    }
}