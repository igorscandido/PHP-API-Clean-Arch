<?php

namespace App\Infrastructure\Http\Controllers\Validators;

use Respect\Validation\Validator as v;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClientValidator extends Validator
{
    public static function validateCreation(Request $request): array
    {
        $data = self::extractJsonData($request);

        $rules = [
            'name' => v::stringType()->notEmpty()->length(1, 255),
            'email' => v::email()->length(1, 255),
            'password' => v::stringType()->length(6, null)
        ];

        self::validateData($data, $rules);
        return $data;
    }

    public static function validateUpdate(Request $request): array
    {
        $data = self::extractJsonData($request, true);
        
        $rules = [
            'name' => v::optional(v::stringType()->notEmpty()->length(1, 255)),
            'email' => v::optional(v::email()->length(1, 255)),
            'password' => v::optional(v::stringType()->length(6, null))
        ];
        
        self::validateData($data, $rules);
        return $data;
    }
}
