<?php

namespace RestMachine;

/**
 * Resource definition builder.
 *
 * @method \RestMachine\Resource handleOk(mixed $value)
 * @method \RestMachine\Resource handleCreated(mixed $value)
 * @method \RestMachine\Resource handleNotFound(mixed $value)
 * @method \RestMachine\Resource handleMalformed(mixed $value)
 * @method \RestMachine\Resource handleServiceNotAvailable(mixed $value)
 * @method \RestMachine\Resource handleAccepted(mixed $value)
 * @method \RestMachine\Resource handleUriTooLong(mixed $value)
 * @method \RestMachine\Resource handlePreconditionFailed(mixed $value)
 * @method \RestMachine\Resource handleForbidden(mixed $value)
 * @method \RestMachine\Resource handleUnauthorized(mixed $value)
 * @method \RestMachine\Resource handleUnprocessableEntity(mixed $value)
 * @method \RestMachine\Resource handleNotAcceptable(mixed $value)
 * @method \RestMachine\Resource handleUnsupportedMediaType(mixed $value)
 * @method \RestMachine\Resource handleNotImplemented(mixed $value)
 * @method \RestMachine\Resource handleUnknownMethod(mixed $value)
 * @method \RestMachine\Resource handleMultipleRepresentations(mixed $value)
 * @method \RestMachine\Resource handleMovedTemporarily(mixed $value)
 * @method \RestMachine\Resource handleNotModified(mixed $value)
 * @method \RestMachine\Resource handleSeeOther(mixed $value)
 * @method \RestMachine\Resource handleMovedPermanently(mixed $value)
 * @method \RestMachine\Resource handleNoContent(mixed $value)
 * @method \RestMachine\Resource handleException(mixed $value)
 * @method \RestMachine\Resource handleConflict(mixed $value)
 * @method \RestMachine\Resource handleMethodNotAllowed(mixed $value)
 * @method \RestMachine\Resource handleRequestEntityTooLarge(mixed $value)
 * @method \RestMachine\Resource handleGone(mixed $value)
 * @method \RestMachine\Resource handleOptions(mixed $value)
 *
 * @method \RestMachine\Resource isMalformed(mixed $value)
 * @method \RestMachine\Resource isProcessable(mixed $value)
 * @method \RestMachine\Resource isNew(mixed $value)
 * @method \RestMachine\Resource isPostRedirect(mixed $value)
 * @method \RestMachine\Resource isMovedTemporarily(mixed $value)
 * @method \RestMachine\Resource isValidEntityLength(mixed $value)
 * @method \RestMachine\Resource isMediaTypeAvailable(mixed $value)
 * @method \RestMachine\Resource isKnownMethod(mixed $value)
 * @method \RestMachine\Resource isModifiedSince(mixed $value)
 * @method \RestMachine\Resource isMethodPut(mixed $value)
 * @method \RestMachine\Resource isPostToMissing(mixed $value)
 * @method \RestMachine\Resource isPutToDifferentUrl(mixed $value)
 * @method \RestMachine\Resource isLanguageAvailable(mixed $value)
 * @method \RestMachine\Resource isValidContentHeader(mixed $value)
 * @method \RestMachine\Resource isConflict(mixed $value)
 * @method \RestMachine\Resource isPutToExisting(mixed $value)
 * @method \RestMachine\Resource isAllowed(mixed $value)
 * @method \RestMachine\Resource isUnmodifiedSince(mixed $value)
 * @method \RestMachine\Resource isDeleteEnacted(mixed $value)
 * @method \RestMachine\Resource isCharsetAvailable(mixed $value)
 * @method \RestMachine\Resource isMethodPatch(mixed $value)
 * @method \RestMachine\Resource isMethodDelete(mixed $value)
 * @method \RestMachine\Resource isKnownContentType(mixed $value)
 * @method \RestMachine\Resource isMovedPermanently(mixed $value)
 * @method \RestMachine\Resource isMultipleRepresentations(mixed $value)
 * @method \RestMachine\Resource isRespondWithEntity(mixed $value)
 * @method \RestMachine\Resource isMethodAllowed(mixed $value)
 * @method \RestMachine\Resource isUriTooLong(mixed $value)
 * @method \RestMachine\Resource isPostToGone(mixed $value)
 * @method \RestMachine\Resource isAuthorized(mixed $value)
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
        $key = Utils::paramCase($method);
        if (strpos($key, 'is-') === 0) {
            return substr($key, 3) . '?';
        }
        return $key;
    }

    // directives

    function allowedMethods($value) {
        $this->config['allowed-methods'] = $value;
        return $this;
    }

    function availableMediaTypes($value) {
        $this->config['available-media-types'] = $value;
        return $this;
    }

    function availableLanguages($value) {
        $this->config['available-languages'] = $value;
        return $this;
    }

    function availableCharsets($value) {
        $this->config['available-charsets'] = $value;
        return $this;
    }

    function availableEncodings($value) {
        $this->config['available-encodings'] = $value;
        return $this;
    }

    function lastModified($value) {
        $this->config['last-modified'] = $value;
        return $this;
    }

    function etag($value) {
        $this->config['etag'] = $value;
        return $this;
    }

    // actions

    function put($value) {
        $this->config['put!'] = $value;
        return $this;
    }

    function post($value) {
        $this->config['post!'] = $value;
        return $this;
    }

    function delete($value) {
        $this->config['delete!'] = $value;
        return $this;
    }

    function patch($value) {
        $this->config['patch!'] = $value;
        return $this;
    }

    // irregular decisions

    function exists($value) {
        $this->config['exists?'] = $value;
        return $this;
    }

    function existed($value) {
        $this->config['existed?'] = $value;
        return $this;
    }

    function canPutToMissing($value) {
        $this->config['can-put-to-missing?'] = $value;
        return $this;
    }

    function canPostToMissing($value) {
        $this->config['can-post-to-missing?'] = $value;
        return $this;
    }

    function canPostToGone($value) {
        $this->config['can-post-to-gone?'] = $value;
        return $this;
    }

    function isOptions($value) {
        $this->config['is-options?'] = $value;
        return $this;
    }

    function ifMatchStar($value) {
        $this->config['if-match-star?'] = $value;
        return $this;
    }

    function ifModifiedSinceExists($value) {
        $this->config['if-modified-since-exists?'] = $value;
        return $this;
    }

    function ifMatchExists($value) {
        $this->config['if-match-exists?'] = $value;
        return $this;
    }

    function ifMatchStarExistsForMissing($value) {
        $this->config['if-match-star-exists-for-missing?'] = $value;
        return $this;
    }

    function ifNoneMatchStar($value) {
        $this->config['if-none-match-star?'] = $value;
        return $this;
    }

    function ifNoneMatchExists($value) {
        $this->config['if-none-match-exists?'] = $value;
        return $this;
    }

    function ifModifiedSinceValidDate($value) {
        $this->config['if-modified-since-valid-date?'] = $value;
        return $this;
    }

    function ifUnmodifiedSinceValidDate($value) {
        $this->config['if-unmodified-since-valid-date?'] = $value;
        return $this;
    }

    function ifUnmodifiedSinceExists($value) {
        $this->config['if-unmodified-since-exists?'] = $value;
        return $this;
    }

    function ifNoneMatch($value) {
        $this->config['if-none-match?'] = $value;
        return $this;
    }

    function acceptExists($value) {
        $this->config['accept-exists?'] = $value;
        return $this;
    }

    function acceptCharsetExists($value) {
        $this->config['accept-charset-exists?'] = $value;
        return $this;
    }

    function acceptEncodingExists($value) {
        $this->config['accept-encoding-exists?'] = $value;
        return $this;
    }

    function etagMatchesForIfMatch($value) {
        $this->config['etag-matches-for-if-match?'] = $value;
        return $this;
    }

    function etagMatchesForIfNone($value) {
        $this->config['etag-matches-for-if-none?'] = $value;
        return $this;
    }
}
