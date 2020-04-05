# Laravel Domain Validation

![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/dacoto/laravel-domain-validation/CI/master)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
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
