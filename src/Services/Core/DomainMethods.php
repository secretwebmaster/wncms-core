<?php

namespace Wncms\Services\Core;

trait DomainMethods
{
    public function addHttp(?string $link): ?string
    {
        if ($link === null) {
            return null;
        }

        $scheme = parse_url($link, PHP_URL_SCHEME);

        if (empty($scheme)) {
            $link = 'http://' . ltrim($link, '/');
        }

        return $link;
    }

    public function addHttps(?string $link): ?string
    {
        if ($link === null) {
            return null;
        }

        $scheme = parse_url($link, PHP_URL_SCHEME);

        if (empty($scheme)) {
            $link = 'https://' . ltrim($link, '/');
        }

        return $link;
    }

    public function getDomain(?string $url = null): ?string
    {
        if (!$url) {
            return str_replace('www.', '', request()->getHost());
        }

        return !empty(parse_url($url)['host'] ?? parse_url($url)['path'])
            ? str_replace('www.', '', parse_url($url)['host'] ?? parse_url($url)['path'])
            : null;
    }

    public function getDomainFromString(string $string, bool $includePort = true, bool $preserveWWW = false): ?string
    {
        $string = trim($string);
        $array = explode(" ", $string);

        foreach ($array as $stringToParse) {
            $pattern = '/\b[a-zA-Z0-9-]+\.[a-zA-Z0-9-:.]+\b/';
            preg_match($pattern, $stringToParse, $matches);

            if (!empty($matches[0])) {
                $url = $matches[0];
                $urlData = parse_url($url);

                if (!empty($urlData['host']) && !empty($urlData['port']) && $includePort) {
                    $result = $urlData['host'] . ":" . $urlData['port'];
                }

                if (!empty($urlData['host']) && (empty($urlData['port']) || empty($includePort))) {
                    $result = $urlData['host'];
                }

                if (!empty($urlData['path'])) {
                    $result = $urlData['path'];
                }

                if (!empty($result)) {
                    return $preserveWWW ? $result : str_replace("www.", '', $result);
                }
            }
        }

        return null;
    }
}
