# Laravel Domain Validation

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
