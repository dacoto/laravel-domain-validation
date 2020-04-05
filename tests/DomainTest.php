<?php

use dacoto\DomainValidator\Validator\Domain;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainTest
 */
class DomainTest extends TestCase
{
    /** @var Domain */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new Domain();
    }

    public function testPasses()
    {
        $this->assertTrue($this->validator->passes('domain', 'dacoto.com'));
        $this->assertTrue($this->validator->passes('domain', 'www.dacoto.com'));
    }

    public function testFails()
    {
        $this->assertFalse($this->validator->passes('domain', 'https://dacoto.com'));
        $this->assertFalse($this->validator->passes('domain', 'https://www.dacoto.com'));
    }
}
