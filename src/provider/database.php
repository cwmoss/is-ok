<?php

namespace is_ok\provider;

use PDO;
use function is_ok\dbg;

class database {
    public PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function unique($v, $rule, $data) {
        if (!$v) {
            return true;
        }
        $checks = $rule->val_hash();

        $test = ['test' => $v];

        $and = "";
        if ($checks['ignore_id'] ?? false) {
            $id = $data->get($checks['ignore_id']);
            /*
                if the id is null (eq: not set), it makes no sense to add this to the query
                example sqlite:
                SELECT name FROM users WHERE name='hansi' AND id != null
                    => 0 results 
                
                in other words: we only support ignore_id's that are strings or ints
            */
            if (!is_null($id)) {
                $and = sprintf(' AND id != :id');
                $test['id'] = $id;
            }
        }

        $query = sprintf(
            "SELECT %s FROM %s WHERE %s=:test %s LIMIT 1",
            $checks['column'],
            $checks['table'],
            $checks['column'],
            $and
        );

        //var_dump($query);
        //var_dump($test);

        $sth = $this->pdo->prepare($query);
        $ok = $sth->execute($test);
        if ($ok === true) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return 'taken';
            } else {
                return true;
            }
        }
        return false;
    }

    public function exists($v, $rule) {
        if (!$v) {
            return true;
        }
        $checks = $rule->val_hash();

        $test = ['test' => $v];
        $query = sprintf(
            "SELECT %s FROM %s WHERE %s=:test LIMIT 1",
            $checks['column'],
            $checks['table'],
            $checks['column'],
        );

        var_dump($query);
        var_dump($test);

        $sth = $this->pdo->prepare($query);
        $ok = $sth->execute($test);
        var_dump($ok);
        if ($ok === true) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            var_dump($row);
            if ($row) {
                return true;
            } else {
                return 'not-existing';
            }
        }
        return false;
    }
}
