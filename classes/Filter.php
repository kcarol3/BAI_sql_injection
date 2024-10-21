<?php

class Filter
{
    public static function filterName($name)
    {
        return filter_var(trim($name), FILTER_SANITIZE_STRING);
    }

    public static function filterEmail($email)
    {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function filterURL($url)
    {
        return filter_var(trim($url), FILTER_VALIDATE_URL);
    }

    public static function filterContent($content)
    {
        return filter_var(trim($content), FILTER_SANITIZE_STRING);
    }

    public static function filter($data, $type)
    {
        switch ($type) {
            case 'name':
                return self::filterName($data);
            case 'email':
                return self::filterEmail($data);
            case 'url':
                return self::filterURL($data);
            default:
                return self::filterContent($data);
        }
    }
}
