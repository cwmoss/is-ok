<?php

namespace is_ok\provider;

use function dbg;

class basic {
    public function method($e, $v, $vd, $opts = []) {
        if (!$v) {
            return true;
        }
        $m = $opts['val'];
        $res = call_user_func_array(array($vd, $m), array($e, $opts));
        if ($res === true) {
            // errors werden in custom function gesetzt
            return true;
        } elseif (is_string($res)) {
            $opts['msg'] = $res;
            // return new error($e, $vd->get_message('', $e, $opts));
            return $res;
        } elseif ($res === false || is_null($res)) {
            //return new error($e, $vd->get_message('', $e, $opts));
            return false;
        } else {
            return $res;
        }
    }

    public function dummy() {
        return true;
    }

    public function required($v, $rule) {
        if (is_null($v)) {
            return false;
        }

        if (is_array($v)) {
            if ($v) {
                return true;
            }
            return false;
        }

        if (!trim($v) && trim($v) !== "0") {
            return false;
        }
        return true;
    }

    public function min($v, $rule) {
        if (!$v) return true;

        $val = $rule->parameter_int();

        $len = mb_strlen((string) $v, "utf-8");
        if ($len < $val) {
            return ['too-short', $val];
        }
        return true;
    }

    public function max($v, $rule) {
        if (!$v) return true;
        $val = $rule->parameter_int();

        $len = mb_strlen((string) $v, "utf-8");
        if ($len > $val) {
            return ['too-long', $val];
        }
        return true;
    }

    public function len($v, $rule) {
        if (!$v) return true;
        $v = (string) $v;
        $len = mb_strlen($v, "utf-8");

        $is = $rule->parameter_int('val', true) ?: $rule->parameter_int('is', true);
        if (!is_null($is) && $len != $is) {
            return ['wrong-length', $is];
        }

        $min = $rule->parameter_int('min', true);
        if (!is_null($min) && $len < $min) {
            return ['too-short', $min];
        }

        $max = $rule->parameter_int('max', true);
        if (!is_null($max) && $len > $max) {
            return ['too-long', $max];
        }

        return true;
    }

    // TODO: handle subpath to => address.plz_confirm
    public function confirmed($v, $rule, $data, $path) {
        if (!$v) {
            return true;
        }
        if (isset($rule->opts['to'])) {
            $field = $data->path_update($path, $rule->opts['to']);
        } else {
            $field = $data->path_suffix($path, '_confirmation');
        }

        $v2 = $data->get($field, 'data');
        // var_dump($v, $v2, $path, $field, $data);

        if ($v != $v2) {
            return 'confirmed';
        }
        return true;
    }

    // The field under validation must be "yes", "on", 1, or true.
    public function accepted($v) {
        if (!in_array($v, ['yes', 'on', 1, true])) {
            return 'accepted';
        }
        return true;
    }

    public function inlist($v, $rule, $data) {
        // wir sind hier sehr lasch und erlauben null/ ""/ 0
        //    falls unerwünscht -- mand check setzen
        if (!$v) {
            return true;
        }

        $list = $rule->parameter();
        if (is_string($list)) {
            $list = $data->get_call($list);
        }
        if (!is_array($list)) return 'inclusion';
        if (!in_array($v, $list)) return 'inclusion';
        return true;
    }

    public function format($v, $rule) {
        if (!$v) {
            return true;
        }

        $f = $rule->parameter_string();

        if (!preg_match("$f", $v)) {
            return 'invalid';
        }
        return true;
    }

    /**
     *    Checks if email is like (anything-without-space)@(anything-without-space).(anything-without-space)
     *
     */
    public function email($v, $rule) {
        if (!$v) return true;
        $test = "^[^ ]+@[^ ]+\.[^ ]+$";
        if (!preg_match("/$test/", $v)) {
            return 'invalid';
        }
        return true;
    }


    public function numeric($v) {
        if (is_null($v)) return true;

        if (!is_numeric($v)) {
            return 'not_a_number';
        }
        return true;
    }

    public function integer($v, $rule) {
        if (is_null($v)) return true;

        // if (filter_var($int, FILTER_VALIDATE_INT, array("options" => array("min_range" => $min, "max_range" => $max))) === false) {
        if (filter_var($v, FILTER_VALIDATE_INT) === false) {
            return 'integer';
        }
        $v = (int) $v;
        $is = $rule->parameter_int('val', true) ?: $rule->parameter_int('is', true);
        if (!is_null($is) && $is != $v) {
            return 'integer-wrong-value';
        }
        $min = $rule->parameter_int('min', true);
        $max = $rule->parameter_int('max', true);
        if (!is_null($min) && !is_null($max) && (($v < $min) || ($v > $max))) {
            return 'integer-not-between';
        }
        if (!is_null($min) && is_null($max) && ($v < $min)) {
            return 'integer-too-small';
        }
        if (is_null($min) && !is_null($max) && ($v > $max)) {
            return 'integer-too-big';
        }
        return true;
    }

    public function decimal($v, $rule) {
        if (is_null($v)) return true;

        if (!is_numeric($v)) {
            return 'not_a_number';
        }

        $number_of_decimals = $rule->parameter_int();

        $matches = [];

        if (preg_match('/^[+-]?\d*\.?(\d*)$/', $v, $matches) !== 1) {
            return false;
        }

        $decimals = strlen(end($matches));
        return $decimals == $number_of_decimals;
    }

    public function _to_float($de_str) {
        $norm = str_replace(",", ".", $de_str);
        $norm = ((float) $norm);
        return $norm;
    }

    /**
     * validiert, ob ein feld leer ist
     *   quasi das gegenteil von mandatory
     *
     */
    public function empty($v) {
        return (!$v);
    }

    public function v_nonconfirm($e, $v, $vd, $opts = []) {
        $field = $opts['field'];
        // log_error("CONFIRM $e vs $field");
        if (!$v) {
            return true;
        }

        #	log_error($opts);
        if ($v == $vd->get($field)) {
            return 'confirm';
        }
        return true;
    }

    public function v_notequal($e, $v, $vd, $opts = []) {
        if (!$v) {
            return true;
        }
        $val = $opts['val'];
        // log_error("NOTEQUAL $e vs $field");
        #	log_error($opts);
        if ($v == $val) {
            return 'invalid';
        }
        return true;
    }

    public function v_notequalto($e, $v, $vd, $opts = []) {
        if (!$v) {
            return true;
        }
        $vals = (array) $opts['val'];
        foreach ($vals as $f) {
            $fv = $vd->get($f);
            // log_error("NOTEQUAL TO $e vs $fv ($field)");
            if ($v == $fv) {
                return 'invalid';
            }
        }
        return true;
    }
}
