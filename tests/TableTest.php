<?php
use PHPUnit\Framework\TestCase;
use vowserDB\Table;

class TableTest extends TestCase {
    protected static $table;

    public static function setUpBeforeClass() {
        self::$table = new Table('unitTest', 'users');
    }

    /**
     * Test the constructor
     */
    public function testSimpleConstruct() {
        $table = new Table('unitTestSimpleConstruct', ['col1', 'col2', 'col3']);
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists("vowserDB/unitTestSimpleConstruct.csv");
        $table->drop();
    }

    public function testTemplateConstruct() {
        $table = new Table('unitTestTemplateConstruct', 'users');
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists("vowserDB/unitTestTemplateConstruct.csv");
        $table->drop();
    }

    public function testAdvancedTemplateConstruct() {
        $table = new Table('unitTestAdvancedTemplateConstruct', 'users', ['column', 'col']);
        $this->assertInstanceOf(
            Table::class,
            $table
        );
        $this->assertFileExists("vowserDB/unitTestAdvancedTemplateConstruct.csv");
        $table->drop();
    }

    public function testFileExistsConstruct() {
        $table = new Table('unitTestFileExistsConstruct', 'users', ['column', 'col']);
        
        $sameTable = new Table('unitTestFileExistsConstruct');
        $this->assertInstanceOf(
            Table::class,
            $sameTable
        );

        $table->drop();
    }

    public static function tearDownAfterClass() {
        self::$table->drop();
    }
}