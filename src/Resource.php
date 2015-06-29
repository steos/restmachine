<?php

namespace RestMachine;

/**
 * Resource definition builder.
 *
 * @method \RestMachine\Resource handleOk(mixed $value)
 * @method \RestMachine\Resource handleCreated(mixed $value)
 *
 * @method \RestMachine\Resource post(mixed $value)
 * @method \RestMachine\Resource put(mixed $value)
 * @method \RestMachine\Resource patch(mixed $value)
 * @method \RestMachine\Resource delete(mixed $value)
 *
 * @method \RestMachine\Resource allowedMethods(mixed $value)
 * @method \RestMachine\Resource availableMediaTypes(mixed $value)
 * @method \RestMachine\Resource lastModified(mixed $value)
 * @method \RestMachine\Resource etag(mixed $value)
 *
 * @method \RestMachine\Resource isMalformed(mixed $value)
 * @method \RestMachine\Resource isProcessable(mixed $value)
 */
class Resource {
    private $config;

    static function create(Resource $default = null) {
        return new self($default ? $default->config : []);
    }

    function __construct(array $defaults = []) {
        $this->config = array_merge(ResourceDefaults::create(), $defaults);
    }

    function copy() {
        return new self($this->config);
    }

    function has($key) {
        return array_key_exists($key, $this->config);
    }

    function value($key, $context, $default = null) {
        if (!$this->has($key)) {
            return $default;
        }
        $value = $this->config[$key];
        return is_callable($value)
            ? call_user_func($value, $context)
            : $value;
    }

    function __invoke($key, $context, $default = null) {
        return $this->value($key, $context, $default);
    }

    public function __call($method, array $args) {
        if (count($args) != 1) throw new \InvalidArgumentException();
        $this->config[$this->keyOf($method)] = $args[0];
        return $this;
    }

    private function keyOf($method) {
        if (in_array($method, ['put', 'post', 'patch', 'delete'])) {
            return $method . '!';
        }
        $key = Utils::paramCase($method);
        if (strlen($key) > 3 && substr($key, 0, 3) == 'is-') {
            return substr($key, 3) . '?';
        }
        return $key;
    }
}
