<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Extensions\encryptExtension;
use vowserDB\Table;

class encryptExtensionTest extends TestCase
{
    protected static $table;

    public static function setUpBeforeClass(): void
    {
        $table = new Table('unitTestEncryptExtension', ['one', 'two', 'three', 'four'], false, ['skip_read' => true]);
        $extension = new encryptExtension('EE3542093D20E7175A8321E48FCC9934');
        $table->attach($extension);

        $table->insert([
            'one'   => '1',
            'two'   => '2',
            'three' => '3',
        ]);

        self::$table = $table;
    }

    public function testTableEncryption()
    {
        // Test table save
        self::$table->save();
        $this->assertStringEqualsFile('vowserDB/unitTestEncryptExtension.csv', 'encrypted');

        // Test table read
        self::$table->read();
        $this->assertCount(1, self::$table->data());
        $this->assertEquals([[
            'one'   => '1',
            'two'   => '2',
            'three' => '3',
            'four'  => '',
        ]], self::$table->data());
    }

    public static function tearDownAfterClass(): void
    {
        self::$table->drop();
    }
}
