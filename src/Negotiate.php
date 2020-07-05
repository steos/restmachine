<?php

namespace RestMachine;

class Negotiate {

    static function bestAllowedContentType(array $acceptable, array $allowed) {
        foreach ($acceptable as $accept) {
            foreach ($allowed as $type) {
                if ($match = self::acceptableType($type, $accept)) {
                    return $match;
                }
            }
        }
        return null;
    }

    static function acceptableType($type, $acceptable) {
        if ($type == $acceptable || $acceptable == '*/*') {
            return $type;
        }
        if ($type == '*/*') {
            return $acceptable;
        }
        list($tmaj, $tmin) = explode('/', $type, 2);
        list($amaj, $amin) = explode('/', $acceptable, 2);
        if ($tmaj == $amaj) {
            if ($tmin == '*') {
                return $acceptable;
            } else if ($amin == '*') {
                return $type;
            }
        }
        return null;
    }
}
