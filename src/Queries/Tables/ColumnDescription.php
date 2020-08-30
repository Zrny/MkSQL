<?php
/*
 * Zrník.eu | MkSQL
 * User: Programátor
 * Date: 06.08.2020 7:55
 */

namespace Zrny\MkSQL\Queries\Tables;

use Zrny\MkSQL\Column;
use Zrny\MkSQL\Table;

class ColumnDescription
{
    /**
     * @var bool
     */
    public bool $columnExists = false;

    /**
     * @var Column
     */
    public Column $column;

    /**
     * @var Table
     */
    public Table $table;

    /**
     * @var bool
     */
    public bool $notNull;

    /**
     * @var string|null
     */
    public ?string $comment;

    /**
     * @var string|null
     */
    public ?string $uniqueIndex;

    /**
     * @var string[]
     */
    public array $foreignKeys = [];

    /**
     * @var mixed
     */
    public $default;

    /**
     * @var string
     */
    public string $type;
}
