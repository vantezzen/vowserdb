<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Helper\Armor;

class ArmorTest extends TestCase
{
    public function testArmorString()
    {
        $input = 'This is a test string!';

        $this->assertEquals($input, Armor::armor($input));
    }

    public function testArmorArray()
    {
        $input = [
            'this' => 'is',
            'a',
            'test',
        ];

        $output = 'vowserDBArray{"this":"is","0":"a","1":"test"}';

        $this->assertEquals($output, Armor::armor($input));
    }

    public function testUnarmorString()
    {
        $input = 'This is a test string!';

        $this->assertEquals($input, Armor::unarmor($input));
    }

    public function testUnarmorArray()
    {
        $input = 'vowserDBArray{"this":"is","0":"a","1":"test"}';

        $output = [
            'this' => 'is',
            'a',
            'test',
        ];

        $this->assertEquals($output, Armor::unarmor($input));
    }
}
