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
        $token = (string) $args[0] ?? '';
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
                Toolkit::trace("SessionId from token: $sessionId");
            }
        }

        if (!$sessionId) {
            $sessionId = $action->cookie(static::$_conf['cookie']['name'], '');
            Toolkit::trace("SessionId from cookie: $sessionId");
        }

        $session = parent::getInstance($args, $conf, $confHash);
        if (!$sessionId) {
            $action->setCookie(
                static::$_conf['cookie']['name'],
                self::$sessionId = (string) $session,
                static::$_conf['cookie']['expireIn'] ?
                    time() + static::$_conf['cookie']['expireIn'] : null,
                static::$_conf['cookie']['path'],
                static::$_conf['cookie']['domain'],
                static::$_conf['cookie']['secure'],
                static::$_conf['cookie']['httpOnly']
            );
        }
        return $session;
    }

    public function destroy() {
        X::router()->action->setCookie(
            static::$_conf['cookie']['name'],
            '',
            strtotime('1997-07-01 00:00:00 GMT+0800'),
            static::$_conf['cookie']['path'],
            static::$_conf['cookie']['domain'],
            static::$_conf['cookie']['secure'],
            static::$_conf['cookie']['httpOnly']
        );
        parent::destroy();
    }

    public function getSessionId() {
        return (string) $this;
    }
}