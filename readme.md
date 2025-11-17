# Laravel Domain Validation

![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/dacoto/laravel-domain-validation/run-tests.yml)
![GitHub](https://img.shields.io/github/license/dacoto/laravel-domain-validation)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/dacoto/laravel-domain-validation)

A domain validator rule for Laravel 10.x and higher with optional DNS record verification.

## Features

- Validates domain format using regex
- Optional DNS record checking (A, AAAA, CNAME, TXT, MX records)
- Verify DNS records resolve to expected values
- Fluent API for chaining multiple DNS checks
- String-based validation syntax support
- Designed for external domains with DNS records

## Installation

```bash
composer require dacoto/laravel-domain-validation
```

## Basic Usage

### Simple Domain Validation

Validates that the input is a properly formatted domain:

```php
use dacoto\DomainValidator\Validator\Domain;

public function rules()
{
    return [
        'domain' => ['required', 'string', new Domain],
    ];
}
```

### String Syntax with DNS Checks

You can use the string syntax to check for the presence of DNS records:

```php
public function rules()
{
    return [

        // Require DNS records to exist (any type)
        'hostname' => ['required', 'domain:dns'],
                // Require MX records
        'domain' => ['required', 'domain:mx'],
        
        // Require multiple DNS record types
        'website' => ['required', 'domain:a,mx'],
        
    ];
}
```

### Fluent API

For more control, use the fluent API:

```php
use dacoto\DomainValidator\Validator\Domain;

public function rules()
{
    return [
        // Check for DNS records
        'hostname' => ['required', (new Domain)->requireDns()],
        // Check for MX records
        'email_domain' => ['required', (new Domain)->requireMx()],
        
        // Chain multiple DNS checks
        'website' => ['required', (new Domain)->requireA()->requireMx()],
        
    ];
}
```

## DNS Record Type Checks

### Available DNS Record Types

- `dns` - Checks for presence of any DNS record (A, AAAA, CNAME, TXT, MX, NS, or SOA)
- `a` - IPv4 address records
- `aaaa` - IPv6 address records
- `cname` - Canonical name records
- `txt` - Text records
- `mx` - Mail exchange records

### Verifying DNS Records Match Expected Values

You can verify that DNS records resolve to specific values using the fluent API:

```php
use dacoto\DomainValidator\Validator\Domain;

public function rules()
{
    return [
        // Require A record pointing to specific IP
        'domain' => ['required', (new Domain)->requireA('192.0.2.1')],
        
        // Require MX record pointing to specific mail server
        'email_domain' => ['required', (new Domain)->requireMx('mail.example.com')],
        
        // Multiple checks with specific values
        'website' => [
            'required',
            (new Domain)
                ->requireA('192.0.2.1')
                ->requireMx('mail.example.com')
        ],
    ];
}
```

#### Multiple Expected Values

When a domain has multiple DNS records of the same type (e.g., multiple A records for load balancing), the validator passes if **ANY** of the records match the expected value. You can chain multiple calls to verify multiple specific values:

```php
public function rules()
{
    return [
        // Verify domain has BOTH specific IPs
        // (each requireA checks if that IP exists among the A records)
        'cdn_domain' => [
            'required',
            (new Domain)
                ->requireA('104.16.132.229')
                ->requireA('104.16.133.229')
        ],
        
        // Example with Google's public DNS (dns.google.com)
        // which resolves to both 8.8.8.8 and 8.8.4.4
        'google_dns' => [
            'required',
            (new Domain)
                ->requireA('8.8.8.8')  // Passes if this IP is found
                ->requireA('8.8.4.4')  // Also passes if this IP is found
        ],
    ];
}
```

## Advanced Examples

### Form Request Validation

```php
use Illuminate\Foundation\Http\FormRequest;
use dacoto\DomainValidator\Validator\Domain;

class CreateWebsiteRequest extends FormRequest
{
    public function rules()
    {
        return [
            'domain' => [
                'required',
                'string',
                (new Domain)->requireA()->requireMx()
            ],
        ];
    }
}
```

### Manual Validator

```php
use Illuminate\Support\Facades\Validator;
use dacoto\DomainValidator\Validator\Domain;

$validator = Validator::make($request->all(), [
    'domain' => ['required', new Domain('mx', 'a')],
]);

if ($validator->fails()) {
    return redirect()->back()->withErrors($validator);
}
```

### Conditional DNS Checks

```php
use dacoto\DomainValidator\Validator\Domain;

public function rules()
{
    return [
        'domain' => [
            'required',
            (new Domain)
                ->requireA()
                ->when($this->check_email, function ($rule) {
                    return $rule->requireMx();
                })
        ],
    ];
}
```

## Error Messages

The validator provides specific error messages for different validation failures:

- **Invalid format**: "The {attribute} is not a valid domain."
- **Missing DNS records**: "The {attribute} must have valid {TYPE} records."
- **Missing any DNS**: "The {attribute} must have valid DNS records."
- **Value mismatch**: "The {attribute} must have a {TYPE} record pointing to {value}."

### Custom Error Messages

You can customize error messages in your language files:

```php
// resources/lang/en/validation.php

return [
    'domain' => 'The :attribute must be a valid domain name.',
    'domain_dns' => 'The :attribute must have valid :type DNS records.',
    'domain_dns_any' => 'The :attribute must have valid DNS records configured.',
    'domain_dns_value' => 'The :attribute must have a :type record pointing to :value.',
];
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or higher

## Testing

Run the test suite:

```bash
composer test
```

The package includes comprehensive tests covering:

- ✅ Basic domain format validation
- ✅ String-based syntax (via constructor: `new Domain('a')`, `new Domain('a', 'mx')`)
- ✅ Fluent API methods (chaining, expected values)
- ✅ All DNS record types (A, AAAA, CNAME, TXT, MX, ANY)
- ✅ Multiple expected values (load balancing scenarios)
- ✅ Error messages and edge cases
- ✅ Uses reliable test domain (dns.google.com)

**Test Results:** 20 tests, 37 assertions, 100% passing ✅

## License

This package is open-sourced software licensed under the MIT license.


