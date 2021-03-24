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

use InvalidArgumentException;

/**
 * Some component position frozen in parsing time.
 *
 * Expects valid UTF-8 to work properly.
 */
final class Position {

    /**
     * @var string
     */
    private ?string $buffer = null;

    /**
     * @var string
     */
    private string $eol = '';

    /**
     * @var int
     */
    private int $line = -1;

    /**
     * @var int
     */
    private int $column = -1;

    /**
     * @var int
     */
    private int $marker = -1;

    /**
     * @param null|string $buffer A single input line
     * @param int $line MUST be >= 1
     * @param int $offset Bythe offset... Easier to work with in PHP
     * @throws InvalidArgumentException If client ignores the golden rules
     */
    public function __construct(string $buffer = null, int $line = 1, int $offset = 0) {
        if (isset($buffer[0])) {
            $this->setupBuffer($buffer);
            $this->setupLine($line);
            $this->setupColumn($offset);
        }
    }

    /**
     * @param string $buffer
     * @throws InvalidArgumentException
     */
    private function setupBuffer(string $buffer) : void {
        $lines = \preg_split('~\R~u', $buffer);
        $count = \count($lines);

        if ($count > 2 || $count === 2 && '' !== $lines[1]) {
            throw new InvalidArgumentException(
                'Multiline parse position buffer not allowed'
            );
        }

        if (1 === $count) {
            $this->eol = \PHP_EOL;
        }

        $this->buffer = $buffer;
    }

    /**
     * @param int $line
     * @throws InvalidArgumentException
     */
    private function setupLine(int $line) : void {
        if ($line < 1) {
            throw new InvalidArgumentException(\sprintf(
                'Parse position must be >= 1, got %d', $line
            ));
        }
        $this->line = $line;
    }

    /**
     * @param int $offset
     * @throws InvalidArgumentException
     */
    private function setupColumn(int $offset) : void {

        if (!isset($this->buffer[$offset]) && '' !== $this->buffer) {
            throw new InvalidArgumentException('Parse position offset not present in buffer');
        }

        $part = \substr($this->buffer, 0, $offset);
        $this->column = \mb_strlen($part, 'UTF-8') + 1;
        $this->marker = \mb_strwidth($part, 'UTF-8');
    }

    /**
     * Returns true if the input buffer is anything but null or an empty string.
     *
     * @return bool
     */
    public function known() : bool {
        return null !== $this->buffer;
    }

    /**
     * The buffer is the context that gives sense to this position.
     *
     * @return null|string
     */
    public function buffer() : ?string {
        return $this->buffer;
    }

    /**
     * If known starts at 1, -1 otherwise.
     *
     * @return int
     */
    public function line() : int {
        return $this->line;
    }

    /**
     * If known starts at 1, -1 otherwise.
     *
     * @return int
     */
    public function column() : int {
        return $this->column;
    }

    /**
     * @param int $indent
     * @param string $pad
     * @return string
     */
    private function pad(int $indent, string $pad = ' ') : string {
        return \str_repeat($pad, \max(0, $indent));
    }

    /**
     * User friendly location string.
     *
     * @param int $indent Left padding value
     * @param string $known Sprintf(ed) patterm to display known locations. Line comes before column.
     * @param string $unknown Literal string if teh position is not known.
     * @return string
     */
    public function location(
        int $indent = 0,
        string $known = 'line %d, column %d',
        string $unknown = 'unknown position') : string {

        $prefix = $this->pad($indent);
        $location = $this->known()
            ? \sprintf($known, $this->line, $this->column)
            : $unknown;

        return $prefix . $location;
    }

    /**
     * Unicode friendly position pointer.
     *
     * @param int $indent Left padding value
     * @param string $pad One characte to use as padding (default is <space>)
     * @param string $marker One characte to use as marker (default is <^>)
     * @return string
     */
    public function marker(int $indent = 0, string $pad = ' ', string $marker = '^') : string {
        $prefix = $this->pad($indent);

        if ($this->known()) {
            $pad = $pad[0] ?? ' ';
            $marker = $marker[0] ?? '^';
            return $prefix
                . $this->buffer
                . $this->eol
                . $prefix
                . $this->pad($this->marker, $pad)
                . $marker;
        }

        return $prefix;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return \trim($this->location() . \PHP_EOL . $this->marker());
    }
}
