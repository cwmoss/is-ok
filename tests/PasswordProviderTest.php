<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use is_ok\message;
use PHPUnit\Framework\TestCase;
use is_ok\validator;
use is_ok\rules;
use is_ok\provider\password;

final class PasswordProviderTest extends TestCase {
    public $ok;
    public $rules;

    function testPassword() {
        $t = [];
        $rules = new rules(['pwd' => 'required|password:letters=1,numbers=1,maxrepeat=2']);
        $validator = new validator(new message, [new password]);
        $errors = $validator->validate($t, $rules);
        $this->assertCount(1, $errors);

        var_dump($rules);
        ob_flush();
        $t = ['pwd' => '123'];
        $errors = $validator->validate($t, $rules);
        $this->assertCount(1, $errors);
    }
}
