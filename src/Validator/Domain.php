<?php

namespace dacoto\DomainValidator\Validator;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class Domain
 * @package dacoto\DomainValidator
 */
class Domain implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (stripos($value, 'localhost') !== false) {
            return true;
        }

        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-æøå]{0,61}[a-z0-9])?\.)+.[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/isu', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('The :attribute is not a valid domain.');
    }
}
