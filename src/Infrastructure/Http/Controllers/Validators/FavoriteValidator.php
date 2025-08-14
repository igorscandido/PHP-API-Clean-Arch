<?php

namespace App\Infrastructure\Http\Controllers\Validators;

use Respect\Validation\Validator as v;
use Psr\Http\Message\ServerRequestInterface as Request;

class FavoriteValidator extends Validator
{
    public static function validateAddFavorite(Request $request): array
    {
        $data = self::extractJsonData($request);
        
        $rules = [
            'product_id' => v::intType()->positive()
        ];
        
        self::validateData($data, $rules);
        
        return $data;
    }
}
