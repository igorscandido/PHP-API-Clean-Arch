<?php

namespace App\Infrastructure\Http\Controllers\Validators;

use Respect\Validation\Validator as v;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthValidator extends Validator
{
    public static function validateLogin(Request $request): array
    {
        $data = self::extractJsonData($request);
        
        $rules = [
            'email' => v::email(),
            'password' => v::stringType()->notEmpty()->length(6, null)
        ];
        
        self::validateData($data, $rules);
        return $data;
    }
}
