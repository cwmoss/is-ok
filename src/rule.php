<?php

namespace is_ok;

class rule {
    public string $name;
    public ?string $if = null;
    public array $opts = [];

    public function __construct(string $name, array $opts = []) {
        $this->name = $name;
        $this->opts = $opts;
    }

    public function parameter($param = 'val', $optional = false) {
        if (!$optional && !isset($this->opts[$param])) {
            throw new \InvalidArgumentException(
                "Missing value on {$this->name} validation, parameter {$param}"
            );
        }
        return $this->opts[$param] ?? null;
    }
    public function parameter_int($param = 'val', $optional = false) {
        $val = $this->parameter($param, $optional);
        if (is_null($val) || is_numeric($val)) {
            return $val;
        }
        throw new \InvalidArgumentException(
            "Invalid validation parameter. Should be numeric. (validation: {$this->name}, parameter {$param}"
        );
    }

    public function parameter_string($param = 'val', $optional = false) {
        $val = $this->parameter($param, $optional);
        if (is_null($val) || is_string($val)) {
            return $val;
        }

        throw new \InvalidArgumentException(
            "Invalid validation parameter. Should be string. (validation: {$this->name}, parameter {$param}"
        );
    }

    public function parameter_array($param = 'val', $optional = false) {
        $val = $this->parameter($param, $optional);
        if (is_null($val) || is_array($val)) {
            return $val;
        }

        throw new \InvalidArgumentException(
            "Invalid validation parameter. Should be array. (validation: {$this->name}, parameter {$param}"
        );
    }


    public function parameter_hash($param = 'val') {
        // TODO assoc
        return $this->opts;

        throw new \InvalidArgumentException(
            "Invalid validation parameter. Should be string. (validation: {$this->name}, parameter {$param}"
        );
    }
}
