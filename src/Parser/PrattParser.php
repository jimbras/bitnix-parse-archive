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
    Bitnix\Parse\Parser;

/**
 * ...
 */
abstract class PrattParser implements Parser {

    /**
     * @param Lexer $lexer
     * @param Grammar $grammar
     */
    public function __construct(private Lexer $lexer, private Grammar $grammar) {}

    /**
     * @return Lexer
     */
    public function lexer() : Lexer {
        return $this->lexer;
    }

    /**
     * @param int $precedence
     * @return Expression
     * @throws ParseFailure
     * @throws RuntimeException
     */
    public function expression(int $precedence = 0) : Expression {
        $left = $this->grammar->prefix($this, $this->lexer->next());
        while ($precedence < $this->grammar->precedence($this->lexer->peek())) {
            $left = $this->grammar->infix($this, $left, $this->lexer->next());
        }
        return $left;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
