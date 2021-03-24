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
    Bitnix\Parse\Token;

/**
 * Default lexer state implementation.
 *
 * Uses regexes and events to process input streams.
 */
final class TokenSet implements State {

    /**
     * @var string
     */
    private string $match;

    /**
     * @var array
     */
    private array $matchers = [];

    /**
     * @var string
     */
    private ?string $skip = null;

    /**
     * @param array $match
     * @param array $skip
     * @param array $actions
     * @throws InvalidArgumentException
     */
    public function __construct(array $match, array $skip = [], array $actions = []) {
        if (empty($match)) {
            throw new InvalidArgumentException('Empty token set is not allowed');
        }

        $fn = function() {};
        $marked = \array_map(function($matcher, $type) use($fn) {
            $this->matchers[$type] = $fn;
            return \str_replace('~', '\\~', $matcher) . '(*MARK:' . $type . ')';
        }, $match, \array_keys($match));
        $this->match = $this->compile($marked, $match);

        if ($skip) {
            \array_walk($skip, fn($matcher) => \str_replace('~', '\\~', $matcher));
            $this->skip = $this->compile($skip);
        }

        foreach ($actions as $type => $fn) {
            $this->bind($type, $fn);
        }
    }

    /**
     * @param array $patterns
     * @param array $source
     * @return string
     * @throws InvalidArgumentException
     */
    private function compile(array $patterns, array $source = []) : string {
        $regex = '~(' . \implode(')|(', \array_values($patterns)) . ')~Au';

        if (false === (@\preg_match($regex, ''))) {
            throw new InvalidArgumentException(\sprintf(
                'Failed to compile tokens: %s',
                \implode(', ', \array_keys($source ?: $patterns))
            ));
        }

        return $regex;
    }

    /**
     * @param string $buffer
     * @param int $offset
     * @return int
     * @throws RuntimeException
     */
    public function skip(string $buffer, int $offset) : int {
        if ($this->skip && \preg_match($this->skip, $buffer, $matches, 0, $offset)) {
            return \strlen($matches[0]);
        }
        return 0;
    }

    /**
     * @param string $type
     * @param callable $handler
     * @throws LogicException
     */
    private function bind(string $type, callable $handler) : void {
        if (!isset($this->matchers[$type])) {
            throw new LogicException(sprintf(
                'Failed to bind action handler: no token %s in set (%s)',
                $type,
                \implode(', ', \array_keys($this->matchers))
            ));
        }
        $this->matchers[$type] = $handler;
    }

    /**
     * @param Stack $stack
     * @param string $buffer
     * @param int $offset
     * @return null|Token
     * @throws RuntimeException
     */
    public function match(Stack $stack, string $buffer, int $offset) : ?Token {
        if (!\preg_match($this->match, $buffer, $matches, 0, $offset)) {
            return null;
        }

        if (!isset($matches['MARK'])) {
            throw new RuntimeException('Unable to determine token type');
        }

        $token = new Token($type = $matches['MARK'], $matches[0]);
        $this->matchers[$type]($stack, $token);
        return $token;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
