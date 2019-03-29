<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Extensions\sessionExtension;
use vowserDB\Table;

class sessionExtensionTest extends TestCase
{
    public function testSetup() {
        $extension = new sessionExtension(false);
        $table = new Table('unitTestSessionExtension', $extension::$columns);
        $table->attach($extension);
        
        $this->assertFileExists('vowserDB/unitTestSessionExtension.csv');
        $this->assertEquals(['id', 'data', 'lastused'], $table->columns);
        
        $table->drop();
    }
}
