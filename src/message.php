<?php

namespace is_ok;

class message {

    public $generic = "Fehler für Eigenschaft {name} (Regel: {rule})";

    public array $msg = [
        'required' => "{name} darf nicht leer sein.",
        'empty' => "{name} muß leer sein.",
        'min' => '{name} ist zu kurz (min. {val}).',
        'max' => '{name} ist zu lang (max. {val}).',
        'taken' => "{name} ist bereits in Benutzung.",
        'invalid' => "{name} ist ungültig.",
        'too-long' => "{name} ist zu lang (maximal {val} Zeichen).",
        'too-short' => "{name} ist zu kurz (mind. {val} Zeichen).",
        'wrong-length' => "{name} hat die falsche Länge. (sollen {val} zeichen sein).",
        'not-a-number' => "{name} ist keine Zahl.",
        'integer' => "{name} ist keine ganze Zahl.",
        'integer-wrong-value' => "{name} muss {val} sein.",
        'integer-too-small' => "{name} muss größer gleich {min} sein.",
        'integer-too-big' => "{name} muss kleiner gleich {max} sein.",
        'integer-not-between' => "{name} muss zwischen {min} und {max} sein.",
        'decimal' => "{name} muss {val} Nachkommastellen haben.",
        'accepted' => "{name} muß zugestimmt werden.",
        'inclusion' => "{name} ist nicht in der Liste der erlaubten Werte.",
        'exclusion' => "{name} ist reserviert.",
        'confirmed' => "{name} stimmt nicht überein.",
        'not-past' => "{name} muss in der Vergangenheit liegen.",
        'not-future' => "{name} muss in der Zukunft liegen.",
        'date-before' => "{name} muss vor {val} liegen.",
        'date-after' => "{name} muss nach {val} liegen.",
        'too-young' => "Das Mindestalter beträgt {val} Jahre.",
        'too-old' => "Das Höchstalter beträgt {val} Jahre.",
        'not-this-year' => '{name} muss in diesem Jahr liegen.'
    ];

    public function fallback($field, $rule) {
        $msg = $this->get_message($rule->name, $field, $rule);
        if (!$msg) {
            $msg = $this->format_message($this->generic, ['name' => $field->name, 'rule' => $rule->name]);
        }
        return $msg;
    }

    public function get_message($default, $field, $rule) {
        $replacements = [
            'name' => $field->label,
            'yourval' => htmlspecialchars($rule->opts['val']['__'] ?? ''),
            'val' => ""
        ];
        $replacements += $rule->opts;

        $vals = $rule->opts['val'] ?? null;
        if ($vals && !is_array($vals)) {
            $replacements['val'] = $vals;
        }
        if (!$replacements['name']) {
            $replacements['name'] = ucfirst($field->name);
        }
        $msg = $rule->opts['msg_' . $default] ?? null;
        if (!$msg) {
            $msg = $rule->opts['msg'] ?? null;
        }
        if (!$msg) {
            $msg = $this->msg[$default] ?? '';
        }

        return self::format_message($msg, $replacements);
    }

    public static function format_message($msg, $replacements = []) {
        $rep = [];
        foreach ($replacements as $k => $v) {
            $rep['{' . $k . '}'] = $v;
        }
        return str_replace(array_keys($rep), $rep, $msg);
    }
}
