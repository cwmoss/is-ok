<?php

namespace is_ok;

error_reporting(\E_ALL);

class validator {

    public array $default_providers = [
        provider\basic::class
    ];

    public array $providers = [];

    // cache validation method paths
    public array $validations = [];

    public $messages;

    public static $msg_rewrite = [];

    public function __construct($msgformatter, $providers = []) {
        $this->messages = $msgformatter;
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

    public function validate($data, $rules, $root = null, $context = []) {
        // alternative signature: $data, $rules, $context
        if (is_array($root)) {
            $context = $root;
            $root = null;
        }
        $errors = [];
        $data = new dotdata(['data' => $data, 'context' => $context]);

        foreach ($this->visit_fields($rules, $root) as [$field, $path]) {
            // [$field, $checks, $path]
            $value = $data->get($path, 'data');
            $errs = $this->validate_field($value, $data, $field, $path);
            if ($errs) {
                // dbg("validate errs", $errs);
                // $errors[$f] = $errs;
                array_push($errors, ...$errs);
            }
        }
        return $errors;
    }


    public function validate_field($value, $data, $field, $path) {

        if ($field->condition) {
            // TODO
            return [];
            // log_error("[V] e: $e, condition: {$checks['condition']}");
            if (!evaluator::check($checks['condition'], $data, $e)) {
                return [];
            } else {
                unset($checks['condition']);
            }
        }

        $errors = [];

        foreach ($field->rules as $rule) {
            $check = $rule->name;
            $opts = $rule->opts;

            if ($check == 'js-condition') {
                continue;
            }
            // einzelcheck kann auch an eine condition gebunden sein
            if ($opts['condition'] ?? false) {
                // log_error("[V] e: $e, condition singlecheck: {$opts['condition']}");
                if (!evaluator::check($opts['condition'], $data, $e)) {
                    // log_error("[V] condition singlecheck FAILED ==> SKIP");
                    continue;
                } else {
                    unset($opts['condition']);
                }
            }

            $errs = $this->validate_rule($rule, $value, $data, $field, $path);
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

    public function validate_rule($rule, $value, $data, $field, $path) {
        $errors = [];

        // TODO (or not?)
        if (false && $rule->function == '.') {
            // der check wird an eine objektfunktion delegiert
            $opts['val'] = trim($check, ".");
            $meth = "method";
        }

        $meth = $rule->name;

        $err = call_user_func_array([$this, $meth], [$value, $rule, $data, $path]);

        if ($err === true) {
            // dbg("[V] OK", $e, $value);
            return $errors;
        } else {
            if (is_object($err)) {
                if (!$err->message) {
                    $err->message = $this->messages->fallback($field, $rule);
                }
                $err->field = $path;
                $err->rule = $meth;
                $errors[] = $err;
                $msg = $err->message;
            } else {
                $msg = "";
                if (is_string($err)) {
                    $msg = $this->get_message($err, $field, $rule);
                } elseif (is_array($err)) {
                    $msg = $this->get_message($err[0], $field, $rule);
                }
                if (!$msg) {
                    $msg = $this->messages->fallback($field, $rule);
                }
                $errors[] = new error($path, $msg, $meth);
            }

            // dbg("[V] failed validation", $e, $value, $msg);
            return $errors;
        }
    }

    public function get_message($default, $field, $rule) {
        return $this->messages->get_message($default, $field, $rule);
    }



    public function visit_fields($rules, $root, $prefix = []) {
        $fields = $rules->get($root);

        foreach ($fields as $name => $field) {
            $path = array_merge($prefix, [$name]);
            if ($field->is_object()) {
                yield from $this->visit_fields($rules, $field->type, $path);
            } else {
                yield [$field, join(".", $path)];
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

    public function __call($method, $parms) {
        if (preg_match("/js_/", $method)) {
            return $this->call_js_validation($method, $parms);
        } elseif (preg_match("/jsm_/", $method)) {
            return $this->call_js_message($method, $parms);
        }
        return $this->call_validation($method, $parms);
    }

    public static function array_is_list(array $array): bool {
        $i = -1;
        foreach ($array as $k => $v) {
            ++$i;
            if ($k !== $i) {
                return false;
            }
        }
        return true;
    }
}
