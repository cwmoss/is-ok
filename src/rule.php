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

    public function val_int($param = 'val', $optional = false) {
        if (!$optional && !isset($this->opts[$param])) {
            throw new \InvalidArgumentException(
                "Missing value on {$this->name} validation, parameter {$param}"
            );
        }
        if ($optional && !isset($this->opts[$param])) {
            return null;
        }
        if (isset($this->opts[$param]) && is_numeric($this->opts[$param])) {
            return $this->opts[$param];
        } else {
            throw new \InvalidArgumentException(
                "Missing or invalid (should be numeric) value on {$this->name} validation, parameter {$param}"
            );
        }
    }

    public function val_string($param = 'val', $optional = false) {
        if (!$optional && !isset($this->opts[$param])) {
            throw new \InvalidArgumentException(
                "Missing value on {$this->name} validation, parameter {$param}"
            );
        }
        if ($optional && !isset($this->opts[$param])) {
            return null;
        }
        if (isset($this->opts[$param]) && is_string($this->opts[$param])) {
            return $this->opts[$param];
        } else {
            throw new \InvalidArgumentException(
                "Missing or invalid (should be string) value on {$this->name} validation, parameter {$param}"
            );
        }
    }

    public function val_array($param = 'val', $optional = false) {
        if (!$optional && !isset($this->opts[$param])) {
            throw new \InvalidArgumentException(
                "Missing value on {$this->name} validation, parameter {$param}"
            );
        }
        if ($optional && !isset($this->opts[$param])) {
            return null;
        }
        if (isset($this->opts[$param]) && is_array($this->opts[$param])) {
            return $this->opts[$param];
        } else {
            throw new \InvalidArgumentException(
                "Missing or invalid (should be array) value on {$this->name} validation, parameter {$param}"
            );
        }
    }


    public function val_hash() {
        // TODO assoc
        return $this->opts;

        throw new \InvalidArgumentException(
            "Missing or invalid (should be associative array) value on {$this->name} validation"
        );
    }
}
