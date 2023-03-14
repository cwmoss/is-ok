<?php

namespace is_ok;

use function PHPUnit\Framework\isList;

class rules {

    public array $sets;

    public function __construct($guess) {
        if (is_string($guess)) {
            $this->set_rulesets(self::parse_yaml($guess));
        } else {
            $this->set_rulesets(self::parse_laravel($guess), true);
        }
    }

    public function get($root = null) {
        // return $this->sets;
        if (is_null($root)) {
            return $this->sets['_'];
        }
        return $this->sets[$root];
    }

    public function set_rulesets($rulesets, $from_laravel = false) {
        foreach ($rulesets as $setname => $rules) {
            $norm = self::normalize_rules($rules, $from_laravel);
            $this->sets[$setname] = $norm;
        }
    }

    public static function normalize_rules($fields, $from_laravel = false) {
        $norm = [];

        foreach ($fields as $k => $r) {
            $field = new field($k);
            if (is_string($r)) {
                $field->set_object($r);
                $norm[$k] = $field;
                continue;
            }

            foreach ($r as $name => $opts) {
                $rule = self::normalize_rule($k, $name, $opts, $from_laravel);
                $field->add_rule($rule);
            }
            $norm[$k] = $field;
        }
        return $norm;
    }

    public static function normalize_rule($field, $name, $opts, $from_laravel = false) {
        // if($name=='max') $name='length';

        if ($name == 'if' || $name == 'condition' || $name == 'js-condition' || $name == 'label') {
            // nicht verändern
        } elseif (!is_array($opts)) {
            if (!$opts) {
                $opts = [];
            } elseif ($from_laravel || is_numeric($opts)) {
                $opts = ['val' => $opts];
            } else {
                $opts = ['msg' => $opts];
            }
        } elseif (is_array($opts) && validator::array_is_list($opts)) {
            $opts = ['val' => $opts];
        }

        // $opts = self::rewrite_messages($opts);
        return ['name' => $name, 'opts' => $opts];
    }
    /*
        'title:The Title' => 'required|unique:posts|max:255',
        'body' => 'required',
        'avatar' => 'dimensions:min_width=100,min_height=200',
        'email' => 'email:rfc,dns'
    */
    static public function parse_laravel($defs) {
        $rules = [];
        foreach ($defs as $key => $val) {
            $r = [];
            if (str_contains($key, ':')) {
                [$key, $label] = explode(':', $key);
                $r['label'] = $label;
            }
            foreach (explode('|', $val) as $rule) {
                $name_params = self::parse_rule($rule);
                $r[$name_params[0]] = $name_params[1];
            }
            $rules[$key] = $r;
        }
        print_r($rules);
        flush();
        return ['_' => $rules];
    }

    static public function parse_rule(string $rule): array {
        $exp      = explode(':', $rule, 2);
        $ruleName = $exp[0];

        if (in_array($ruleName, ['matches', 'regex'])) {
            $params = [$exp[1]];
        } else {
            if (isset($exp[1])) {
                if (str_contains($exp[1], '=')) {
                    parse_str(str_replace(',', '&', $exp[1]), $res);
                    $params = $res;
                } else {
                    $val = str_getcsv($exp[1]);
                    // more often we only need a single value
                    if (count($val) < 2) $val = $val[0];
                    $params = $val;
                }
            } else {
                $params = null;
            }
        }

        return [$ruleName, $params];
    }
    static public function parse_yaml($yaml) {
        return \Symfony\Component\Yaml\Yaml::parse(
            $yaml,
            \Symfony\Component\Yaml\Yaml::PARSE_CUSTOM_TAGS
        );
    }
}
