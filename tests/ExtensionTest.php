<?php
use PHPUnit\Framework\TestCase;
use vowserDB\Table;
use vowserDB\AbstractExtension;

class TableTest extends TestCase {
    protected static $table;

    public static function setUpBeforeClass() {
        self::$table = new Table('unitExtensionTest', ['one', 'two', 'three', 'four']);
    }

    public function testExtension() {
    }

    public static function tearDownAfterClass() {
        self::$table->drop();
    }
}