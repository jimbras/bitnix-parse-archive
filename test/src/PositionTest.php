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

use InvalidArgumentException,
    PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class PositionTest extends TestCase {

    public function testUnknownPosition() {
        $pos = new Position();

        $this->assertNull($pos->buffer());
        $this->assertEquals(-1, $pos->line());
        $this->assertEquals(-1, $pos->column());
        $this->assertFalse($pos->known());

        $pos = new Position('');

        $this->assertNull($pos->buffer());
        $this->assertEquals(-1, $pos->line());
        $this->assertEquals(-1, $pos->column());
        $this->assertFalse($pos->known());
    }

    public function testKnownPosition() {
        $pos = new Position('Hello', 10, 2);

        $this->assertEquals('Hello', $pos->buffer());
        $this->assertEquals(10, $pos->line());
        $this->assertEquals(3, $pos->column());
        $this->assertTrue($pos->known());
    }

    public function testKnownPositionLocation() {
        $pos = new Position('Hello', 1, 1);
        $this->assertEquals('line 1, column 2', $pos->location());
        $this->assertEquals('  line 1, column 2', $pos->location(2));

        $this->assertEquals('[1:2]', $pos->location(0, '[%d:%d]'));
        $this->assertEquals('  [1:2]', $pos->location(2, '[%d:%d]'));
    }

    public function testUnknownPositionLocation() {
        $pos = new Position();
        $this->assertEquals('unknown position', $pos->location());
        $this->assertEquals('  unknown position', $pos->location(2));

        $this->assertEquals('???', $pos->location(0, '[%d:%d]', '???'));
        $this->assertEquals('  ???', $pos->location(2, '[%d:%d]', '???'));
    }

    public function testUnkownPositionMarker() {
        $pos = new Position();
        $this->assertEquals('', $pos->marker());
        $this->assertEquals('  ', $pos->marker(2));
    }

    public function testKownPositionMarker() {
        $pos = new Position('Hello', 1, 1);

        $exp = 'Hello' . PHP_EOL
             . ' ^';
        $this->assertEquals($exp, $pos->marker());

        $exp = '  Hello' . PHP_EOL
             . '   ^';
        $this->assertEquals($exp, $pos->marker(2));
    }

    public function testMultibytePosition() {
        $buffer = "Say: ご飯が熱い。 Gohan ga atsui. (The rice is hot.)\n";
        $marker = "       ^";
        $offset = \strpos($buffer, '飯');

        $pos = new Position($buffer, 1, $offset);
        $this->assertEquals($buffer, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(7, $pos->column());
        $this->assertEquals('line 1, column 7', $pos->location());
        $this->assertEquals($buffer . $marker, $pos->marker());

        $buffer = "Say: ご飯が熱い。 Gohan ga atsui. (The rice is hot.)\n";
      $marker = "                  ^"; // weird font spacing!!!
        $offset = \strpos($buffer, 'G');

        $pos = new Position($buffer, 1, $offset);
        $this->assertEquals($buffer, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(13, $pos->column());
        $this->assertEquals('line 1, column 13', $pos->location());
        $this->assertEquals($buffer . $marker, $pos->marker());

        $buffer = "Say: ご飯が熱い。 Gohan ga atsui. (The rice is hot.)\n";
      $marker = "                                                    ^"; // weird font spacing!!!
        $offset = \strpos($buffer, "\n");

        $pos = new Position($buffer, 1, $offset);
        $this->assertEquals($buffer, $pos->buffer());
        $this->assertEquals(1, $pos->line());
        $this->assertEquals(47, $pos->column());
        $this->assertEquals('line 1, column 47', $pos->location());
        $this->assertEquals($buffer . $marker, $pos->marker());
    }

    public function testToStringUnknownPosition() {
        $pos = new Position();
        $this->assertEquals('unknown position', (string) $pos);
    }

    public function testToStringKnownPosition() {
        $pos = new Position("Hello\n", 10, 3);
        $exp =  "line 10, column 4\n";
        $exp .= "Hello\n";
        $exp .= '   ^';
        $this->assertEquals($exp, (string) $pos);
    }

    /**
     * @dataProvider validBuffer
     */
    public function testValidBuffer(string $buffer = null) {
        $pos = new Position($buffer);
        $this->assertEquals($buffer, $pos->buffer());
    }

    public function validBuffer() : array {
        return [
            [null],  // unknown
            [''], // unknown
            ["\n"],
            ['foo'],
            ["foo\n"]
        ];
    }

    /**
     * @dataProvider invalidBuffer
     */
    public function testInvalidBuffer(string $buffer) {
        $this->expectException(InvalidArgumentException::CLASS);
        $pos = new Position($buffer);
    }

    public function invalidBuffer() : array {
        return [
            ["foo\nbar\n"],
            ["foo\nbar\nbaz"]
        ];
    }

    public function testInvalidLine() {
        $this->expectException(InvalidArgumentException::CLASS);
        $pos = new Position('Hello', 0);
    }

    public function testInvalidOffset() {
        $this->expectException(InvalidArgumentException::CLASS);
        $pos = new Position('Hello', 1, 10);
    }

}
