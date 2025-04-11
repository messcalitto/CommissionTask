<?php
namespace App\Config;

class UserType
{
    const PRIVATE = 'private';
    const BUSINESS = 'business';

    public static function isValid(string $userType): bool
    {
        return in_array($userType, [self::PRIVATE, self::BUSINESS], true);
    }
}