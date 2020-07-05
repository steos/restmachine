<?php

namespace RestMachine;

use PHPUnit\Framework\TestCase;

class NegotiateTest extends TestCase {
    function testAcceptableType() {
        $this->assertEquals('text/html', Negotiate::acceptableType('text/html', 'text/html'));
        $this->assertEquals('text/html', Negotiate::acceptableType('text/html', 'text/*'));
        $this->assertEquals('text/html', Negotiate::acceptableType('text/html', '*/*'));
        $this->assertEquals('text/html', Negotiate::acceptableType('*/*', 'text/html'));
        $this->assertEquals('text/html', Negotiate::acceptableType('text/*', 'text/html'));
        $this->assertNull(Negotiate::acceptableType('text/html', 'text/json'));
    }

    function testBestAllowedContentType() {
        $this->assertEquals('text/html', Negotiate::bestAllowedContentType(['text/*'], ['text/html', 'text/plain']));
        $this->assertEquals('text/plain', Negotiate::bestAllowedContentType(['text/plain', 'text/html'], ['text/html', 'text/plain']));
        $this->assertNull(Negotiate::bestAllowedContentType(['application/json'], ['text/html', 'text/plain']));
    }
}