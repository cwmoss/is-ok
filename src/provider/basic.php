<?php

namespace is_ok\provider;

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

    public function dummy($e, $v, $vd, $opts = []) {
        return true;
    }

    public function required($e, $v, $vd, $opts = []) {
        if (is_null($v)) {
            return "empty";
        }

        if (is_array($v)) {
            if ($v) {
                return true;
            }
            return 'empty';
        }

        if (!trim($v) && trim($v) !== "0") {
            return 'empty';
        }
        return true;
    }

    public function js_mand($e, $vd, $opts) {
        return ['required', 'empty', true];
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

    public function min($e, $v, $vd, $opts = []) {
        $opts['minimum'] = $opts['val'];
        return $this->v_length($e, $v, $vd, $opts);
    }

    public function js_min($e, $vd, $opts) {
        return ['minlength', 'too-short', $opts['val']];
    }

    public function max($e, $v, $vd, $opts = []) {
        if (!isset($opts['val'])) {
            throw new \InvalidArgumentException(
                sprintf('Missing max value on max validation')
            );
        }
        $opts['maximum'] = $opts['val'];

        return $this->v_length($e, $v, $vd, $opts);
    }

    public function v_len($e, $v, $vd, $opts = []) {
        $opts['is'] = $opts['val'];
        return $this->v_length($e, $v, $vd, $opts);
    }

    public function js_len($e, $vd, $opts) {
        return ['maxlength', 'wrong-length', $opts['val']];
    }

    public function v_length($e, $v, $vd, $opts = []) {
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

    public function confirm($e, $v, $vd, $opts = []) {
        if (!$v) {
            return true;
        }
        dbg("++ confirm validation", $e, $v);
        // rule on src element

        if (!preg_match("/Confirmation$/", $e)) {
            $src = $e;
            $confirm = $e . "Confirmation";
            $vgl = $confirm;

            // rule explicit defined on *Confirmation element
        } else {
            $src = str_replace('Confirmation', '', $e);
            $confirm = $e;
            $vgl = $src;
        }
        $v2 = $vd->get($vgl);
        if (is_null($v2)) {
            return true;
        }
        dbg("++ confirm v1 vs v2 (src confirm)", $v, $v2, $src, $confirm, $e);

        // fehler immer an das _confirmationfeld hängen
        if ($v != $v2) {
            //return new error($confirm, $vd->get_message('confirmation', $src, $opts));
            return 'confirmation';
        }
        return true;
    }

    public function js_confirm($e, $name, $opts) {
        // rule on src element
        if (!preg_match("/_confirmation$/", $e)) {
            $src = $e;
            $confirm = $e . "_confirmation";
            $vgl = $confirm;
            return ["equalTo:{$e}_confirmation", 'confirmation', "#{$e}"];

            // rule explicit defined on _confirmation element
        } else {
            $src = str_replace('_confirmation', '', $e);
            $confirm = $e;
            $vgl = $src;
            return ["equalTo", 'confirmation', "#{$src}"];
        }
    }

    public function v_accept($e, $v, $vd, $opts = []) {
        if (is_null($v)) {
            return true;
        }
        if (!$opts['accept']) {
            $opts['accept'] = 1;
        }
        if ($v != $opts['accept']) {
            return 'accepted';
        }
        return true;
    }

    public function js_accept($e, $vd, $opts) {
        return ['acceptcheckbox', 'accepted', true];
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

    public function format($e, $v, $vd, $opts = []) {
        if (!$v) {
            return true;
        }

        $f = $opts['regex'];
        // $f = str_replace('BOB_DIA_OK', BOB_DIA_OK, $f);

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
    public function email($e, $v, $vd, $opts = []) {
        $opts['regex'] = "^[^ ]+@[^ ]+\.[^ ]+$";
        return $this->format($e, $v, $vd, $opts);
    }

    public function js_email($e, $vd, $opts = []) {
    }

    public function v_plz($e, $v, $vd, $opts = []) {
        $opts['regex'] = "^[0-9]{5}$";
        return $this->v_format($e, $v, $vd, $opts);
    }

    public function js_plz($e, $vd, $opts) {
        return ['plz', 'format', true];
    }

    public function v_konto($e, $v, $vd, $opts = []) {
        $opts['regex'] = "^\d{2,10}$";
        return $this->v_format($e, $v, $vd, $opts);
    }

    public function v_blz($e, $v, $vd, $opts = []) {
        $opts['regex'] = "^\d{8}$";
        return $this->v_format($e, $v, $vd, $opts);
    }

    public function v_iban($e, $v, $vd, $opts = []) {
        $opts['regex'] = "^(DE|de)\d{20}$";
        return $this->v_format($e, $v, $vd, $opts);
    }

    public function js_iban($e, $vd, $opts) {
        return ['iban', 'invalid', $opts];
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
    public function empty($e, $v, $vd, $opts = []) {
        if ($v) {
            return new error($e, $opts['msg']);
        }
        return true;
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



    public function v_fulleuro($e, $v, $vd, $opts = []) {
        $opts['regex'] = "^[\d]+$";
        return $this->v_format($e, $v, $vd, $opts);
    }
}
