<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/9/21
 * Time: 下午6:11
 */

namespace x2ts;


use x2ts\ComponentFactory as X;

/**
 * Class Session
 *
 * @package x2ts
 *
 * @property-read string $session_id
 */
class Session extends Token {
    protected static $_conf = [
        'saveComponentId' => 'cache',
        'saveKeyPrefix'   => 'session_',
        'tokenLength'     => 16,
        'autoSave'        => true,
        'expireIn'        => 604800,
        'varCache'        => true,
        'cookie'          => [
            'name'     => 'X_SESSION_ID',
            'expireIn' => null,
            'path'     => '/',
            'domain'   => null,
            'secure'   => null,
            'httpOnly' => true,
        ],
    ];

    private static $sessionId;

    public static function getInstance(array $args, array $conf, string $confHash) {
        $token = (string) ($args[0] ?? '');
        $action = X::router()->action;
        $sessionId = '';
        if ($token) {
            $sessionId = $token;
        } elseif (self::$sessionId) {
            $sessionId = self::$sessionId;
        } elseif ($auth = $action->header('Authorization')) {
            @list ($method, $data) = explode(' ', $auth, 2);
            if (strtolower($method) === 'token' && $data) {
                $sessionId = $data;
                X::logger()->trace("SessionId from token: $sessionId");
            }
        }

        if (!$sessionId && $conf['cookie'] !== false) {
            $sessionId = $action->cookie(
                $conf['cookie']['name']
                ?? static::$_conf['cookie']['name'],
                ''
            );
            X::logger()->trace("SessionId from cookie: $sessionId");
        }

        $session = parent::getInstance([$sessionId], $conf, $confHash);
        if (null === self::$sessionId) {
            self::$sessionId = (string) $session;
        }
        if (!$sessionId && $session->conf['cookie'] !== false) {
            $action->setCookie(
                $session->conf['cookie']['name'],
                self::$sessionId,
                $session->conf['cookie']['expireIn'] ?
                    time() + $session->conf['cookie']['expireIn'] : null,
                $session->conf['cookie']['path'],
                $session->conf['cookie']['domain'],
                $session->conf['cookie']['secure'],
                $session->conf['cookie']['httpOnly']
            );
        }
        return $session;
    }

    public function destroy() {
        if ($this->conf['cookie'] !== false) {
            X::router()->action->setCookie(
                $this->conf['cookie']['name'],
                '',
                strtotime('1997-07-01 00:00:00 GMT+0800'),
                $this->conf['cookie']['path'],
                $this->conf['cookie']['domain'],
                $this->conf['cookie']['secure'],
                $this->conf['cookie']['httpOnly']
            );
            unset($_COOKIE[$this->conf['cookie']['name']]);
        }
        parent::destroy();
    }

    public function getSessionId() {
        return (string) $this;
    }
}
