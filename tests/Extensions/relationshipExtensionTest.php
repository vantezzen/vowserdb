<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Extensions\relationshipExtension;
use vowserDB\Table;

class relationshipExtensionTest extends TestCase
{
    protected static $table;
    protected static $table2;

    public static function setUpBeforeClass(): void
    {
        $table = new Table('unitTestRelationshipExtension', ['one', 'two', 'three', 'four']);
        $table2 = new Table('unitTestRelationshipExtension2', ['one', 'two', 'three', 'four']);
        $extension = new relationshipExtension('one', 'two');
        $table->attach($extension);
        $table2->attach($extension);

        $table->insert([
            'one'   => '1',
            'two'   => '2',
            'three' => '3',
        ]);
        $table2->insert([
            'one'   => '2',
            'two'   => '1',
            'three' => '2',
        ])->insert([
            'one'   => '5',
            'two'   => '5',
            'three' => '2',
        ]);;

        self::$table = $table;
        self::$table2 = $table2;
    }

    public function testRelationship() {
        $data = self::$table->select(['three' => '3'])->selected();
        $expected = [
            [
                'one' => [
                    [
                        'one' => '2',
                        'two' => '1',
                        'three' => '2'
                    ]
                ],
                'two' => '2',
                'three' => '3'
            ]
        ];
        $this->assertEquals($expected, $data);
    }

    public static function tearDownAfterClass(): void
    {
        self::$table->drop();
        self::$table2->drop();
    }
}
