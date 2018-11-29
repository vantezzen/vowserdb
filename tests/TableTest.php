<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Table;

class TableTest extends TestCase
{
    protected static $table;

    public static function setUpBeforeClass()
    {
        self::$table = new Table('unitTest', ['one', 'two', 'three', 'four']);
    }

    /**
     * Test the constructor.
     */
    public function testSimpleConstruct()
    {
        $table = new Table('unitTestSimpleConstruct', ['col1', 'col2', 'col3']);
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists('vowserDB/unitTestSimpleConstruct.csv');
        $table->drop();
    }

    public function testTemplateConstruct()
    {
        $table = new Table('unitTestTemplateConstruct', 'users');
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists('vowserDB/unitTestTemplateConstruct.csv');
        $table->drop();
    }

    public function testAdvancedTemplateConstruct()
    {
        $table = new Table('unitTestAdvancedTemplateConstruct', 'users', ['column', 'col']);
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists('vowserDB/unitTestAdvancedTemplateConstruct.csv');
        $table->drop();
    }

    public function testTemplateTableNameConstruct()
    {
        $table = new Table('users');
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists('vowserDB/users.csv');
        $this->assertEquals($table->columns, ['username', 'uuid', 'password', 'mail', 'data']);
        $table->drop();
    }

    public function testFileExistsConstruct()
    {
        $table = new Table('unitTestFileExistsConstruct', 'users', ['column', 'col']);

        $sameTable = new Table('unitTestFileExistsConstruct');
        $this->assertInstanceOf(
            Table::class,
            $sameTable
        );

        $table->drop();
    }

    /**
     * Test CRUD.
     */
    public function testCrud()
    {
        $table = self::$table;

        $data = [
            'one'  => 'first',
            'two'  => 'second',
            'four' => 'forth',
        ];

        // Lines in table file
        $lines = count(file('vowserDB/unitTest.csv'));
        $this->assertEquals(1, $lines);

        // INSERT
        $table->insert($data)->save();

        $this->assertCount(1, $table->data());
        $this->assertEquals([$data], $table->data());

        // Lines in table file
        $lines = count(file('vowserDB/unitTest.csv'));
        $this->assertEquals(2, $lines);

        // SELECT
        // Insert more data
        $table->insert([
            'one'   => 'row1',
            'two'   => 'row2',
            'three' => 'row3',
            'four'  => 'row4',
        ]);
        $table->insert([
            'one'   => 'row1',
            'two'   => 'row2__',
            'three' => 'row3__',
            'four'  => 'row4__',
        ]);
        $table->insert([
            'one'   => '1st',
            'two'   => '2nd',
            'three' => '3rd',
            'four'  => '4th',
        ]);
        $arrayTestRow = [
            'one' => 'row_data_1',
            'two' => [
                'two_one' => 'data_two_one',
                'two_two' => 'data_two_two',
            ],
            'three' => 'row_data_3',
            'four'  => [
                'data_1',
                'data_2',
                'data_3',
            ],
        ];
        $table->insert($arrayTestRow);

        // String select
        $table->select(['one' => 'row1']);
        $this->assertCount(2, $table->selected());
        $this->assertEquals([[
            'one'   => 'row1',
            'two'   => 'row2',
            'three' => 'row3',
            'four'  => 'row4', ],
            ['one'  => 'row1',
            'two'   => 'row2__',
            'three' => 'row3__',
            'four'  => 'row4__',
        ], ], $table->selected());

        $table->select(['one' => '1st']);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([[
            'one'   => '1st',
            'two'   => '2nd',
            'three' => '3rd',
            'four'  => '4th',
        ]], $table->selected());

        $table->select(['one' => 'row1', 'two' => 'row2']);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([[
            'one'   => 'row1',
            'two'   => 'row2',
            'three' => 'row3',
            'four'  => 'row4',
        ]], $table->selected());

        $table->select(['one' => 'row1'])->select(['two' => 'row2'], true);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([[
            'one'   => 'row1',
            'two'   => 'row2',
            'three' => 'row3',
            'four'  => 'row4',
        ]], $table->selected());

        // Array select
        // Associative
        // Full match
        $table->select(['two' => [
            'two_one' => 'data_two_one',
            'two_two' => 'data_two_two',
        ]]);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([$arrayTestRow], $table->selected());

        // Partial match
        $table->select(['two' => [
            'two_one' => 'data_two_one',
        ]], false, true);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([$arrayTestRow], $table->selected());

        // Non-associative
        // Full match
        $table->select(['four' => [
            'data_1',
            'data_2',
            'data_3',
        ]]);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([$arrayTestRow], $table->selected());

        // Partial match
        $table->select(['four' => [
            'data_1',
            'data_2',
        ]], false, true);
        $this->assertCount(1, $table->selected());
        $this->assertEquals([$arrayTestRow], $table->selected());

        // UPDATE
        // Simple update
        $table->select(['two' => 'updated_row2__']);
        $this->assertCount(0, $table->selected());

        $table->select(['two' => 'row2__'])->update(['two' => 'updated_row2__']);

        $table->select(['two' => 'row2__']);
        $this->assertCount(0, $table->selected());
        $table->select(['two' => 'updated_row2__']);
        $this->assertCount(1, $table->selected());

        // Update with update arguments
        $table->insert([
            'one'   => 1,
            'two'   => 2,
            'three' => 3,
            'four'  => 4,
        ]);
        $table->select(['one' => 5]);
        $this->assertCount(0, $table->selected());

        $table->select(['one' => 1])->update(['one' => 'INCREASE BY 4']);

        $table->select(['one' => 1]);
        $this->assertCount(0, $table->selected());
        $table->select(['one' => 5]);
        $this->assertCount(1, $table->selected());

        // DELETE
        // Delete single row
        $this->assertCount(6, $table->data());
        $table->select(['one' => 5])->delete();
        $this->assertCount(5, $table->data());

        // Delete multiple rows
        $this->assertCount(5, $table->data());
        $table->select(['one' => 'row1'])->delete();
        $this->assertCount(3, $table->data());

        // Truncate table
        $this->assertCount(3, $table->data());
        $table->truncate();
        $this->assertCount(0, $table->data());
    }

    public static function tearDownAfterClass()
    {
        self::$table->drop();
    }
}
