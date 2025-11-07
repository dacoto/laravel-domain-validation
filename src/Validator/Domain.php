<?php

namespace dacoto\DomainValidator\Validator;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

/**
 * Class Domain
 * @package dacoto\DomainValidator
 */
class Domain implements Rule, ValidatorAwareRule
{
    use Conditionable;
    use Macroable;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * DNS record types to check for presence.
     *
     * @var array
     */
    protected array $dnsChecks = [];

    /**
     * DNS record types with expected values.
     *
     * @var array
     */
    protected array $dnsExpectedValues = [];

    /**
     * Check if any DNS record type should exist.
     *
     * @var bool
     */
    protected bool $checkDns = false;

    /**
     * The error message after validation, if any.
     *
     * @var string
     */
    protected string $errorMessage = '';

    /**
     * Create a new Domain validation rule instance.
     *
     * @param  string|null  ...$parameters
     */
    public function __construct(...$parameters)
    {
        if (!empty($parameters)) {
            $this->parseParameters($parameters);
        }
    }

    /**
     * Parse parameters passed via string validation syntax.
     *
     * @param  array  $parameters
     * @return void
     */
    protected function parseParameters(array $parameters): void
    {
        foreach ($parameters as $param) {
            $param = strtolower(trim($param));

            if (in_array($param, ['a', 'aaaa', 'cname', 'txt', 'mx', 'dns'])) {
                if ($param === 'dns') {
                    $this->checkDns = true;
                } else {
                    $this->dnsChecks[] = strtoupper($param);
                }
            }
        }
    }

    /**
     * Require that the domain has A records.
     *
     * @param  string|null  $expectedValue
     * @return $this
     */
    public function requireA(?string $expectedValue = null)
    {
        return $this->requireDnsRecord('A', $expectedValue);
    }

    /**
     * Require that the domain has AAAA records.
     *
     * @param  string|null  $expectedValue
     * @return $this
     */
    public function requireAaaa(?string $expectedValue = null)
    {
        return $this->requireDnsRecord('AAAA', $expectedValue);
    }

    /**
     * Require that the domain has CNAME records.
     *
     * @param  string|null  $expectedValue
     * @return $this
     */
    public function requireCname(?string $expectedValue = null)
    {
        return $this->requireDnsRecord('CNAME', $expectedValue);
    }

    /**
     * Require that the domain has TXT records.
     *
     * @param  string|null  $expectedValue
     * @return $this
     */
    public function requireTxt(?string $expectedValue = null)
    {
        return $this->requireDnsRecord('TXT', $expectedValue);
    }

    /**
     * Require that the domain has MX records.
     *
     * @param  string|null  $expectedValue
     * @return $this
     */
    public function requireMx(?string $expectedValue = null)
    {
        return $this->requireDnsRecord('MX', $expectedValue);
    }

    /**
     * Require that the domain has DNS records.
     *
     * @return $this
     */
    public function requireDns()
    {
        $this->checkDns = true;

        return $this;
    }

    /**
     * Add a DNS record type requirement.
     *
     * @param  string  $type
     * @param  string|null  $expectedValue
     * @return $this
     */
    protected function requireDnsRecord(string $type, ?string $expectedValue = null)
    {
        if ($expectedValue !== null) {
            $this->dnsExpectedValues[$type] = $expectedValue;
        } else {
            if (!in_array($type, $this->dnsChecks)) {
                $this->dnsChecks[] = $type;
            }
        }

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $this->errorMessage = '';

        // First, validate domain format
        if (!$this->isValidDomainFormat($value)) {
            $this->errorMessage = $this->trans('validation.domain', ['attribute' => $attribute])
                ?: "The {$attribute} is not a valid domain.";
            return false;
        }

        // Perform DNS checks if configured
        if ($this->checkDns) {
            if (!$this->hasDnsRecords($value)) {
                $this->errorMessage = $this->trans('validation.domain_dns', ['attribute' => $attribute])
                    ?: "The {$attribute} must have valid DNS records.";
                return false;
            }
        }

        // Check for presence of specific DNS record types
        foreach ($this->dnsChecks as $type) {
            if (!$this->hasDnsRecord($value, $type)) {
                $recordType = strtoupper($type);
                $this->errorMessage = $this->trans('validation.domain_dns', ['attribute' => $attribute, 'type' => $recordType])
                    ?: "The {$attribute} must have valid {$recordType} records.";
                return false;
            }
        }

        // Check for DNS records with expected values
        foreach ($this->dnsExpectedValues as $type => $expectedValue) {
            if (!$this->dnsRecordMatchesValue($value, $type, $expectedValue)) {
                $recordType = strtoupper($type);
                $this->errorMessage = $this->trans('validation.domain_dns_value', [
                    'attribute' => $attribute,
                    'type' => $recordType,
                    'value' => $expectedValue,
                ]) ?: "The {$attribute} must have a {$recordType} record pointing to {$expectedValue}.";
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the value is a valid domain format.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidDomainFormat($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-æøå]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/isu', $value);
    }

    /**
     * Check if the domain has DNS records.
     *
     * @param  string  $domain
     * @return bool
     */
    protected function hasDnsRecords(string $domain): bool
    {
        $types = [DNS_A, DNS_AAAA, DNS_CNAME, DNS_TXT, DNS_MX, DNS_NS, DNS_SOA];

        foreach ($types as $type) {
            $records = @dns_get_record($domain, $type);
            if ($records !== false && count($records) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the domain has a specific DNS record type.
     *
     * @param  string  $domain
     * @param  string  $type
     * @return bool
     */
    protected function hasDnsRecord(string $domain, string $type): bool
    {
        $dnsType = $this->getDnsConstant($type);

        if ($dnsType === null) {
            return false;
        }

        $records = @dns_get_record($domain, $dnsType);

        return $records !== false && count($records) > 0;
    }

    /**
     * Check if a DNS record matches an expected value.
     *
     * @param  string  $domain
     * @param  string  $type
     * @param  string  $expectedValue
     * @return bool
     */
    protected function dnsRecordMatchesValue(string $domain, string $type, string $expectedValue): bool
    {
        $dnsType = $this->getDnsConstant($type);

        if ($dnsType === null) {
            return false;
        }

        $records = @dns_get_record($domain, $dnsType);

        if ($records === false || count($records) === 0) {
            return false;
        }

        // Check if any record matches the expected value
        foreach ($records as $record) {
            $recordValue = $this->extractRecordValue($record, $type);

            if ($recordValue !== null && $this->matchesExpectedValue($recordValue, $expectedValue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the value from a DNS record based on its type.
     *
     * @param  array  $record
     * @param  string  $type
     * @return string|null
     */
    protected function extractRecordValue(array $record, string $type): ?string
    {
        return match (strtoupper($type)) {
            'A' => $record['ip'] ?? null,
            'AAAA' => $record['ipv6'] ?? null,
            'CNAME' => $record['target'] ?? null,
            'TXT' => $record['txt'] ?? null,
            'MX' => $record['target'] ?? null,
            default => null,
        };
    }

    /**
     * Check if a record value matches the expected value.
     *
     * @param  string  $recordValue
     * @param  string  $expectedValue
     * @return bool
     */
    protected function matchesExpectedValue(string $recordValue, string $expectedValue): bool
    {
        // Normalize values for comparison
        $recordValue = strtolower(rtrim($recordValue, '.'));
        $expectedValue = strtolower(rtrim($expectedValue, '.'));

        return $recordValue === $expectedValue;
    }

    /**
     * Get the DNS constant for a record type.
     *
     * @param  string  $type
     * @return int|null
     */
    protected function getDnsConstant(string $type): ?int
    {
        return match (strtoupper($type)) {
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'CNAME' => DNS_CNAME,
            'TXT' => DNS_TXT,
            'MX' => DNS_MX,
            'NS' => DNS_NS,
            'SOA' => DNS_SOA,
            default => null,
        };
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage ?: $this->trans('validation.domain')
            ?: 'The :attribute is not a valid domain.';
    }

    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array  $replace
     * @return string|null
     */
    protected function trans(string $key, array $replace = []): ?string
    {
        // @phpstan-ignore-next-line - trans() is a Laravel global helper
        if (function_exists('trans')) {
            /** @phpstan-ignore-next-line */
            $translation = \trans($key, $replace);
            // If translation returns the key itself, it means no translation was found
            return $translation !== $key ? $translation : null;
        }

        // @phpstan-ignore-next-line - __() is a Laravel global helper
        if (function_exists('__')) {
            /** @phpstan-ignore-next-line */
            $translation = \__($key, $replace);
            return $translation !== $key ? $translation : null;
        }

        return null;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
