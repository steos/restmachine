<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Response;

class Utils {
    static function paramCase($str) {
        return implode('-', array_map('strtolower', self::splitWhen($str, 'ctype_upper')));
    }

    static function splitWhen($str, callable $pred) {
        $xs = [];
        $offset = 0;
        for ($i = 0, $len = strlen($str); $i < $len; ++$i) {
            if (call_user_func($pred, $str[$i])) {
                $xs[] = substr($str, $offset, $i - $offset);
                $offset = $i;
            }
        }
        if ($offset < $len - 1) {
            $xs[] = substr($str, $offset);
        }
        return $xs;
    }

    static function setHeaderMaybe(Response $response, $header, $value) {
        if ($value) {
            $response->headers->set($header, $value);
        }
    }

    static function httpDate(\DateTime $date) {
        return $date->format(\DateTime::RFC1123);
    }
}
