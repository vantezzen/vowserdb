<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Storage\CSV;
use vowserDB\Table;

class CSVStorageTest extends TestCase
{
    protected static $storage;
    protected static $table;

    public static function setUpBeforeClass(): void
    {
        self::$storage = new CSV();
        self::$table = new Table('unitCSVStorageTest', ['one', 'two', 'three', 'four'], false, [
            'storage' => self::$storage,
        ]);
    }

    /**
     * Test basic functionality.
     */
    public function testCreation()
    {
        $this->assertFileExists('vowserDB/unitCSVStorageTest.csv');
        $this->assertStringEqualsFile('vowserDB/unitCSVStorageTest.csv', 'one,two,three,four
');
    }

    /**
     * Test get columns array.
     */
    public function testGetColumns()
    {
        $file = self::$table->path;
        $actual = self::$storage->columns($file);
        $this->assertEquals(['one', 'two', 'three', 'four'], $actual);
    }

    /**
     * Test insertion.
     */
    public function testInsert()
    {
        self::$table->insert(['two' => 'test'])->save()->read();

        // Test table class data
        $expected = [[
            'one'   => '',
            'two'   => 'test',
            'three' => '',
            'four'  => '',
        ]];
        $this->assertEquals($expected, self::$table->data());

        // Test table file
        $this->assertStringEqualsFile('vowserDB/unitCSVStorageTest.csv', 'one,two,three,four
,test,,
');
        self::$table->truncate()->save();
    }

    public static function tearDownAfterClass(): void
    {
        self::$table->drop();
    }
}
