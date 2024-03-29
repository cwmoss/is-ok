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
        $this->assertSame($errors[0]->message, 'Name darf nicht leer sein.');
        $this->assertSame($errors[1]->message, 'Email darf nicht leer sein.');

        $t2 = ['email' => 'user@example.com'];
        $errors = $this->ok->validate($t2, $this->rules, 'basic');

        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Name darf nicht leer sein.');

        $t2 = ['email' => 'user-too-long@example.com'];
        $errors = $this->ok->validate($t2, $this->rules, 'basic');

        $this->assertCount(
            2,
            $errors
        );
        $this->assertSame($errors[1]->message, 'Email ist zu lang (maximal 20 Zeichen).');

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

    public function testConfirmed(): void {
        $data = ['plz' => 12345];
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            1,
            $errors
        );
        $data['plz_confirmation'] = '123';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Plz stimmt nicht überein.');
        $data['plz_confirmation'] = '12345';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        // ob_flush();
        //die();
        $this->assertCount(
            0,
            $errors
        );
        $data['plz_optional'] = '11999';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Plz_optional stimmt nicht überein.');
        $data['plz_optional_confirmation'] = '11999';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            0,
            $errors
        );
        $data['plz_different_fieldname'] = '11999';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            1,
            $errors
        );
        $data['plz2'] = '119990';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            1,
            $errors
        );
        $data['plz2'] = '11999';
        $errors = $this->ok->validate($data, $this->rules, 'confirm');
        $this->assertCount(
            0,
            $errors
        );
    }

    function testAccepted(): void {
        $rules = new is_ok\rules([
            'tos' => 'accepted',
        ]);
        $data = [];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Tos muß zugestimmt werden.');
        $data['tos'] = true;
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data['tos'] = 1;
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data['tos'] = 'yes';
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
    }

    function testEmpty(): void {
        $rules = new is_ok\rules([
            'comment' => 'empty',
        ]);
        $data = [];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data['comment'] = 'dont like it';
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Comment muß leer sein.');
    }

    function testLen(): void {
        $rules = new is_ok\rules([
            'plz' => 'len:5'
        ]);
        $data = ['plz' => '1234'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $rules = new is_ok\rules([
            'plz' => 'len:is=5'
        ]);
        $data = ['plz' => '1234'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $rules = new is_ok\rules([
            'plz' => 'len:min=1'
        ]);
        $data = ['plz' => '1234'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $rules = new is_ok\rules([
            'plz' => 'len:max=3'
        ]);
        $data = ['plz' => '1234'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $rules = new is_ok\rules([
            'plz' => 'len:min=3,max=5'
        ]);
        $data = ['plz' => '1234'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data = ['plz' => '123'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data = ['plz' => '12'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['plz' => '123456'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
    }

    function testInlist(): void {
        $rules = new is_ok\rules([
            'number' => 'inlist:context.happy'
        ]);
        $data = ['number' => '1234'];
        $errors = $this->ok->validate($data, $rules, ['happy' => ['333', '555']]);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['number' => '555'];
        $errors = $this->ok->validate($data, $rules, ['happy' => ['333', '555']]);
        $this->assertCount(
            0,
            $errors
        );
        $data = ['number' => '1234'];
        $errors = $this->ok->validate($data, $rules, ['happy' => fn () => range(55, 66)]);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['number' => '55'];
        $errors = $this->ok->validate($data, $rules, ['happy' => fn () => range(55, 66)]);
        $this->assertCount(
            0,
            $errors
        );
    }

    function testFormat(): void {
        $rules = new is_ok\rules([
            'invoice_number' => 'format:/^inv_/i'
        ]);
        $data = ['invoice_number' => '1234'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['invoice_number' => 'inv_123'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data = ['invoice_number' => 'INV_123'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
    }

    function testDecimal(): void {
        $rules = new is_ok\rules([
            'price' => 'decimal:2'
        ]);
        $data = ['price' => '18.2'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Price muss 2 Nachkommastellen haben.');
        $data = ['price' => '18.20'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
    }

    function testNumeric(): void {
        $rules = new is_ok\rules([
            'age' => 'numeric'
        ]);
        $data = ['age' => 'plus18'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['age' => '1856'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $data = ['age' => '18.75'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
    }

    function testInteger(): void {
        $rules = new is_ok\rules([
            'age' => 'integer'
        ]);
        $data = ['age' => 'plus18'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Age ist keine ganze Zahl.');
        $data = ['age' => '1856'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
        $rules = new is_ok\rules([
            'age' => 'integer:22'
        ]);
        $data = ['age' => '1856'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Age muss 22 sein.');

        $data = ['age' => '22'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );

        $rules = new is_ok\rules([
            'age' => 'integer:min=18'
        ]);
        $data = ['age' => '16'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Age muss größer gleich 18 sein.');

        $data = ['age' => '18'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );

        $rules = new is_ok\rules([
            'age' => 'integer:max=26'
        ]);
        $data = ['age' => '35'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Age muss kleiner gleich 26 sein.');

        $data = ['age' => '18'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );

        $rules = new is_ok\rules([
            'age' => 'integer:min=18,max=26'
        ]);
        $data = ['age' => '35'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Age muss zwischen 18 und 26 sein.');

        $data = ['age' => '16'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Age muss zwischen 18 und 26 sein.');

        $data = ['age' => '26'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
    }

    function testEmail(): void {
        $rules = new is_ok\rules([
            'email' => 'email'
        ]);
        $data = ['email' => 'harry'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['email' => 'harry@'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['email' => 'harry@de'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            1,
            $errors
        );
        $data = ['email' => 'harry@20sec.net'];
        $errors = $this->ok->validate($data, $rules);
        $this->assertCount(
            0,
            $errors
        );
    }
}
