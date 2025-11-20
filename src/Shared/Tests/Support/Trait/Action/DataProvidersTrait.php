<?php

namespace App\Shared\Tests\Support\Trait\Action;

trait DataProvidersTrait
{
    public static function invalidEmailProvider(): array
    {
        return [
            'missing @' => ['noemail.com'],
            'empty' => [''],
            'only spaces' => ['   '],
            'special chars' => ['test@#$%.com'],
        ];
    }

    public static function invalidPasswordProvider(): array
    {
        return [
            'too short' => ['Pass1!'],
            'no uppercase' => ['password123!'],
            'no lowercase' => ['PASSWORD123!'],
            'no numbers' => ['Password!'],
            'no special chars' => ['Password123'],
            'only spaces' => ['        '],
            'empty string' => [''],
        ];
    }
}
