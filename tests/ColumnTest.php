<?php declare(strict_types=1);

/*
 * Zrník.eu | MkSQL  
 * User: Programátor
 * Date: 31.08.2020 15:45
 */


use Mock\MockSQLMaker_ExistingTable_First;
use Zrny\MkSQL\Column;
use PHPUnit\Framework\TestCase;
use Zrny\MkSQL\Exceptions\ColumnDefinitionExists;
use Zrny\MkSQL\Exceptions\PrimaryKeyAutomaticException;
use Zrny\MkSQL\Table;

class ColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstructor()
    {
        $column = new Column("tested");
        $this->addToAssertionCount(1);

        $column = new Column("tested","text");
        $this->addToAssertionCount(1);

        $column = new Column("tested","something(10, 20, 30)");
        $this->addToAssertionCount(1);

        try
        {
            $column = new Column("tested.table","text");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }

        try
        {
            $column = new Column("tested","text.value");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }
    }

    public function testGetName()
    {
        $tested1 = new Column("tested_column_1");
        $this->assertSame(
            "tested_column_1",
            $tested1->getName()
        );

        $tested2 = new Column("testing_different_value");
        $this->assertSame(
            "testing_different_value",
            $tested2->getName()
        );
    }

    /**
     * @throws Exception
     */
    public function testForeignKeys()
    {
        $column = new Column("tested");

        $this->assertSame(
            [],
            $column->getForeignKeys()
        );

        $column->addForeignKey("table.column");

        $this->assertSame(
            [
                "table.column"
            ],
            $column->getForeignKeys()
        );

        $column->addForeignKey("account.id");

        $this->assertSame(
            [
                "table.column",
                "account.id"
            ],
            $column->getForeignKeys()
        );

        //Already Exists
        try
        {
            $column->addForeignKey("table.column");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }


        //database in identifier not supported (is it even possible?)
        try
        {
            $column->addForeignKey("database.table.column");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }


        //database in identifier not supported (is it even possible?)
        try
        {
            $column->addForeignKey("database");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @throws Exception
     */
    public function testComments()
    {
        $column = new Column("tested");

        $this->assertNull($column->getComment());

        $column->setComment("Hello World");

        $this->assertSame(
            "Hello World",
            $column->getComment()
        );


        $column->setComment("Its possible to form sentences, even with comma.");

        $this->assertSame(
            "Its possible to form sentences, even with comma.",
            $column->getComment()
        );

        //No argument will reset (or when u set argument as null!)
        $column->setComment();
        $this->assertNull($column->getComment());

        // only  A-Z a-z 0-9 underscore comma space allowed!
        try
        {
            $column->setComment("It works!");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }

        try
        {
            $column->setComment("Really?");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }
    }


    /**
     * @throws Exception
     */
    public function testTypes()
    {
        //default type is INT!
        $this->assertSame(
            "int",
            (new Column("hello"))->getType()
        );

        //It should save and then return it
        $this->assertSame(
            "longtext",
            (new Column("hello", "longtext"))->getType()
        );

        //allowed stuff and removed spaces
        // az AZ 09 (),
        $this->assertSame(
            "something(10,11,12)",
            (new Column("hello", "something (10, 11, 12) "))->getType()
        );


        //something invalid in type:
        try
        {
            new Column("hello","world.invalid");
            throw new Exception("Expected exception ".\Zrny\MkSQL\Exceptions\InvalidArgumentException::class." not thrown!");
        }
        catch(\Zrny\MkSQL\Exceptions\InvalidArgumentException $_)
        {
            $this->addToAssertionCount(1);
        }

    }

    public function testNotNull()
    {
        //Only wraps public property...
        $tested = new Column("tested");

        $this->assertNotTrue($tested->getNotNull());

        $tested->setNotNull();
        $this->assertTrue($tested->getNotNull());

        $tested->setNotNull(false);
        $this->assertNotTrue($tested->getNotNull());

        $tested->setNotNull(true);
        $this->assertTrue($tested->getNotNull());
    }

    public function testGetUnique()
    {
        //Only wraps public property...
        $tested = new Column("tested");

        $this->assertNotTrue($tested->getUnique());

        $tested->setUnique();
        $this->assertTrue($tested->getUnique());

        $tested->setUnique(false);
        $this->assertNotTrue($tested->getUnique());

        $tested->setUnique(true);
        $this->assertTrue($tested->getUnique());
    }




    public function testDefault()
    {
        $column = new Column("tested");

        //String or null
        //default = null
        $this->assertNull($column->getDefault());

        //Set string
        $column->setDefault("Hello World");

        $this->assertSame(
            "Hello World",
            $column->getDefault()
        );

        //No argument will reset (or when u set argument as null!)
        $column->setDefault();
        $this->assertNull($column->getDefault());

        //Default value can be anything, we leave it up to programmer.

        $column->setDefault(0);
        $this->assertSame(
            0,
            $column->getDefault()
        );

        $column->setDefault(false);
        $this->assertSame(
            false,
            $column->getDefault()
        );

        $column->setDefault(PHP_INT_MAX);
        $this->assertSame(
            PHP_INT_MAX,
            $column->getDefault()
        );

        $column->setDefault(0.42069); //stoned nice
        $this->assertSame(
            0.42069,
            $column->getDefault()
        );
    }

    /**
     * @throws ColumnDefinitionExists
     * @throws PrimaryKeyAutomaticException
     */
    public function testSetParent()
    {
        $table = new Table("RequiredAsParent");
        $column = new Column("tested");

        //endColumn returns parent!
        $this->assertNull($column->endColumn());

        $column->setParent($table);

        $this->assertSame(
            "RequiredAsParent",
            $column->endColumn()->getName()
        );


        $column = new Column("tested2");
        $this->assertNull($column->endColumn());

        //table table addColumn will autoSet parent!
        $table->columnAdd($column);
        $this->assertSame(
            "RequiredAsParent",
            $column->endColumn()->getName()
        );

        //and created column is automatically parented too
        $this->assertSame(
            "RequiredAsParent",
            $table->columnCreate("new_auto_created_column")->endColumn()->getName()
        );
    }

    /**
     * @throws ColumnDefinitionExists
     * @throws PrimaryKeyAutomaticException
     */
    public function testInstall()
    {
        //Get Mocked QueryMaker TableDefinition with prepared Tables:
        $desc = MockSQLMaker_ExistingTable_First::describeTable(new \Mock\PDO(), new Table("null"));
        $column = $desc->table->columnGet("name");

        //Should not fail.
        $QueriesToExecute = $column->install($desc,$desc->columnGet("name"));

        //Mock SQLMakers are (and should) not create any queries!
        $this->assertSame(
            [],
            $QueriesToExecute
        );
    }

}