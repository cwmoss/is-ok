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

    public function val_int() {
        if (isset($this->opts['val']) && is_numeric($this->opts['val'])) {
            return $this->opts['val'];
        } else {
            throw new \InvalidArgumentException(
                "Missing or invalid (should be numeric) value on {$this->name} validation"
            );
        }
    }

    public function val_string() {
        if (isset($this->opts['val']) && is_string($this->opts['val'])) {
            return $this->opts['val'];
        } else {
            throw new \InvalidArgumentException(
                "Missing or invalid (should be string) value on {$this->name} validation"
            );
        }
    }

    public function val_array() {
        if (isset($this->opts['val']) && is_array($this->opts['val'])) {
            return $this->opts['val'];
        } else {
            throw new \InvalidArgumentException(
                "Missing or invalid (should be array) value on {$this->name} validation"
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
