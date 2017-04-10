<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/10
 * Time: PM3:35
 */

namespace x2ts;

require_once __DIR__ . '/xts.php';

use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase {
    public function testTokenLength() {
        $token = S::token();
        self::assertSame(16, strlen($token));
    }

    public function testTokenSave() {
        $token = S::token();
        $token->set('abc', 'def');
        $token->save();
        $tokenId = (string) $token;

        $newToken = S::token($tokenId);
        self::assertInstanceOf(Token::class, $newToken);
    }

    public function testTokenSet() {
        $token = S::token('abcdefg');
        $token->set('abc', 'name');
        $token->save();
    }
}
