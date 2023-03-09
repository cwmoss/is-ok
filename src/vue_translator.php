<?php

namespace twentyseconds\validation;

class vue_translator
{
    public $map = [
        'mand' => ['required', 'empty'],
        'format' => ['regex', 'invalid', 'regex'],
        'min' => ['minlength', 'too-short', 'val'],
        'max' => ['maxlength', 'too-long', 'val'],
        'plz' => ['plz', 'invalid'],
        'accept' => ['isTrue', 'invalid'],
        'iban_precheck' => ['checkIbanFormat', 'invalid'],
        'remote_iban' => ['checkIban', 'invalid']
    ];

    public function __construct()
    {
    }

    public function pre_translate($name, $rule)
    {
        return $rule;
    }

    public function post_translate($name, $rule, $orig)
    {
        $replacements = [];
        if ($rule[2] && !is_array($rule[2])) {
            $replacements['val'] = 'val';
        }
        //print_r($rule);
        return [
            'name' => $rule[0],
            'msg' => $rule[1],
            'vals' => $rule[2],
            'replacements' => array_map(fn ($r) =>'{'.$r.'}', $replacements)
        ];
    }

    public function v_confirm($name, $rule){
        #print_r($rule);
        if(isset($rule['to'])){
            $to = $rule['to'];
        }elseif (!preg_match("/Confirmation$/", $name)) {
            $to = $name."Confirmation";

        // rule explicit defined on *Confirmation element
        } else {
            $to = str_replace('Confirmation', '', $name);
        }
        return ['equalTo', 'confirmation', $to];
    }

    public function vvvvv_format($rule)
    {
        return ['format', 'invalid', $rule['regex']];
    }

    public function translate($name, $rule, $field=null)
    {
        $js = $this->pre_translate($name, $rule);
        $m = 'v_'.$name;
        if (method_exists($this, $m)) {
            $js = $this->$m($field, $rule);
        } else {
            // TODO, general check?
            // OR not-implemented
            $js = $this->translate_by_map($name, $rule);
            if ($js===false) {
                return false;
            }
        }
        #print_r($js);
        $js = $this->post_translate($name, $js, $rule);
        //print_r($js);
        return $js;
    }

    public function translate_by_map($name, $rule)
    {
        $map = $this->map[$name]??null;
        if (!$map) {
            return false;
        }
        $val = $map[2]??null;
        if ($val) {
            $val = $rule[$val];
        }
        return [$map[0], $map[1], $val];
    }

    public function sort($rules)
    {
        $sorted = [];
        //print_r($rules);
        foreach ($rules as $r) {
            // dbg("sort rule", $r);
            if (isset($sorted[$r['path']])) {
                $sorted[$r['path']][]=$this->_reshape($r);
            } else {
                $sorted[$r['path']] = [$this->_reshape($r)];
            }
        }
        return $sorted;
    }

    public function _reshape($r)
    {
        $attrs = [];

        if (isset($r['vals']) && is_scalar($r['vals'])) {
            $attrs=['val'=>$r['vals']];
        } elseif (isset($r['vals']) && is_array($r['vals'])) {
            $attrs=$r['vals'];
        }
        $attrs['msg'] = $r['msg'];

        return [$r['name'] => $attrs];
    }
}
