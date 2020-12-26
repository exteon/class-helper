<?php

namespace Exteon;

use Exception;

abstract class ClassNameHelper
{
    /**
     * @param string[] $frags
     * @return string
     */
    static function joinNs(string ...$frags): string
    {
        foreach ($frags as $key => $value) {
            if (!$value) {
                unset($frags[$key]);
            }
        }
        return implode('\\', $frags);
    }

    /**
     * @param string $nsPrefix
     * @param string $nsName
     * @return string
     * @throws Exception
     */
    static function stripNsPrefix(string $nsPrefix, string $nsName): string
    {
        if (!static::isNsPrefix($nsPrefix, $nsName)) {
            throw new Exception("$nsPrefix is not a prefix of $nsName");
        }
        if ($nsPrefix) {
            return substr($nsName, strlen($nsPrefix) + 1);
        }
        return $nsName;
    }

    /**
     * @param string $nsPrefix
     * @param string $nsName
     * @return bool
     */
    static function isNsPrefix(string $nsPrefix, string $nsName): bool
    {
        if (!$nsPrefix) {
            return true;
        }
        $nsPrefix .= '\\';
        return ($nsPrefix === substr($nsName, 0, strlen($nsPrefix)));
    }

    /**
     * @param string $nsName
     * @return array {
     *      class: string
     *      ns: string
     * }
     */
    static function toNsClass(string $nsName): array
    {
        $frags = static::nsToFragments($nsName);
        $result = [];
        $result['class'] = array_pop($frags);
        $result['ns'] = implode('\\', $frags);
        return $result;
    }

    /**
     * @param string $nsName
     * @return string
     * @throws Exception
     */
    static function trimNsLeading(string $nsName): string
    {
        if ($nsName[0] === '\\') {
            $nsNameFx = substr($nsName, 1);
            if ($nsNameFx[0] === '\\') {
                throw new Exception("Malformed namespace name $nsName");
            }
        } else {
            $nsNameFx = $nsName;
        }
        return $nsNameFx;
    }

    /**
     * @param string $nsName
     * @return string
     * @throws Exception
     */
    static function addNsLeading(string $nsName): string
    {
        if ($nsName[0] === '\\') {
            throw new Exception("Malformed namespace name $nsName");
        }
        return '\\' . $nsName;
    }

    /**
     * @param string $nsName
     * @return bool
     */
    static function hasNsLeading(string $nsName): bool
    {
        return ($nsName[0] === '\\');
    }

    /**
     * @param string $nsName
     * @return string[]
     */
    static function nsToFragments(string $nsName): array
    {
        return explode('\\', $nsName);
    }
}