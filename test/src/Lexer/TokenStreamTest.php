<?php declare(strict_types=1);

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.txt>.
 */

namespace Bitnix\Parse\Lexer;

use InvalidArgumentException,
    RuntimeException,
    Bitnix\Parse\ParseFailure,
    PHPUnit\Framework\TestCase;

class TokenStreamTest extends TestCase {

    public function testNext() {

        $input = "1 2\n3 4";
        $tokens = new TokenSet([
            'T_INT'  => '\d+'
        ], [
            'T_SKIP' => '\s+'
        ]);

        $stream = TokenStream::fromString($tokens, $input);

        $this->assertTrue($stream->valid());

        $pos = $stream->position();
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(1, $pos->column());
        $this->assertEquals("1 2\n", $pos->buffer());

        $token = $stream->next();
        $this->assertEquals(1, $token->lexeme());
        $this->assertTrue($stream->valid());

        $pos = $stream->position();
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(3, $pos->column());
        $this->assertEquals("1 2\n", $pos->buffer());

        $token = $stream->next();
        $this->assertEquals(2, $token->lexeme());
        $this->assertTrue($stream->valid());

        $pos = $stream->position();
        $this->assertEquals(2, $pos->line());
        $this->assertEquals(1, $pos->column());
        $this->assertEquals("3 4", $pos->buffer());

        $token = $stream->next();
        $this->assertEquals(3, $token->lexeme());
        $this->assertTrue($stream->valid());

        $pos = $stream->position();
        $this->assertEquals(2, $pos->line());
        $this->assertEquals(3, $pos->column());
        $this->assertEquals("3 4", $pos->buffer());

        $token = $stream->next();
        $this->assertEquals(4, $token->lexeme());
        $this->assertFalse($stream->valid());

        $pos = $stream->position();
        $this->assertEquals(2, $pos->line());
        $this->assertEquals(3, $pos->column());
        $this->assertEquals("3 4", $pos->buffer());

        $token = $stream->next();
        $this->assertEquals('T_EOS', $token->type());
        $this->assertSame($token, $stream->next());
    }

    public function testNextError() {
        $this->expectException(ParseFailure::CLASS);

        $tokens = new TokenSet([
            'T_INT'  => '\d+'
        ], [
            'T_SKIP' => '\s+'
        ]);

        $stream = TokenStream::fromString($tokens, 'This will fail');
        $stream->next();
    }

    public function testStackSizeError() {
        $this->expectException(InvalidArgumentException::CLASS);
        TokenStream::fromString(
            $this->createMock(State::CLASS),
            'this will fail',
            [TokenStream::STACK_SIZE => -1]
        );
    }

    public function testStack() {
        $main = $this->createMock(State::CLASS);
        $first = $this->createMock(State::CLASS);
        $second = $this->createMock(State::CLASS);
        $third = $this->createMock(State::CLASS);

        $tokens = TokenStream::fromString($main, '...', [TokenStream::STACK_SIZE => 2]);

        try {
            $tokens->pop();
            $this->fail('Stack pop failed to throw exception...');
        } catch (RuntimeException $x) {}

        $tokens->push($first);
        $tokens->push($second);

        try {
            $tokens->push($third);
            $this->fail('Stack push failed to throw exception...');
        } catch (RuntimeException $x) {}

        $this->assertSame($second, $tokens->pop());
        $this->assertSame($first, $tokens->pop());

        try {
            $tokens->pop();
            $this->fail('Stack pop failed to throw exception...');
        } catch (RuntimeException $x) {}
    }

    public function testCreateFromFile() {
        $main = new TokenSet([
            'T_INT'  => '\d+'
        ], [
            'T_SKIP' => '\s+'
        ]);
        $stream = TokenStream::fromFile($main, __DIR__ . '/_empty', [TokenStream::EOS_TOKEN => 'T_XXX']);
        $this->assertEquals('T_XXX', $stream->next()->type());
    }

    public function testCreateFromFileError() {
        $this->expectException(InvalidArgumentException::CLASS);
        $main = $this->createMock(State::CLASS);
        TokenStream::fromFile($main, __DIR__ . '/_not_found');
    }

    public function testCreateFromStream() {
        $main = new TokenSet([
            'T_INT'  => '\d+'
        ], [
            'T_SKIP' => '\s+'
        ]);
        $stream = TokenStream::fromStream($main, \fopen('php://memory', 'wb+'), [TokenStream::EOS_TOKEN => 'T_XXX']);
        $this->assertEquals('T_XXX', $stream->next()->type());
    }

    public function testCreateFromStreamError() {
        $this->expectException(InvalidArgumentException::CLASS);
        $main = $this->createMock(State::CLASS);
        TokenStream::fromStream($main, $this);
    }

    public function testReadError() {
        $this->expectException(RuntimeException::CLASS);

        $input = \fopen('php://memory', 'wb+');
        \fwrite($input, "1\n2");
        \rewind($input);

        $main = new TokenSet([
            'T_INT'  => '\d+'
        ], [
            'T_SKIP' => '\s+'
        ]);

        $tokens = TokenStream::fromStream($main, $input);

        \fclose($input);

        $tokens->next();
    }

    public function testToString() {
        $stream = TokenStream::fromString(
            $this->createMock(State::CLASS),
            'foo bar baz'
        );
        $this->assertIsString((string) $stream);
    }
}
