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

            if (method_exists($validator, 'setName')) {
                $validator->setName($field);
            }

            try {
                $validator->assert($value);
            } catch (ValidationException $e) {
                $messages = method_exists($e, 'getMessages') ? $e->getMessages() : [$e->getMessage()];
                foreach ($messages as $message) {
                    $errors[] = $message;
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Bad request: ' . implode(', ', $errors));
        }
    }
}
