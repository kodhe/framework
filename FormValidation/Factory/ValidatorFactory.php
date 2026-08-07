<?php

namespace Kodhe\FormValidation\Factory;

use Kodhe\FormValidation\Contracts\ValidatorInterface;
use Kodhe\FormValidation\Validators\RequiredValidator;
use Kodhe\FormValidation\Validators\NumericValidator;
use Kodhe\FormValidation\Validators\IntegerValidator;
use Kodhe\FormValidation\Validators\EmailValidator;
use Kodhe\FormValidation\Validators\UrlValidator;
use Kodhe\FormValidation\Validators\RegexValidator;
use Kodhe\FormValidation\Validators\MinLengthValidator;
use Kodhe\FormValidation\Validators\MaxLengthValidator;
use Kodhe\FormValidation\Validators\MatchesValidator;

class ValidatorFactory
{
    private static $instances = [];

    public static function make($type, $params = []): ?ValidatorInterface
    {
        $key = $type . '_' . md5(serialize($params));
        
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        $validator = self::createValidator($type, $params);
        
        if ($validator) {
            self::$instances[$key] = $validator;
        }
        
        return $validator;
    }

    private static function createValidator($type, $params = []): ?ValidatorInterface
    {
        switch ($type) {
            case 'required':
                return new RequiredValidator();
            case 'numeric':
                return new NumericValidator();
            case 'integer':
                return new IntegerValidator();
            case 'valid_email':
                return new EmailValidator();
            case 'valid_url':
                return new UrlValidator();
            case 'regex_match':
                $pattern = $params[0] ?? '';
                return new RegexValidator($pattern);
            case 'min_length':
                return new MinLengthValidator($params[0] ?? 0);
            case 'max_length':
                return new MaxLengthValidator($params[0] ?? 0);
            case 'matches':
                return new MatchesValidator($params[0] ?? '');
            default:
                return null;
        }
    }

    public static function clearCache()
    {
        self::$instances = [];
    }
}
