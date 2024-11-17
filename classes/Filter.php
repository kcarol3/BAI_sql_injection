<?php

class Filter
{
    public static function filterName($name)
    {
        return htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
    }

    public static function filterEmail($email)
    {
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        return $email ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : false;
    }

    public static function filterURL($url)
    {
        $url = filter_var(trim($url), FILTER_VALIDATE_URL);
        return $url ? htmlspecialchars($url, ENT_QUOTES, 'UTF-8') : false;
    }

    public static function filterContent($content)
    {
        // Trim spaces and escape HTML characters
        return htmlspecialchars(trim($content), ENT_QUOTES, 'UTF-8');
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

