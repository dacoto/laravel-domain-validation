# Laravel Domain Validation

![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/dacoto/laravel-domain-validation/CI/master)
![GitHub](https://img.shields.io/github/license/dacoto/laravel-domain-validation)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/dacoto/laravel-domain-validation)

A domain validator rule for Laravel 6.x and higher.

## Usage

```
use dacoto\DomainValidator\Validator\Domain;

public function rules()
{
    return [
        'domain' => ['required', 'string', new Domain],
    ];
}
```
