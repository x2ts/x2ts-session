<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/9/22
 * Time: 上午11:35
 */

namespace x2ts;


use x2ts\cache\ICache;

/**
 * Class Token
 *
 * @package x2ts
 * @property ICache saver
 * @property array  data
 */
class Token extends Component {
    protected static $_conf = [
        'saveComponentId' => 'cache',
        'saveKeyPrefix'   => 'tok_',
        'tokenLength'     => 16,
        'autoSave'        => true,
        'expireIn'        => 300,
    ];

    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $isDestroy = false;

    /**
     * @var int
     */
    protected $expireIn = 0;

    /**
     * @var array
     */
    private static $_tokens = [];

    private $config = [];

    protected function __construct() { }

    public function getConf() {
        if (count($this->config)) {
            return $this->config;
        }
        return Configuration::$configuration[$this->getConfHash()] ?? [];
    }

    public function saveConf(array $conf, string $hash) {
        Toolkit::override($this->config, static::$_conf);
        Toolkit::override($this->config, $conf);
        Configuration::$configuration[$hash] = $this->config;
        $this->_confHash = $hash;
    }

    public static function getInstance(array $args, array $conf, string $confHash) {
        $token = $args[0] ?? '';
        /** @var Token $that */
        $that = new static();
        $that->saveConf($conf, $confHash);
        if ('' === $token) {
            $that->clean();
            return self::$_tokens["$that"] = $that;
        }

        if (array_key_exists($token, self::$_tokens) && self::$_tokens[$token] instanceof Token) {
            return self::$_tokens[$token];
        }
        $that->token = $token;
        $t = $that->saver->get($that->saveKey());
        if ($t instanceof Token) {
            $t->_confHash = $that->_confHash;
            $t->config = $that->config;
            if (empty($t->expireIn)) {
                $t->expireIn($that->config['expireIn']);
            }
            $that->isDestroy = true;
            return self::$_tokens["$t"] = $t;
        }

        $that->data = [];
        $that->expireIn = $that->conf['expireIn'];
        return self::$_tokens["$that"] = $that;
    }

    public function __sleep() {
        return ['data', 'expireIn'];
    }

    public function expireIn(int $seconds) {
        $this->expireIn = $seconds;
        return $this;
    }

    public function clean() {
        $this->token = Toolkit::randomChars($this->conf['tokenLength']);
        $this->data = [];
        $this->expireIn($this->conf['expireIn']);
    }

    public function __toString() {
        return $this->token;
    }

    public function save() {
        if (!$this->isDestroy && $this->expireIn) {
            $this->saver->set($this->saveKey(), $this, $this->expireIn);
            return true;
        }
        return false;
    }

    public function getData() {
        return $this->data;
    }

    public function setData(array $data) {
        $this->data = $data;
    }

    public function __destruct() {
        if ($this->conf['autoSave']) {
            $this->save();
        }
    }

    private function saveKey(): string {
        return $this->conf['saveKeyPrefix'] . $this->token;
    }

    public function destroy() {
        $this->isDestroy = true;
        $this->saver->remove($this->saveKey());
        return $this;
    }

    public function get(string $name) {
        return $this->data[$name] ?? null;
    }

    public function set(string $name, $value) {
        $this->data[$name] = $value;
        return $this;
    }

    public function isset(string $name) {
        return isset($this->data[$name]);
    }

    public function del(string $name) {
        unset($this->data[$name]);
        return $this;
    }

    public function __get($name) {
        $getter = Toolkit::toCamelCase("get $name");
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (array_key_exists($name, $this->data)) {
            return $this->get($name);
        }
        return null;
    }

    public function __set($name, $value) {
        $setter = Toolkit::toCamelCase("set $name");
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->set($name, $value);
        }
    }

    public function __isset($name) {
        return isset($this->data[$name]) ||
            method_exists($this, Toolkit::toCamelCase("get $name"));
    }

    public function __unset($name) {
        $this->del($name);
    }

    public function getSaver(): ICache {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ComponentFactory::getComponent($this->conf['saveComponentId']);
    }
}