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

    static function setHeadersMaybe(Response $response, $headers) {
        $response->headers->add(array_filter($headers));
    }

    static function httpDate(\DateTime $date) {
        return $date->format(\DateTime::RFC1123);
    }

    static function parseHttpDate($str) {
        // TODO handle RFC850/1036 and ANSI C's asctime() format as per rfc 2616
        // http://tools.ietf.org/html/rfc2616#section-3.3
        // quote: "clients and servers that parse the date value MUST accept all three formats"
        return \DateTime::createFromFormat(\DateTime::RFC1123, $str);
    }
}
