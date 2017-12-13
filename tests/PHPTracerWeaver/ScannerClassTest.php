<?php

use PHPUnit\Framework\TestCase;

class TestOfClassScanner extends TestCase
{
    public function testCanTrackCurrentClass()
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php class Foo { function bar($x) {}');
        $token_stream->iterate($scanner);
        $this->assertSame('Foo', $scanner->getCurrentClass());
    }

    public function testForgetsClassWhenScopeEnds()
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php class Foo { function bar($x) {} }');
        $token_stream->iterate($scanner);
        $this->assertNull($scanner->getCurrentClass());
    }

    public function testForgetsClassWhenScopeEndsWithinNestedScopes()
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php while (true) { class Foo { function bar($x) {} } }');
        $token_stream->iterate($scanner);
        $this->assertNull($scanner->getCurrentClass());
    }
}
