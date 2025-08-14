<?php

namespace App\Infrastructure\Http\Controllers\Validators;

use Respect\Validation\Exceptions\ValidationException;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class Validator
{
    protected static function extractJsonData(Request $request, bool $allowEmpty = false): array
    {
        $body = $request->getBody()->getContents();

        if (empty($body)) {
            if (!$allowEmpty) {
                throw new \InvalidArgumentException('Request body is empty');
            }
            return [];
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON format: ' . json_last_error_msg());
        }

        return $data ?? [];
    }
    
    protected static function validateData(array $data, array $rules): void
    {
        $errors = [];

        foreach ($rules as $field => $validator) {
            $value = $data[$field] ?? null;

            try {
                $validator->assert($value);
            } catch (ValidationException $e) {
                $errors[] = "{$field}: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }
    }
}
