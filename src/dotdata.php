<?php

namespace is_ok;

class dotdata {

    function __construct(
        public array|object $data
    ) {
    }

    function get($path) {

        if (!is_array($path)) {
            $path = explode('.', $path);
        }
        $current = $this->data;
        $current_path = [];

        foreach ($path as $part) {
            $current_path[] = $part;

            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
            } elseif (is_object($current) && isset($current->$part)) {
                $current = $current->$part;
            } else {
                return null;
            }
        }
        return $current;
    }
}
