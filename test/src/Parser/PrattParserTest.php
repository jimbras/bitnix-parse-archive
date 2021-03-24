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

namespace Bitnix\Parse\Parser;

use Bitnix\Parse\Expression,
    Bitnix\Parse\Lexer,
    Bitnix\Parse\Token,
    Bitnix\Parse\Lexer\Scanner,
    Bitnix\Parse\Lexer\TokenSet,
    Bitnix\Parse\Lexer\TokenStream,
    PHPUnit\Framework\TestCase;

class Value implements Expression {
    public function __construct(public int $value) {}
}

class PrattParserTest extends TestCase {

    private ?Lexer $lexer = null;
    private ?Grammar $grammar = null;
    private ?PrattParser $parser = null;

    public function setUp() : void {
        $this->lexer = $this->createMock(Lexer::CLASS);
        $this->grammar = $this->createMock(Grammar::CLASS);
        $this->parser = $this->getMockBuilder(PrattParser::CLASS)
            ->setConstructorArgs([$this->lexer, $this->grammar])
            ->getMockForAbstractClass();
    }

    public function testLexer() {
        $this->assertSame($this->lexer, $this->parser->lexer());
    }

    public function testExpression() {
        $tokens = [
            new Token('T_INT', '1'),
            new Token('T_ADD', '+'),
            new Token('T_INT', '2'),
            new Token('T_MUL', '*'),
            new Token('T_INT', '3'),
            new Token('T_MUL', '*'),
            new Token('T_INT', '4'),
            new Token('T_ADD', '+'),
            new Token('T_INT', '5')
        ];
        $this->lexer
            ->expects($this->any())
            ->method('next')
            ->will($this->returnCallback(function() use (&$tokens) {
                return $tokens ? \array_shift($tokens) : new Token('T_EOF');
            }));
        $this->lexer
            ->expects($this->any())
            ->method('peek')
            ->will($this->returnCallback(function() use (&$tokens) {
                return $tokens[0] ??= new Token('T_EOS');
            }));
        $this->grammar
            ->expects($this->any())
            ->method('precedence')
            ->will($this->returnCallback(function($token) {
                $value = 0;
                switch ($token->type()) {
                    case 'T_ADD':
                        $value = 10;
                        break;
                    case 'T_MUL':
                        $value = 20;
                        break;
                    default:
                        break;
                }
                return $value;
            }));
        $this->grammar
            ->expects($this->any())
            ->method('prefix')
            ->will($this->returnCallback(function($parser, $token) {
                return new Value((int) $token->lexeme());
            }));
        $this->grammar
            ->expects($this->any())
            ->method('infix')
            ->will($this->returnCallback(function($parser, $left, $token) {
                $value = 0;
                switch ($token->type()) {
                    case 'T_ADD':
                        $right = $parser->expression(10);
                        $value = $left->value + $right->value;
                        break;
                    case 'T_MUL':
                        $right = $parser->expression(20);
                        $value = $left->value * $right->value;
                        break;
                    default:
                        throw new \RuntimeException();
                }
                return new Value($value);
            }));
        $this->assertEquals(30, $this->parser->expression()->value);
    }

    public function testToString() {
        $this->assertIsString((string) $this->parser);
    }

}
