<?php

namespace is_ok;

class field {

    public string $name;
    public ?string $type = null;
    public ?string $condition = null;
    public ?string $label = null;
    public array $rules = [];

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function set_object($name) {
        $this->type = $name;
    }

    public function is_object() {
        return $this->type ? true : false;
    }

    public function add_rule($r) {
        // $fieldprops = ['if', 'label', 'condition', 'js-condition', 'type'];
        if ($r['name'] == 'label') {
            $this->label = $r['opts'];
        } elseif ($r['name'] == 'if') {
            $this->condition = $r['opts'];
        } else {
            $this->rules[] = new rule($r['name'], $r['opts']);
        }
    }
}
