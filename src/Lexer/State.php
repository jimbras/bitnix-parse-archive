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

use RuntimeException,
    Bitnix\Parse\Token;

/**
 * Represents a lexing state.
 */
interface State {

    /**
     * @param string $buffer
     * @param int $offset
     * @return int
     * @throws RuntimeException
     */
    public function skip(string $buffer, int $offset) : int;

    /**
     * @param Stack $stack
     * @param string $buffer
     * @param int $offset
     * @return null|Token
     * @throws RuntimeException
     */
    public function match(Stack $stack, string $buffer, int $offset) : ?Token;

}
