<?php

namespace is_ok;


class error {
    function __construct(
        public string $field = '_',
        public string $message = 'Error',
        public string $rule = 'unknown'
    ) {
    }
}
