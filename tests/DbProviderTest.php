<?php

declare(strict_types=1);

error_reporting(\E_ALL);


use is_ok\message;
use is_ok\provider\database;
use PHPUnit\Framework\TestCase;
use is_ok\validator;
use is_ok\rules;
use is_ok\provider\password;

final class DbProviderTest extends TestCase {
    public $ok;
    public $pdo;

    function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL
        )');
        $this->ok = new validator(new message, [new database($this->pdo)]);
    }

    function testUnique() {
        $t = ['username' => 'hansi'];

        $rules = new rules(['username' => 'required|unique:table=users,column=name']);
        var_dump($rules);
        ob_flush();
        // die();

        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(0, $errors);

        $this->pdo->exec("INSERT INTO users (name) VALUES('hansi')");
        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals('Username ist bereits in Benutzung.', $errors[0]->message);

        $rules = new rules(['username' => 'required|unique:table=users,column=name,ignore_id=context.user_id']);
        $errors = $this->ok->validate($t, $rules);
        var_dump($errors);
        // die();
        $this->assertCount(1, $errors);

        $errors = $this->ok->validate($t, $rules, ['user_id' => 1]);
        $this->assertCount(0, $errors);

        $errors = $this->ok->validate($t, $rules, ['user_id' => 99]);
        $this->assertCount(1, $errors);

        $rules = new rules(['username' => 'required|unique:table=users,column=name,ignore_id=data.id']);

        $t = ['username' => 'hansi', 'id' => 1];
        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(0, $errors);

        $t = ['username' => 'hansi', 'id' => 99];
        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(1, $errors);
    }

    function testExists() {
        $this->pdo->exec("INSERT INTO users (id, name) VALUES(11, 'hansi')");

        $rules = new rules(['username' => 'required|exists:table=users,column=name']);

        $t = ['username' => 'hansi', 'id' => 22];
        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(0, $errors);

        $t = ['username' => 'hansixx', 'id' => 11];
        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(1, $errors);

        $rules = new rules(['id' => 'required|exists:table=users,column=id']);

        $t = ['username' => 'hansi', 'id' => 22];
        $errors = $this->ok->validate($t, $rules);
        $this->assertCount(1, $errors);

        $t = ['username' => 'hansi', 'id' => 11];
        $errors = $this->ok->validate($t, $rules);
        //var_dump($errors);
        //die();
        $this->assertCount(0, $errors);
    }
}
