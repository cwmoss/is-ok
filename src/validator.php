<?php

namespace is_ok;

error_reporting(\E_ALL);

class validator {
    public $o;
    public $rules = [];
    public $sets = [];
    public $context = [];
    public $trans_js = array(
        "mand" => "required", 'accept' => 'required', 'minval' => 'min',
        'maxval' => 'max', 'min' => 'minlength', 'max' => 'maxlength'
    );

    public static $v = null;

    public array $default_providers = [
        provider\basic::class
    ];

    public array $providers = [];

    // cache validation method paths
    public array $validations = [];

    public $messages;
    // metadaten zur regel quelle
    public static $m = array();

    // provider
    public static $p = [];
    public static $opts = array();



    public static $msg_rewrite = [];

    public function __construct($msgformatter, $rules = [], $context = [], $providers = []) {
        $this->messages = $msgformatter;
        $this->context = $context;
        $this->set_rulesets($rules);
        $iproviders = $this->default_providers;
        array_push($iproviders, ...$providers);
        foreach ($iproviders as $p) {
            $this->add_provider($p);
        }
    }

    public function add_provider($provider) {
        if (is_string($provider)) {
            $provider = new $provider;
        }
        array_unshift($this->providers, $provider);
    }

    public static function update_messages($msg) {
        self::$msg = array_merge(self::$msg, $msg);
    }

    public static function set_message_rewrites($vars) {
        self::$msg_rewrite = $vars;
    }

    public function set_rulesets($rulesets) {
        foreach ($rulesets as $setname => $rules) {
            $norm = self::normalize_rules($rules);
            $this->sets[$setname] = $norm;
        }
    }

    public function set_rules($rules) {
        if (is_string($rules)) {
            // log_error("loading rules - string command $rules");
            $this->rules = self::load_rules($rules);
        } else {
            // log_error("loading rules from array");
            $this->rules = self::normalize_rules($rules);
        }
    }


    public static function normalize_rules($rules) {
        $norm = [];
        foreach ($rules as $k => $r) {
            if (is_string($r)) {
                $norm[$k] = [self::normalize_rule($k, 'object', ['name' => $r])];
                continue;
            }
            $norm[$k] = [];
            foreach ($r as $name => $opts) {
                $norm[$k][] = self::normalize_rule($k, $name, $opts);
            }
        }
        return $norm;
    }

    public static function normalize_rule($field, $name, $opts) {
        // if($name=='max') $name='length';

        if ($name == 'on' || $name == 'condition' || $name == 'js-condition') {
            // nicht verändern
        } elseif (!is_array($opts)) {
            if ($name == 'length') {
                $opts = array('maximum' => $opts);
            } else {
                $opts = array('msg' => $opts);
            }
        }

        $opts = self::rewrite_messages($opts);
        return ['name' => $name, 'opts' => $opts];
    }

    public static function rewrite_messages($opts) {
        // actung hier könnte auch ein conditions string reinkommen
        if (!self::$msg_rewrite || !is_array($opts) || !$opts['msg']) {
            return $opts;
        }

        #var_dump(self::$msg_rewrite);

        $repl = array();
        foreach (self::$msg_rewrite as $k => $v) {
            if (preg_match("/^datum_/", $k)) {
                $v = hum_date($v);
            }
            $repl['[' . strtoupper($k) . ']'] = $v;
        }
        $txt = $opts['msg'];
        $txt = str_replace(array_keys($repl), $repl, $txt);
        # $txt = replace_links($txt);
        $opts['msg'] = $txt;
        return $opts;
    }


    public function validate($data, $root) {
        $errors = [];
        $data = new dotdata($data);

        foreach ($this->visit_fields($root) as $field) {
            // [$field, $checks, $path]
            $value = $data->get($field[2]);
            $errs = $this->validate_field($value, $data, $field[0], $field[1], $field[2]);
            if ($errs) {
                // dbg("validate errs", $errs);
                // $errors[$f] = $errs;
                array_push($errors, ...$errs);
            }
        }
        return $errors;
    }



    public function validate_field($value, $o, $e, $checks, $path) {
        // datumselemente
        // $dates=array("geburtsdatum", "seit", "bv_seit", "fa_seit", "aa_seit");
        $dbgvalue = $value;
        if (is_object($value) && !method_exists($item, '__toString')) {
            $dbgvalue = '[unprintable object]';
        }

        if (!$checks) {
            return [];
        }

        // top level event condition

        unset($checks['on']);

        if ($checks['condition'] ?? false) {
            // log_error("[V] e: $e, condition: {$checks['condition']}");
            if (!evaluator::check($checks['condition'], $o, $e)) {
                return [];
            } else {
                unset($checks['condition']);
            }
        }

        $errors = [];

        foreach ($checks as $k => $check_entry) {
            $check = $check_entry['name'];
            $opts = $check_entry['opts'];

            if ($check == 'js-condition') {
                continue;
            }

            // einzelcheck event condition
            unset($opts['on']);

            // einzelcheck kann auch an eine condition gebunden sein
            if ($opts['condition'] ?? false) {
                // log_error("[V] e: $e, condition singlecheck: {$opts['condition']}");
                if (!evaluator::check($opts['condition'], $o, $e)) {
                    // log_error("[V] condition singlecheck FAILED ==> SKIP");
                    continue;
                } else {
                    unset($opts['condition']);
                }
            }

            $errs = $this->validate_rule($o, $e, $value, $check, $opts, $path);
            if ($errs) {
                // $errors[] = array_merge($errors, $errs);
                array_push($errors, ...$errs);
                // bail out on error?
                if (isset($opts['bail']) && $opts['bail']) {
                    break;
                }
            }
        }
        // array_push($errors, $errors);
        return $errors;
    }

    public function validate_rule($o, $e, $value, $check, $opts, $path) {
        $errors = [];

        if ($check[0] == '.') {
            // der check wird an eine objektfunktion delegiert
            $opts['val'] = trim($check, ".");
            $meth = "method";
        } else {
            $meth = "{$check}";
        }

        #log_error("[V] e: $e m:$meth FAILED RSP");
        if ($opts['prepare'] ?? false) {
            $func = explode('::', $opts['prepare']);
            if (!$func[1]) {
                $prepare_opts = call_user_func_array(array($o, trim($func[0], '()')), array());
            } else {
                $prepare_opts = call_user_func_array(array($func[0], trim($func[1], '()')), array($e));
            }

            $opts = array_merge($opts, $prepare_opts);
        }
        $err = call_user_func_array(array($this, $meth), array($e, $value, $o, $opts));

        if ($err === true) {
            // dbg("[V] OK", $e, $value);
            return $errors;
        } else {
            if (is_object($err)) {
                if (!$err->message) {
                    $err->message = "Fehler für Eigenschaft $e ($check)";
                }
                $err->field = $path;
                $err->rule = $check;
                $errors[] = $err;
                $msg = $err->message;
            } else {
                if (is_string($err)) {
                    $msg = $this->get_message($err, $e, $opts);
                } else {
                    $msg = $this->get_message($err[0], $e, $opts, $err[1]);
                }
                if (!$msg) {
                    $msg = "Fehler für Eigenschaft $e (Regel: $check)";
                }
                $errors[] = new error($path, $msg, $check);
            }

            // dbg("[V] failed validation", $e, $value, $msg);
            return $errors;
        }
    }

    public function get_message($default, $e, $opts, $vals = []) {
        return $this->messages->get_message($default, $e, $opts, $vals);
    }



    public function visit_fields($root, $prefix = []) {
        $fields = $this->sets[$root];

        foreach ($fields as $name => $checks) {
            $path = array_merge($prefix, [$name]);
            if ($checks[0]['name'] == 'object') {
                yield from $this->visit_fields($checks[0]['opts']['name'], $path);
            } else {
                yield [$name, $checks, join(".", $path)];
            }
        }
    }

    public function translate($translator, $root) {
        $rules = [];

        // log_error("*** translator elements start ***");

        foreach ($this->visit_fields($root) as $field) {
            // [$field, $checks, $path]
            #print "$field ($path)";
            #print_r($checks);

            $frules = $this->translate_field($translator, $field);
            if ($frules) {
                // dbg("translated fieldrules", $frules);
                // $errors[$f] = $errs;
                array_push($rules, ...$frules);
            }
        }

        return $translator->sort($rules);
        return $rules;
    }

    public function translate_field($translator, $fieldrules) {
        $rules = [];
        [$field, $checks, $path] = $fieldrules;
        foreach ($checks as $check) {
            $rulename = $check['name'];
            $opts = $check['opts'];
            $rule = $translator->translate($rulename, $opts, $field);
            if ($rule) {
                $rule['msg'] = $this->get_message($rule['msg'], $field, $opts, $rule['vals']);
                $rule['field'] = $field;
                $rule['path'] = $path;
                $rules[] = $rule;
            }
        }
        return $rules;
    }





    public function call_validation($m, $parms) {
        // dbg("[V] call validation:", $m);
        $meth = $this->find_method($m);
        // dbg("[V] call method:", $meth);
        #log_error($parms);
        return call_user_func_array($meth, $parms);
    }


    public function find_method($m, $throw = true) {
        if (isset($this->validations[$m])) {
            return $this->validations[$m];
        }
        foreach ($this->providers as $p) {
            // dbg("check provider", get_class($p));
            if (method_exists($p, $m)) {
                $this->validations[$m] = array($p, $m);
                return $this->validations[$m];
            }
        }
        if ($throw) {
            throw new \InvalidArgumentException(sprintf('Unknown validation "%s"', $m));
        }
        return false;
    }

    public function __call($method, $attrs) {
        if (preg_match("/js_/", $method)) {
            return $this->call_js_validation($method, $attrs);
        } elseif (preg_match("/jsm_/", $method)) {
            return $this->call_js_message($method, $attrs);
        }
        return $this->call_validation($method, $attrs);
    }

    static public function parse_yaml($yaml) {
        return \Symfony\Component\Yaml\Yaml::parse(
            $yaml,
            \Symfony\Component\Yaml\Yaml::PARSE_CUSTOM_TAGS
        );
    }
}
