<?php

declare(strict_types=1);

error_reporting(\E_ALL);

use PHPUnit\Framework\TestCase;

final class BasicProviderTest extends TestCase {
    public $ok;
    public $data;

    public function setUp(): void {
        $yaml = file_get_contents(__DIR__ . '/test.yaml');
        $this->data = is_ok\validator::parse_yaml($yaml);
        $this->ok = new is_ok\validator(new is_ok\message, $this->data, []);
    }
    public function testRequired(): void {
        $t1 = [];
        $errors = $this->ok->validate($t1, 'basic');
        $this->assertCount(
            2,
            $errors
        );
        print_r($errors);
        ob_flush();
        $this->assertSame($errors[0]->message, 'Name darf nicht leer sein');
        $this->assertSame($errors[1]->message, 'Email darf nicht leer sein');

        $t2 = ['email' => 'user@example.com'];
        $errors = $this->ok->validate($t2, 'basic');

        $this->assertCount(
            1,
            $errors
        );
        $this->assertSame($errors[0]->message, 'Name darf nicht leer sein');
        print_r($this->ok);
        ob_flush();
    }

    public function xxtestCannotBeCreatedFromInvalidEmail(): void {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString('invalid');
    }
}
