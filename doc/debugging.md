# Debugging

Sometimes it can be hard to figure out how RestMachine arrived at a specific handler and response. To make it easier
you can enable execution tracing.

```php
$webMachine->enableTrace();
```

If tracing is enabled RestMachine will remember the path it took through the decision graph and output `X-RestMachine-Trace` headers:

```
X-RestMachine-Trace: service-available?             -> true
X-RestMachine-Trace: known-method?                  -> true
X-RestMachine-Trace: uri-too-long?                  -> null
X-RestMachine-Trace: method-allowed?                -> true
X-RestMachine-Trace: malformed?                     -> false
X-RestMachine-Trace: authorized?                    -> true
X-RestMachine-Trace: allowed?                       -> true
X-RestMachine-Trace: valid-content-header?          -> true
X-RestMachine-Trace: known-content-type?            -> true
X-RestMachine-Trace: valid-entity-length?           -> true
X-RestMachine-Trace: is-options?                    -> false
X-RestMachine-Trace: accept-exists?                 -> true
X-RestMachine-Trace: media-type-available?          -> true
X-RestMachine-Trace: accept-language-exists?        -> null
X-RestMachine-Trace: accept-charset-exists?         -> null
X-RestMachine-Trace: accept-encoding-exists?        -> null
X-RestMachine-Trace: processable?                   -> true
X-RestMachine-Trace: exists?                        -> true
X-RestMachine-Trace: if-match-exists?               -> false
X-RestMachine-Trace: if-unmodified-since-exists?    -> false
X-RestMachine-Trace: if-none-match-exists?          -> false
X-RestMachine-Trace: if-modified-since-exists?      -> false
X-RestMachine-Trace: method-delete?                 -> false
X-RestMachine-Trace: method-patch?                  -> false
X-RestMachine-Trace: post-to-existing?              -> false
X-RestMachine-Trace: put-to-existing?               -> false
X-RestMachine-Trace: multiple-representations?      -> null
X-RestMachine-Trace: handle-ok
```
