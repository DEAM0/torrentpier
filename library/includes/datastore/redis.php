<?php

if (!defined('BB_ROOT')) {
    die(basename(__FILE__));
}

class datastore_redis extends datastore_common
{
    public $cfg = null;
    public $redis = null;
    public $prefix = null;
    public $connected = false;
    public $engine = 'Redis';

    /**
     * datastore_redis constructor.
     * @param $cfg
     * @param null $prefix
     * @return datastore_redis
     */
    public function datastore_redis($cfg, $prefix = null)
    {
        if (!$this->is_installed()) {
            die('Error: Redis extension not installed');
        }

        $this->cfg = $cfg;
        $this->redis = new Redis();
        $this->dbg_enabled = sql_dbg_enabled();
        $this->prefix = $prefix;
    }

    /**
     * Подключение
     */
    public function connect()
    {
        $this->cur_query = 'connect ' . $this->cfg['host'] . ':' . $this->cfg['port'];
        $this->debug('start');

        if ($this->redis->connect($this->cfg['host'], $this->cfg['port'])) {
            $this->connected = true;
        }

        if (!$this->connected && $this->cfg['con_required']) {
            die('Could not connect to redis server');
        }

        $this->debug('stop');
        $this->cur_query = null;
    }

    /**
     * @param $title
     * @param $var
     * @return bool
     */
    public function store($title, $var)
    {
        if (!$this->connected) {
            $this->connect();
        }
        $this->data[$title] = $var;

        $this->cur_query = "cache->set('$title')";
        $this->debug('start');
        $this->debug('stop');
        $this->cur_query = null;
        $this->num_queries++;

        return (bool)$this->redis->set($this->prefix . $title, serialize($var));
    }

    /**
     * Очистка
     */
    public function clean()
    {
        if (!$this->connected) {
            $this->connect();
        }
        foreach ($this->known_items as $title => $script_name) {
            $this->cur_query = "cache->rm('$title')";
            $this->debug('start');
            $this->debug('stop');
            $this->cur_query = null;
            $this->num_queries++;

            $this->redis->del($this->prefix . $title);
        }
    }

    /**
     * Получение из кеша
     */
    public function _fetch_from_store()
    {
        if (!$items = $this->queued_items) {
            $src = $this->_debug_find_caller('enqueue');
            trigger_error("Datastore: item '$item' already enqueued [$src]", E_USER_ERROR);
        }

        if (!$this->connected) {
            $this->connect();
        }
        foreach ($items as $item) {
            $this->cur_query = "cache->get('$item')";
            $this->debug('start');
            $this->debug('stop');
            $this->cur_query = null;
            $this->num_queries++;

            $this->data[$item] = unserialize($this->redis->get($this->prefix . $item));
        }
    }

    /**
     * @return bool
     */
    public function is_installed()
    {
        return class_exists('Redis');
    }
}
