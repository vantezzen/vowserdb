<?php

use PHPUnit\Framework\TestCase;
use vowserDB\Helper\CRUD;

class CRUDTest extends TestCase
{
    public function testCanApplySingleSelection()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
            [
                'column1' => 'value6',
                'column2' => 'value7',
            ],
        ];
        $columns = [
            'column1',
            'column2',
        ];
        $selection = ['column1' => 'value1'];
        $expectedResult = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::applySelection($exampleData, $selection, $columns, true)
        );
    }

    public function testCanApplyMultipleSelections()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
            [
                'column1' => 'value6',
                'column2' => 'value7',
            ],
        ];
        $columns = [
            'column1',
            'column2',
        ];
        $selection = [
            'column1' => 'value1',
            'column2' => 'value2',
        ];
        $expectedResult = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::applySelection($exampleData, $selection, $columns, true)
        );
    }

    public function testCanApplyPreciseArraySelection()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
            [
                'column1' => [
                    'somevalue',
                    'othervalue',
                ],
                'column2' => 'value5',
            ],
            [
                'column1' => [
                    'nomatch',
                    'othervalue',
                ],
                'column2' => 'value7',
            ],
        ];
        $columns = [
            'column1',
            'column2',
        ];
        $selection = [
            'column1' => [
                'somevalue',
                'othervalue',
            ],
        ];
        $expectedResult = [
            [
                'column1' => [
                    'somevalue',
                    'othervalue',
                ],
                'column2' => 'value5',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::applySelection($exampleData, $selection, $columns, false)
        );
    }

    public function testCanApplyPartialArraySelection()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
            [
                'column1' => [
                    'somevalue',
                    'othervalue',
                ],
                'column2' => 'value5',
            ],
            [
                'column1' => [
                    'nomatch',
                    'othervalue',
                ],
                'column2' => 'value7',
            ],
        ];
        $columns = [
            'column1',
            'column2',
        ];
        $selection = [
            'column1' => [
                'somevalue',
            ],
        ];
        $expectedResult = [
            [
                'column1' => [
                    'somevalue',
                    'othervalue',
                ],
                'column2' => 'value5',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::applySelection($exampleData, $selection, $columns, true)
        );
    }

    public function testCanApplyPartialAssociativeArraySelection()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
            [
                'column1' => [
                    'some'  => 'value',
                    'other' => 'value_',
                ],
                'column2' => 'value5',
            ],
            [
                'column1' => [
                    'nomatch',
                    'othervalue',
                ],
                'column2' => 'value7',
            ],
        ];
        $columns = [
            'column1',
            'column2',
        ];
        $selection = [
            'column1' => [
                'some' => 'value',
            ],
        ];
        $expectedResult = [
            [
                'column1' => [
                    'some'  => 'value',
                    'other' => 'value_',
                ],
                'column2' => 'value5',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::applySelection($exampleData, $selection, $columns, true)
        );
    }

    public function testCanApplyBasicUpdate()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
            [
                'column1' => 'value6',
                'column2' => 'value7',
            ],
        ];
        $selection = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => 'value4',
            ],
        ];
        $update = [
            'column2' => 'updated_value',
        ];
        $expectedResult = [
            [
                'column1' => 'value1',
                'column2' => 'updated_value',
            ],
            [
                'column1' => 'value3',
                'column2' => 'updated_value',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
            [
                'column1' => 'value6',
                'column2' => 'value7',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::update($selection, $exampleData, $update)
        );
    }

    public function testCanApplyMathUpdate()
    {
        $exampleData = [
            [
                'column1' => '5',
                'column2' => 'value2',
            ],
            [
                'column1' => '99',
                'column2' => 'value4',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
            [
                'column1' => 'value6',
                'column2' => 'value7',
            ],
        ];
        $selection = [
            [
                'column1' => '5',
                'column2' => 'value2',
            ],
            [
                'column1' => '99',
                'column2' => 'value4',
            ],
        ];
        $update = [
            'column1' => 'INCREASE BY 10',
        ];
        $expectedResult = [
            [
                'column1' => '15',
                'column2' => 'value2',
            ],
            [
                'column1' => '109',
                'column2' => 'value4',
            ],
            [
                'column1' => 'value1',
                'column2' => 'value5',
            ],
            [
                'column1' => 'value6',
                'column2' => 'value7',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::update($selection, $exampleData, $update)
        );
    }

    public function testCanApplyArrayPushUpdate()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => [
                    'dont',
                    'update',
                    'me',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                ],
            ],
        ];
        $selection = [
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                ],
            ],
        ];
        $update = [
            'column2' => 'ARRAY PUSH another value',
        ];
        $expectedResult = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => [
                    'dont',
                    'update',
                    'me',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                    'another value',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                    'another value',
                ],
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::update($selection, $exampleData, $update)
        );
    }

    public function testCanApplyArrayRemoveUpdate()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => [
                    'dont',
                    'update',
                    'me',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                ],
            ],
        ];
        $selection = [
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                ],
            ],
        ];
        $update = [
            'column2' => 'ARRAY REMOVE values',
        ];
        $expectedResult = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => [
                    'dont',
                    'update',
                    'me',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                ],
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::update($selection, $exampleData, $update)
        );
    }

    public function testCanDelete()
    {
        $exampleData = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'value3',
                'column2' => [
                    'dont',
                    'update',
                    'me',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                ],
            ],
        ];
        $selection = [
            [
                'column1' => 'value1',
                'column2' => 'value2',
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'some',
                    'values',
                ],
            ],
        ];
        $expectedResult = [
            [
                'column1' => 'value3',
                'column2' => [
                    'dont',
                    'update',
                    'me',
                ],
            ],
            [
                'column1' => 'select',
                'column2' => [
                    'random',
                    'values',
                ],
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            CRUD::delete($selection, $exampleData)
        );
    }
}
