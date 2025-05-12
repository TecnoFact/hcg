<?php

namespace App\Services\Exceptions;

use Exception;

class CfdiValidationException extends Exception
{
    protected array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Errores de validación CFDI');
        $this->errors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->errors;
    }
}
