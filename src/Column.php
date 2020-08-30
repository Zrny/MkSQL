<?php
/*
 * Zrník.eu | MkSQL
 * User: Programátor
 * Date: 31.07.2020 9:38
 */


namespace Zrny\MkSQL;

use InvalidArgumentException;
use LogicException;
use Nette\NotImplementedException;
use Zrny\MkSQL\Queries\Tables\ColumnDescription;
use Zrny\MkSQL\Queries\Tables\TableDescription;

class Column
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Table
     */
    private $parent;

    /**
     * Column constructor.
     * @param string $colName
     * @param Table $parent
     * @param string $colType
     */
    public function __construct(string $columnName, string $columnType = "int")
    {
        $this->name = $columnName;
        $this->type = $columnType;
    }

    /**
     * Returns back to parent table.
     * @return Table
     */
    public function endColumn(): Table
    {
        return $this->parent;
    }

    /**
     * Returns column name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns column type.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    //region Unique

    /**
     * @var bool
     */
    private $unique = false;

    /**
     * Sets column to be unique or not
     * @param bool $Unique
     * @return $this
     */
    public function setUnique(bool $Unique = true): Column
    {
        //Unique must be NotNull
        $this->setNotNull();
        $this->unique = $Unique;
        return $this;
    }

    /**
     * Is column unique?
     * @return bool
     */
    public function getUnique(): bool
    {
        return $this->unique;
    }
    //endregion

    //region NOT NULL
    /**
     * @var bool
     */
    private $NotNull = false;

    /**
     * Sets column to be NOT NULL or can be NULL
     * @param bool $notNull
     * @return $this
     */
    public function setNotNull(bool $notNull = true): Column
    {
        $this->NotNull = $notNull;
        return $this;
    }

    /**
     * Is column NOT NULL?
     * @return bool
     */
    public function getNotNull(): bool
    {
        return $this->NotNull;
    }
    //endregion

    //region Default Value
    /**
     * @var mixed|null
     */
    private $default = null;

    /**
     * Set or unset (with null) default value of column.
     * @param mixed|null $defaultValue
     * @return $this
     */
    public function setDefault($defaultValue): Column
    {
        $this->default = $defaultValue;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getDefault()
    {
        return $this->default;
    }
    //endregion

    //region Foreign Keys
    /**
     * @var string[]
     */
    private $foreignKeys = [];

    /**
     * Add foreign key on column
     * @param string $foreignKey
     * @return Column
     */
    public function addForeignKey(string $foreignKey): Column
    {
        $foreignKey = Utils::confirmName($foreignKey, ["."]);
        $setForeignException = new LogicException("Foreign key needs to target another table. Use dot. (E.g. 'TableName.ColumnName')");
        $exploded = explode(".", $foreignKey);

        if (count($exploded) != 2)
            throw $setForeignException;

        if (strlen($exploded[0]) <= 0 || strlen($exploded[1]) <= 0)
            throw $setForeignException;

        if (in_array($foreignKey, $this->foreignKeys))
            throw new InvalidArgumentException("Foreign key '" . $foreignKey . "' already exist on column '" . $this->getName() . "'!");

        $this->foreignKeys[] = $foreignKey;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }
    //endregion

    //region Comment
    /**
     * @var string|null
     */
    private $comment = 'mksql handled';

    /**
     * Set or unset (with null) comment string for column
     * @param string|null $commentString
     * @return $this
     */
    public function setComment(?string $commentString): Column
    {
        $commentString = Utils::confirmName($commentString, [".", ",", " "]); //Allow dots, commas and spaces to form sentences :)
        $this->comment = $commentString;
        return $this;
    }

    /**
     * Returns string that was set as a comment.
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    //endregion

    public function install(TableDescription $tdesc, ?ColumnDescription $desc): array
    {
        $Commands = [];

        if($desc === null || !$desc->columnExists)
        {
            $Commands = array_merge($Commands, $tdesc->queryMakerClass::createTableColumnQuery($tdesc->table, $this, $tdesc, $desc));

            try{
                foreach($this->getForeignKeys() as $foreignKey)
                    $Commands = array_merge($Commands, $tdesc->queryMakerClass::createForeignKey($tdesc->table, $this, $foreignKey, $tdesc, $desc));
            }
            catch (NotImplementedException $ex)
            {
                // TODO: Document this behavior
            }
            if($this->getUnique())
                $Commands = array_merge($Commands, $tdesc->queryMakerClass::createUniqueIndexQuery($tdesc->table, $this, $tdesc, $desc));
        }
        else
        {
            $Reasons = [];
            //Utils::typeEquals($desc->type, $this->getType())
            if(!$tdesc->queryMakerClass::compareType($desc->type, $this->getType()))
                $Reasons[] = "type different [".$desc->type." != ".$this->getType()."]";

            if($desc->notNull !== $this->getNotNull())
                $Reasons[] = "not_null [is: ".($desc->notNull?"yes":"no")." need:".($this->getNotNull()?"yes":"no")."]";

            //$desc->comment != $this->getComment()
            if(!$tdesc->queryMakerClass::compareComment($desc->comment, $this->getComment()))
                $Reasons[] = "comment [".$desc->comment." != ".$this->getComment()."]";

            if($desc->default != $this->getDefault())
                $Reasons[] = "default [".$desc->default." != ".$this->getDefault()."]";

            if(count($Reasons) > 0)
            {
                $Queries = $tdesc->queryMakerClass::alterTableColumnQuery($desc->table, $desc->column, $tdesc, $desc);
                foreach($Queries as $alterQuery)
                    $alterQuery->reason .= "\n<br>"."Reasons: ".implode(", ",$Reasons);

                $Commands = array_merge($Commands, $Queries);
            }

            //Foreign Keys to Delete:
            try
            {
                if(count($desc->foreignKeys) > 0)
                {
                    foreach($desc->foreignKeys as $existingForeignKey => $foreignKeyName)
                    {
                        if(!in_array($existingForeignKey,$this->getForeignKeys())
                        )
                        {
                            $Commands = array_merge($Commands, $tdesc->queryMakerClass::removeForeignKey($desc->table, $desc->column, $foreignKeyName, $tdesc, $desc));
                        }
                    }
                }
            }
            catch(NotImplementedException $ex)
            {
                // Foreign Keys not implemented,
                // TODO: Document this behavior
            }

            //Foreign Keys to Add:
            try
            {
                foreach($this->getForeignKeys() as $requiredForeignKey)
                {
                    if(!isset($desc->foreignKeys[$requiredForeignKey]))
                    {
                        $Commands = array_merge($Commands, $tdesc->queryMakerClass::createForeignKey($desc->table, $desc->column, $requiredForeignKey, $tdesc, $desc));
                    }
                }
            }
            catch(NotImplementedException $ex)
            {
                // Foreign Keys not implemented,
                // TODO: Document this behavior
            }

            // Unique?
            if($this->getUnique())
            {
                //Must be unique
                if($desc->uniqueIndex === null)
                {
                    $Commands = array_merge($Commands, $tdesc->queryMakerClass::createUniqueIndexQuery($desc->table, $desc->column, $tdesc, $desc));
                }
            }
            else
            {
                //Must not be unique
                if($desc->uniqueIndex !== null)
                {
                    $Commands = array_merge($Commands, $tdesc->queryMakerClass::removeUniqueIndexQuery($desc->table, $desc->column, $desc->uniqueIndex, $tdesc, $desc));
                }
            }
        }
        return $Commands;
    }
}
