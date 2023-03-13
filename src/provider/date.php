<?php

namespace is_ok\provider;

class date {
    public function v_dateformat($e, $v, $vd, $opts) {
        if (!$v) {
            return true;
        }

        if (!is_string($v) && !is_numeric($v)) {
            return false;
        }
        $format = $opts['format'];


        $date = \DateTime::createFromFormat('!' . $format, $v);

        if ($date && $date->format($format) == $v) {
            return true;
        }

        return false;
    }



    public function v_datum($e, $v, $vd, $opts = []) {
        $err = $this->_check_and_clean_date_in_object($vd->o, $e, $opts);

        if (!$err) {
            return true;
        } else {
            // TODO: (error) col
            $msg = join("<br>\n", array_map(function ($e) {
                return $e->msg;
            }, $err));

            return new Xorcstore_Error($e, $msg);
        }
    }

    public function _check_and_clean_date_in_object($o, $el, $opts = array()) {
        $y = $el . "_yy";
        $m = $el . "_mm";
        $d = $el . "_dd";
        $inp = $el . "_inp";
        // soll der tag mit berücksichtigt werden?
        $day = $opts['day'];
        log_error("CHECKDATE: $el ($day)");
        log_error($opts);

        if ($opts['single-field']) {
            $error_el = $el;
            $input_exists = ($o->$el);

            $d = explode(".", $o->$el, 3);
            $datp = htmlspecialchars($d[2] . "-" . $d[1] . "-" . $d[0]);
            $dat  = sprintf("%04d-%02d-%02d", $d[2], $d[1], $d[0]);
        } else {
            $error_el = $el . "_dd";
            $input_exists = ($o->$y || $o->$m || $o->$d);

            if (!$day) {
                $o->$d = "01";
                $datp = htmlspecialchars($o->$y . "-" . $o->$m);
            } else {
                $datp = htmlspecialchars($o->$y . "-" . $o->$m . "-" . $o->$d);
            }
            $dat = sprintf("%04d-%02d-%02d", $o->$y, $o->$m, $o->$d);
        }

        $errors = array();

        if ($input_exists) {
            // formaler test
            if ($dat != $datp) {
                log_error("++ DAT-FORMAT $dat VS $datp");
                $errors[] = new Xorcstore_Error($error_el, "Datumsformat ungültig.");
                return $errors;
            }

            $lab = $opts['name'];

            // formal alles ok.
            list($yy, $mm, $dd) = explode("-", $dat);

            if (@checkdate($mm, $dd, $yy)) {
                $o->$inp = sprintf("%02d.%02d.%04d", $dd, $mm, $yy);
                $now = date("Y-m-d");
                if ($opts['past'] && ($now < $dat)) {
                    $errors[] = new Xorcstore_Error($error_el, "Datum darf nicht in der Zukunft liegen: " . $lab . " ($datp)");
                    $o->$inp = null;
                } elseif ($el != "geburtsdatum" && $o->geburtsdatum_inp) {
                    $gebdat = my_date($o->geburtsdatum_inp);
                    log_error("############ GEB vs. DAT (OTHER): $gebdat vs. $dat");
                    if (substr($dat, 0, 7) < substr($gebdat, 0, 7)) {
                        $errors[] = new Xorcstore_Error($error_el, "Datum darf nicht vor Ihrem Geburtsdatum liegen: " . $lab . " ($datp)");
                        $o->$inp = null;
                    }
                } elseif ($el == "geburtsdatum" && $o->geburtsdatum_inp) {
                    $gebdat = my_date($o->geburtsdatum_inp);
                    log_error("############ GEB vs. DAT (GEBDAT): $gebdat vs. $dat");
                    if (substr($gebdat, 0, 4) == date("Y")) {
                        $errors[] = new Xorcstore_Error($error_el, "Sie haben sich bei Ihrem Geburtsjahr vertippt: (" . date("Y") . ")");
                        $o->$inp = null;
                    }
                }
                if ($opts['future'] && ($now >= $dat)) {
                    $errors[] = new Xorcstore_Error($error_el, "Datum muss in der Zukunft liegen: " . $lab . " ($datp)");
                    $o->$inp = null;
                }
                if ($opts['same-year'] && (date("Y") != $yy)) {
                    $errors[] = new Xorcstore_Error($error_el, $opts['msg-same-year']);
                    $o->$inp = null;
                }
                if ($opts['min-age'] && (strtotime("-{$opts['min-age']} years") < strtotime($dat))) {
                    $errors[] = new Xorcstore_Error($error_el, $opts['msg-min-age']);
                }
            } else {
                # print_r($o);
                $o->$inp = null;
                #	$errors[] = new Xorcstore_Error($el."_dd", "Datum ungültig. ".$lab." ($datp)");
                $errors[] = new Xorcstore_Error($error_el, "Datum ungültig.");
            }
        } else {
            $o->$inp = null;
        }
        return $errors;
    }
}
