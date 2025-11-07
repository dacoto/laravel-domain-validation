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
        self::assertFalse($this->validator->passes('domain', 'localhost'));
    }

    public function testDnsCheckWithParameters(): void
    {
        // Test with parameters for DNS checks
        $validator = new Domain('mx');
        // google.com has MX records
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testDnsCheckWithMultipleParameters(): void
    {
        // Test with multiple DNS record types
        $validator = new Domain('a', 'mx');
        // google.com has both A and MX records
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testDnsCheckDns(): void
    {
        // Test 'dns' check - verifies domain has DNS records
        $validator = new Domain('dns');
        // google.com has various DNS records
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testFluentApiRequireMx(): void
    {
        // Test fluent API for MX records
        $validator = (new Domain())->requireMx();
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testFluentApiRequireA(): void
    {
        // Test fluent API for A records
        $validator = (new Domain())->requireA();
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testFluentApiChaining(): void
    {
        // Test chaining multiple requirements
        $validator = (new Domain())->requireA()->requireMx();
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testFluentApiRequireDns(): void
    {
        // Test requireDns method
        $validator = (new Domain())->requireDns();
        self::assertTrue($validator->passes('domain', 'google.com'));
    }

    public function testDnsCheckFailsForInvalidDomain(): void
    {
        // Test that DNS check fails for domains without MX records
        $validator = new Domain('mx');
        // This domain format is valid but likely doesn't have MX records
        self::assertFalse($validator->passes('domain', 'this-domain-definitely-does-not-exist-12345.com'));
    }

    public function testDnsCheckWithExpectedValue(): void
    {
        // Test fluent API with expected value
        // dns.google.com reliably resolves to 8.8.8.8 and 8.8.4.4
        $validator = (new Domain())->requireA('8.8.8.8');
        self::assertTrue($validator->passes('domain', 'dns.google.com'));
    }

    public function testDnsCheckWithAlternateExpectedValue(): void
    {
        // dns.google.com also resolves to 8.8.4.4
        // Should pass if ANY of the A records match
        $validator = (new Domain())->requireA('8.8.4.4');
        self::assertTrue($validator->passes('domain', 'dns.google.com'));
    }

    public function testDnsCheckWithMultipleExpectedValues(): void
    {
        // Test chaining multiple expected values
        // Both IPs should be present for dns.google.com
        $validator = (new Domain())
            ->requireA('8.8.8.8')
            ->requireA('8.8.4.4');
        self::assertTrue($validator->passes('domain', 'dns.google.com'));

        $validator = (new Domain())
            ->requireAaaa('2001:4860:4860::8888')
            ->requireAaaa('2001:4860:4860::8844');
        self::assertTrue($validator->passes('domain', 'dns.google.com'));
    }

    public function testDnsCheckWithWrongExpectedValue(): void
    {
        // Test with an IP that dns.google.com doesn't resolve to
        $validator = (new Domain())->requireA('1.1.1.1');
        self::assertFalse($validator->passes('domain', 'dns.google.com'));
    }

    public function testErrorMessage(): void
    {
        $validator = new Domain();
        $validator->passes('domain', 'invalid..domain');
        $message = $validator->message();
        self::assertIsString($message);
        self::assertStringContainsString('domain', strtolower($message));
    }

    public function testDnsErrorMessage(): void
    {
        $validator = new Domain('mx');
        $validator->passes('domain', 'this-domain-definitely-does-not-exist-12345.com');
        $message = $validator->message();
        self::assertIsString($message);
        // The error message should mention MX records
        self::assertStringContainsString('MX', $message);
    }

    public function testStringBasedSyntaxSingleParameter(): void
    {
        // Test string-based syntax: 'domain:a'
        // This simulates how Laravel parses 'domain:a' and calls new Domain('a')
        $validator = new Domain('a');
        self::assertTrue($validator->passes('domain', 'dns.google.com'));

        // Test with a domain that doesn't have A records (or doesn't exist)
        self::assertFalse($validator->passes('domain', 'this-domain-definitely-does-not-exist-12345.com'));
    }

    public function testStringBasedSyntaxMultipleParameters(): void
    {
        // Test string-based syntax: 'domain:a,mx'
        // This simulates how Laravel parses 'domain:a,mx' and calls new Domain('a', 'mx')
        $validator = new Domain('a', 'mx');
        self::assertTrue($validator->passes('domain', 'google.com'));

        // Test with a domain that has A but not MX
        $validatorMxOnly = new Domain('mx');
        // dns.google.com likely doesn't have MX records (it's a DNS service, not a mail domain)
        self::assertFalse($validatorMxOnly->passes('domain', 'dns.google.com'));
    }

    public function testStringBasedSyntaxDns(): void
    {
        // Test string-based syntax: 'domain:dns'
        // This simulates how Laravel parses 'domain:dns' and calls new Domain('dns')
        $validator = new Domain('dns');
        self::assertTrue($validator->passes('domain', 'google.com'));
        self::assertTrue($validator->passes('domain', 'dns.google.com'));
    }

    public function testStringBasedSyntaxAllTypes(): void
    {
        // Test each DNS record type via string syntax

        // A records (IPv4)
        $validatorA = new Domain('a');
        self::assertTrue($validatorA->passes('domain', 'dns.google.com'));

        // AAAA records (IPv6)
        $validatorAaaa = new Domain('aaaa');
        self::assertTrue($validatorAaaa->passes('domain', 'dns.google.com'));

        // MX records (mail)
        $validatorMx = new Domain('mx');
        self::assertTrue($validatorMx->passes('domain', 'google.com'));

        // TXT records
        $validatorTxt = new Domain('txt');
        self::assertTrue($validatorTxt->passes('domain', 'google.com'));
    }
}
