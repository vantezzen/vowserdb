<?php
use PHPUnit\Framework\TestCase;
use vowserDB\Helper\CRUD;

class CRUDTest extends TestCase {
    public function testCanApplySelection() {
        $exampleData = [
            [
                "column1" => "value1",
                "column2" => "value2"
            ],
            [
                "column1" => "value3",
                "column2" => "value4"
            ],
            [
                "column1" => "value1",
                "column2" => "value5"
            ],
            [
                "column1" => "value6",
                "column2" => "value7"
            ]
        ];
        $columns = [
            "column1",
            "column2"
        ];
        $selection = ["column1" => "value1"];
        $expectedResult = [
            [
                "column1" => "value1",
                "column2" => "value2"
            ],
            [
                "column1" => "value1",
                "column2" => "value5"
            ],
        ];
        
        $this->assertEquals(
            $expectedResult,
            CRUD::applySelection($exampleData, $selection, $columns)
        );
    }
}