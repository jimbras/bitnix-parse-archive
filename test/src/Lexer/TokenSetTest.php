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
    LogicException,
    RuntimeException,
    Bitnix\Parse\Token,
    PHPUnit\Framework\TestCase;

class TokenSetTest extends TestCase {

    public function testMatchReturnsToken() {
        $called = false;
        $stack = $this->createMock(Stack::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo'], [], [
            'T_FOO' => function($s, $t) use ($stack, &$called) {
                $called = true;
                $this->assertSame($stack, $s);
                $this->assertInstanceOf(Token::CLASS, $t);
                $this->assertEquals('T_FOO', $t->type());
                $this->assertEquals('foo', $t->lexeme());
            }
        ]);

        $token = $tokens->match($stack, 'foo is bar', 0);
        $this->assertInstanceOf(Token::CLASS, $token);
        $this->assertEquals('T_FOO', $token->type());
        $this->assertEquals('foo', $token->lexeme());
        $this->assertTrue($called);
    }

    public function testMatchReturnsNull() {
        $called = false;
        $stack = $this->createMock(Stack::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo'], [], [
            'T_FOO' => function($s, $t) use ($stack, &$called) {
                $called = true;
            }
        ]);

        $this->assertNull($tokens->match($stack, 'foo is bar', 3));
        $this->assertFalse($called);
    }

    public function testSkipReturnsSkippedBytes() {
        $tokens = new TokenSet(
            ['T_FOO' => 'foo'],
            ['T_SKIP' => '\s+']
        );
        $this->assertEquals(2, $tokens->skip('  foo is bar', 0));
        $this->assertEquals(0, $tokens->skip('  foo is bar', 3));
    }

    public function testEmptyMatchersAreNotAllowed() {
        $this->expectException(InvalidArgumentException::CLASS);
        $tokens = new TokenSet([]);
    }

    public function testMatchersMustCompile() {
        $this->expectException(InvalidArgumentException::CLASS);
        $tokens = new TokenSet(['T_FOO' => '(']);
    }

    public function testSkippersMustCompile() {
        $this->expectException(InvalidArgumentException::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo'], ['T_SKIP' => '(']);
    }

    public function testInvalidMatch() {
        $this->expectException(RuntimeException::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo|fuu', 'T_BAR' => 'bar']);
        $stack = $this->createMock(Stack::CLASS);
        $tokens->match($stack, 'foo', 0);
    }

    public function testCannotBindToUnknownMatchers() {
        $this->expectException(LogicException::CLASS);
        $tokens = new TokenSet(['T_FOO' => 'foo'], [], [
            'T_BAR' => function() {}
        ]);
    }

    public function testToString() {
        $this->assertIsString((string) new TokenSet(['T_FOO' => 'foo']));
    }
}
