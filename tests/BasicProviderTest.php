<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;

final class BasicProviderTest extends TestCase {
    public $ok;
    public $rules;

    public function setUp(): void {
        $yaml = file_get_contents(__DIR__ . '/test.yaml');
        $this->rules = new is_ok\rules($yaml);
        #print_r($this->rules);

        $rules =  [
            'title:The Title' => 'required|unique:posts|max:255',
            'body' => 'required',
            'avatar' => 'dimensions:min_width=100,min_height=200',
            'email' => 'email:rfc,dns'
        ];
        #$rule = new \is_ok\rules($rules);
        #print_r($rule);
        #die();
        $this->ok = new is_ok\validator(new is_ok\message);
    }
    public function testRequired(): void {
        $t1 = [];
        $errors = $this->ok->validate($t1, $this->rules, 'basic');
        $this->assertCount(
            2,
            $errors
        );
        print_r($errors);
        ob_flush();
        $this->assertSame($errors[0]->message, 'Name darf nicht leer sein');
        $this->assertSame($errors[1]->message, 'Email darf nicht leer sein');

        $t2 = ['email' => 'user@example.com'];
        $errors = $this->ok->validate($t2, $this->rules, 'basic');

        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Name darf nicht leer sein');

        $t2 = ['email' => 'user-too-long@example.com'];
        $errors = $this->ok->validate($t2, $this->rules, 'basic');

        $this->assertCount(
            2,
            $errors
        );
        $this->assertSame($errors[1]->message, 'Email ist zu lang (maximal 20 Zeichen)');

        print_r($this->rules);
        ob_flush();
    }

    public function testLaravelParser(): void {
        $rules =  [
            'title:The Title' => 'required|unique:posts|max:255',
            'body' => 'required',
            'avatar' => 'dimensions:min_width=100,min_height=200',
            'email' => 'email:rfc,dns'
        ];
        $rule = new \is_ok\rules($rules);
        $this->assertCount(4, $rule->sets['_']);
    }

    public function testLaravelRules(): void {
        $t1 = [];
        $rules = new is_ok\rules([
            'name:Your Name' => 'required|max:60',
            'email' => 'required|max:20|email'
        ]);
        print_r($rules);
        flush();
        //die();

        $errors = $this->ok->validate($t1, $rules);
        $this->assertCount(
            2,
            $errors
        );
    }
}
