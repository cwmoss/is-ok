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


    public function v_inlist($e, $v, $vd, $opts = array()) {
        // wir sind hier sehr lasch und erlauben null/ ""/ 0
        //    falls unerwünscht -- mand check setzen
        if (!$v) {
            return true;
        }
        $list = $opts['func'] ?? null;
        if ($list) {
            if (str_contains($list, '::')) {
                $list = explode('::', $list);
                $list = call_user_func_array(array($list[0], trim($list[1], '()')), array($e));
                $list = array_keys($list);
            } else {
                [$ctxobj, $meth] = explode('#', $list);
                $obj = $vd->context[$ctxobj];
                $list = call_user_func_array(array($obj, trim($meth, '()')), array($e));
                $list = array_keys($list);
            }
        } else {
            $list = $opts['list'];
        }

        $opts['in'] = $list;
        # TODO
        $opts['between'] = null;
        if (
            $opts['exclude'] == 1 &&
            ($opts['in'] && in_array($v, $opts['in'])) ||
            ($opts['between'] && ($v >= $opts['between'][0] && $v <= $opts['between'][1]))
        ) {
            return ['exclusion', $opts['between']];
        } else {
            if (
                !$opts['exclude'] &&
                ($opts['in'] && !in_array($v, $opts['in'])) ||
                ($opts['between'] && ($v < $opts['between'][0] || $v > $opts['between'][1]))
            ) {
                return 'inclusion';
            }
        }
        return true;
    }

    public function min($v, $rule) {
        if (!$v) return true;

        $val = $rule->val_int();

        $len = mb_strlen((string) $v, "utf-8");
        if ($len < $val) {
            return ['too-short', $val];
        }
        return true;
    }

    public function max($v, $rule) {
        if (!$v) return true;
        $val = $rule->val_int();

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

        $is = $rule->val_int('val', true) ?: $rule->val_int('is', true);
        if (!is_null($is) && $len != $is) {
            return ['wrong-length', $is];
        }

        $min = $rule->val_int('min', true);
        if (!is_null($min) && $len < $min) {
            return ['too-short', $min];
        }

        $max = $rule->val_int('max', true);
        if (!is_null($max) && $len > $max) {
            return ['too-long', $max];
        }

        return true;
    }

    public function js_len($e, $vd, $opts) {
        return ['maxlength', 'wrong-length', $opts['val']];
    }

    public function length($e, $v, $vd, $opts = []) {
        if ($opts['allow_null'] ?? false && is_null($v)) {
            return true;
        }
        $v = (string) $v;
        if (function_exists('mb_strlen')) {
            $v = str_replace("\r\n", "\n", $v);
            $v = str_replace("\r", "\n", $v);
            $len = mb_strlen($v, "utf-8");
        } else {
            $len = strlen($v);
        }
        $min = $max = null;
        if (isset($opts['between'])) {
            list($min, $max) = $opts['between'];
        }
        if (isset($opts['maximum'])) {
            $max = $opts['maximum'];
        }
        if (isset($opts['minimum'])) {
            $min = $opts['minimum'];
        }

        if (isset($opts['is']) && $len != $opts['is']) {
            return array('wrong-length', $opts['is']);
        } elseif (!is_null($min) && ($len < $min || is_null($v))) {
            return array('too-short', $opts['minimum']);
        } elseif (!is_null($max) && ($len > $max || is_null($v))) {
            return array('too-long', $opts['maximum']);
        }
        return true;
    }

    public function js_length($e, $name, $opts) {
        return ['maxlength', 'too-long', $opts['maximum']];
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


    public function v_unique($e, $v, $vd, $opts = []) {
        if (!$v) {
            return true;
        }

        $unique = $vd->is_unique($v, $e);
        if (!$unique) {
            return 'taken';
        }
        return true;
    }

    public function js_unique($e, $vd, $opts = []) {
        $url = $opts['url'];
        if (!$url) {
            return false;
        }
        return ['remote', 'taken', url($url)];
    }

    public function format($v, $rule) {
        if (!$v) {
            return true;
        }

        $f = $rule->val_string();

        if (!preg_match("/$f/", $v)) {
            return 'invalid';
        }
        return true;
    }

    public function js_format($e, $vd, $opts = []) {
        return ['format', 'invalid', $opts['regex']];
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



    public function v_number($e, $v, $vd, $opts = []) {
        if ($opts['allow_null'] && (is_null($v) || (is_string($v) && !trim($v)))) {
            return true;
        }

        $opts['modify_before_check'] = 'to_float';
        if ($opts['modify_before_check']) {
            $func = $opts['modify_before_check'];
            $v = $func($v);
        }

        if (!is_numeric($v) || ($opts['only_integer'] && (is_float($v) || !preg_match("/^[-+]?\d+$/", $v)))) {
            return 'not_a_number';
        }
    }

    public function v_minval($e, $v, $vd, $opts = []) {
        if ($v === "") {
            return true;
        }
        $min = $opts['val'];
        if ($opts['compare_float']) {
            $min = $this->_to_float($min);
            $v = $this->_to_float($v);
        }
        if ($v < $min) {
            return ['min', $min];
        }
        return true;
    }

    public function js_minval($e, $vd, $opts) {
        if ($opts['compare_float']) {
            return ['min_de', 'invalid', $opts['val']];
        }
        return ['min', 'invalid', $opts['val']];
    }

    public function v_maxval($e, $v, $vd, $opts = []) {
        if ($v === "") {
            return true;
        }
        $max = $opts['val'];
        if ($opts['compare_float']) {
            $max = $this->_to_float($max);
            $v = $this->_to_float($v);
        }
        // log_error("maxval {$v} vs {$max}");
        if ($v > $max) {
            return ['max', $max];
        }
        return true;
    }

    public function _to_float($de_str) {
        $norm = str_replace(",", ".", $de_str);
        $norm = ((float) $norm);
        return $norm;
    }

    public function js_maxval($e, $vd, $opts) {
        if ($opts['compare_float']) {
            return ['max_de', 'invalid', $opts['val']];
        }
        return ['max', 'invalid', $opts['val']];
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
