<?php

namespace dacoto\DomainValidator;

use dacoto\DomainValidator\Validator\Domain;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Class LaravelDomainValidationServiceProvider
 * @package dacoto\DomainValidator
 */
class LaravelDomainValidationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Validator::extend('domain', Domain::class);
    }
}
