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
            new Column\Id(),
            new Column\Text('status')->type(length: 100),
            new Column\Text('task_id'),
            new Column\Datetime('run_at'),
            new Column\FloatCol('runtime_seconds')
                ->type(nullable: true, precision: 3)
                ->read(fn (null|float $value): float => is_null($value) ? 0 : round($value, 3)),
            new Column\Text('memory_usage_bytes')->type(nullable: true),
            new Column\Text(name: 'result', type: 'text'),
        ];
    }
}