<?php

use dacoto\DomainValidator\Validator\Domain;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainTest
 */
final class DomainTest extends TestCase
{
    /** @var Domain */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new Domain();
    }

    public function testPasses(): void
    {
        self::assertTrue($this->validator->passes('domain', 'dacoto.com'));
        self::assertTrue($this->validator->passes('domain', 'www.dacoto.com'));
        self::assertTrue($this->validator->passes('domain', 'hello.world.io'));        
    }

    public function testFails(): void
    {
        self::assertFalse($this->validator->passes('domain', '.'));
        self::assertFalse($this->validator->passes('domain', 'empty'));
        self::assertFalse($this->validator->passes('domain', 'empty.'));
        self::assertFalse($this->validator->passes('domain', '.empty'));
        self::assertFalse($this->validator->passes('domain', 'https://dacoto.com'));
        self::assertFalse($this->validator->passes('domain', 'https://www.dacoto.com'));
    }
}
