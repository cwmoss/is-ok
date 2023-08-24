<?php

namespace is_ok\provider;

use function is_ok\dbg;

class password {
    public function password($v, $rule) {
        if (!$v) {
            return true;
        }
        $checks = $rule->parameter_hash();

        foreach ($checks as $r => $ropts) {
            if (!$this->$r($v, $ropts)) {
                return false;
            }
        }
        return true;
    }

    public function letters($v) {
        return preg_match('/\pL/u', $v);
    }

    public function mixedcase($v) {
        return preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $v);
    }

    public function numbers($v) {
        return preg_match('/\pN/u', $v);
    }

    public function symbols($v) {
        return preg_match('/\p{Z}|\p{S}|\p{P}/u', $v);
    }

    public function maxrepeat($v, $max) {
        preg_match_all('/(.)\1+/u', $v, $matches);
        $result = array_combine($matches[0], array_map('mb_strlen', $matches[0]));
        if (!$result) return true;

        arsort($result, \SORT_NUMERIC);
        return (current($result) <= $max);
    }
}
