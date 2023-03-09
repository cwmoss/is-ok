<?php

namespace twentyseconds\validation;

use xorcstore_error;

class passwordprovider
{
    public function v_password($e, $v, $vd, $opts)
    {
        dbg("++ passwordcheck");
        if (!$v) {
            return true;
        }

        foreach ($opts['rules'] as $r => $ropts) {
            dbg("++ pwd rule", $r);
            if(!$this->$r($v, $ropts)){
                return false;
            }
        }
        return true;
    }

    public function letters($v)
    {
        return preg_match('/\pL/u', $v);
    }

    public function mixedcase($v)
    {
        return preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $v);
    }

    public function numbers($v)
    {
        return preg_match('/\pN/u', $v);
    }

    public function symbols($v)
    {
        return preg_match('/\p{Z}|\p{S}|\p{P}/u', $v);
    }

    public function maxrepeat($v, $max)
    {
        preg_match_all('/(.)\1+/u', $v, $matches);
        $result = array_combine($matches[0], array_map('mb_strlen', $matches[0]));
        if(!$result) return true;

        arsort($result, \SORT_NUMERIC);
        return (current($result) <= $max);
    }
}
