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

namespace Bitnix\Parse;

/**
 * A lexer allows you to add syntatic meaning to the tokens returned by a tokenizer.
 */
interface Lexer extends Tokenizer {

    /**
     * Peek at the next tokens without actually consuming the stream.
     *
     * @param int $dist SHOULD be >= 0
     * @return Token The look ahead token.
     * @throws ParseFailure For errors related to parsing
     * @throws RuntimeException For any other errors
     */
    public function peek(int $dist = 0) : Token;

    /**
     * Attempt to match any of the given types with the next available token.
     *
     * @param string ...$types
     * @return bool
     */
    public function match(string ...$types) : bool;

    /**
     * Similar to match, but consumes the token in case of a match.
     *
     * @param string ...$types
     * @return null|Token
     * @throws ParseFailure For errors related to parsing
     * @throws RuntimeException For any other errors
     */
    public function consume(string ...$types) : ?Token;

    /**
     * Similar to consume, but will fail if there is no token match.
     *
     * @param string $type
     * @param null|string $message
     * @return Token
     * @throws ParseFailure For errors related to parsing
     * @throws RuntimeException For any other errors
     */
    public function demand(string $type, string $message = null) : Token;

}
