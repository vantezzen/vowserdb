<?php
namespace vowserDB\Extensions;

use vowserDB\AbstractExtension;
use Exception;

class relationshipExtension extends AbstractExtension {
    // Temporary storage for relationship row names
    protected $row1;
    protected $row2;

    protected $table1 = false;
    protected $table2 = false;

    protected $lock = false;

    public $listeners = [];
    public $methods = [];
    public $middlewares = [
        "selected" => "applyRelationship",
        "data" => "applyRelationship"
    ];
    
    public function __construct($row1 = "", $row2 = "") {
        $this->row1 = $row1;
        $this->row2 = $row2;
    }

    public function applyRelationship($event, $value, $table) {
        // Lock mechanism necessary for not resulting in an endless loop
        // (partner trying to apply relationship when getting relationship data etc.)
        if ($this->lock) {
            return $value;
        }
        $this->lock = true;

        if ($table == $this->table1['table']) {
            $main = $this->table1;
            $partner = $this->table2;
        } else {
            $main = $this->table2;
            $partner = $this->table1;
        }
        
        foreach($value as $key => $row) {
            // Save last selection for restoring later
            $lastselection = $partner['instance']->lastSelection;

            $new = $partner['instance']->select(
                [
                    $partner['row'] => $row[$main['row']]
                ]
            )->selected();
            $value[$key][$main['row']] = $new;

            // Restore last selection
            $partner['instance']->select($lastselection);
        }

        $this->lock = false;
         
        return $value;
    }

    public function onAttach(string $table, string $path, \vowserDB\Table $instance) {
        $info = [
            "table" => $table,
            "path" => $path,
            "instance" => $instance
        ];

        if ($this->table1 == false) {
            $info['row'] = $this->row1;
            $this->table1 = $info;
        } else if ($this->table2 == false) {
            $info['row'] = $this->row2;
            $this->table2 = $info;
        } else {
            throw new Exception("Already attached two tables to relationship extension");
        }
    }
}