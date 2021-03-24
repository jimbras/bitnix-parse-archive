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

use Error,
    InvalidArgumentException,
    RuntimeException,
    Bitnix\Parse\ParseFailure,
    Bitnix\Parse\Position,
    Bitnix\Parse\Token,
    Bitnix\Parse\Tokenizer;

/**
 * Default tokenizer.
 */
final class TokenStream implements Tokenizer, Stack {

    public const EOS_TOKEN  = 'eos_token';
    public const STACK_SIZE = 'stack_size';

    public const DEFAULT_OPTIONS = [
        self::EOS_TOKEN  => 'T_EOS',
        self::STACK_SIZE => 5
    ];

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var null|string
     */
    private ?string $buffer = null;

    /**
     * @var int
     */
    private int $line = 0;

    /**
     * @var int
     */
    private int $offset = 0;

    /**
     * @var int
     */
    private int $size;

    /**
     * @var int
     */
    private int $count = 0;

    /**
     * @var array
     */
    private array $stack = [];

    /**
     * @var State
     */
    private State $state;

    /**
     * @var bool
     */
    private bool $valid = true;

    /**
     * @var Token
     */
    private ?Token $done = null;

    /**
     * @var string
     */
    private string $eos;

    /**
     * @param State $main
     * @param resource $input
     * @param array $options
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws TypeError
     */
    private function __construct(State $main, $input, array $options = []) {
        $options += self::DEFAULT_OPTIONS;

        $this->stream = $input;

        $this->state = $main;
        $this->size = $options[self::STACK_SIZE];
        if ($this->size < 1) {
            throw new InvalidArgumentException('Stack size must be >= 0');
        }

        $this->eos = $options[self::EOS_TOKEN];

        $this->read();
    }

    public function __destruct() {
        $this->release();
    }

    private function release() : void {
        $this->valid = false;
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * @param State $state
     * @throws RuntimeException
     */
    public function push(State $state) : void {
        if ($this->count === $this->size) {
            throw new RuntimeException('Push failed... stack is full');
        }

        ++$this->count;
        $this->stack[] = $this->state;
        $this->state = $state;
    }

    /**
     * @throws RuntimeException
     */
    public function pop() : State {
        if (0 === $this->count) {
            throw new RuntimeException('Pop failed... stack is empty');
        }

        --$this->count;
        $state = $this->state;
        $this->state = \array_pop($this->stack);
        return $state;
    }

    /**
     * @return bool
     * @throws RuntimeException
     */
    private function read() : bool {

        try {
            if (false !== ($line = fgets($this->stream))) {
                $this->buffer = $line;
                ++$this->line;
                $this->offset = 0;
                return true;
            }
        } catch (Error $x) {
            $this->release();
            throw new RuntimeException('Tokenizer read failure', 0, $x);
        }

        $this->release();

        return false;
    }

    /**
     * @return bool
     */
    public function valid() : bool {
        return $this->valid;
    }

    /**
     * @return Token
     */
    private function eos() : Token {
        return $this->done ??= new Token($this->eos);
    }

    /**
     * @return Token
     * @throws ParseFailure
     * @throws RuntimeException
     */
    public function next() : Token {

        if (!$this->valid || !$this->trim()) {
            return $this->eos();
        }

        $token = $this->state->match($this, $this->buffer, $this->offset);

        if (!$token || 0 === ($bytes = \strlen($token->lexeme()))) {
            $this->error('Unexpected token');
        }

        $this->offset += $bytes;

        $this->trim();

        return $token;
    }

    /**
     * @return bool
     */
    private function done() : bool {
        return !isset($this->buffer[$this->offset]) && !$this->read();
    }

    /**
     * @return bool
     * @throws RuntimeException
     */
    private function trim() : bool {

        do {

            if ($this->done()) {
                return false;
            }

            $bytes = $this->state->skip($this->buffer, $this->offset);
            $this->offset += $bytes;

        } while ($bytes > 0);

        return true;
    }

    /**
     * @return Position
     */
    public function position() : Position {
        $offset = $this->offset;

        if (null !== $this->buffer) {
            // fix offset if needed...
            while (!isset($this->buffer[$offset])) {
                --$offset;
            }
        }

        return new Position($this->buffer, $this->line, $offset);
    }

    /**
     * @param string $message
     * @throws ParseFailure
     */
    public function error(string $message) : void {
        throw new ParseFailure($message, $this->position());
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }

    /**
     * @param State $main
     * @param string $input
     * @param array $options
     * @return self
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws TypeError
     */
    public static function fromString(State $main, string $input, array $options = []) : self {
        $stream = \fopen('php://memory', 'wb+');
        \fwrite($stream, $input);
        \rewind($stream);
        return new self($main, $stream, $options);
    }

    /**
     * @param State $main
     * @param string $file
     * @param array $options
     * @return self
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws TypeError
     */
    public static function fromFile(State $main, string $file, array $options = []) : self {
        if (\is_file($file) && \is_readable($file)) {
            return self::fromString($main, \file_get_contents($file), $options);
        }

        throw new InvalidArgumentException(\sprintf(
            'Invalid or unreadable token stream file: %s', $file
        ));
    }

    /**
     * @param State $main
     * @param resource $stream
     * @param array $options
     * @return self
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws TypeError
     */
    public static function fromStream(State $main, $stream, array $options = []) : self {
        if (\is_resource($stream) && 'stream' === \get_resource_type($stream)) {
            return new self($main, $stream, $options);
        }

        throw new InvalidArgumentException(\sprintf(
            'Invalid token stream: string or stream required, got %s',
            \is_object($stream) ? \get_class($stream) : \gettype($stream)
        ));
    }
}
