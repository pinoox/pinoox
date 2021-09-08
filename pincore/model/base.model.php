<?php

namespace pinoox\model;

use PDO;
use pinoox\component\Config;
use pinoox\component\MagicTrait;
use pinoox\component\querybuilder\queries\Delete;
use pinoox\component\querybuilder\queries\Select;
use pinoox\component\querybuilder\Query;

/**
 * Base Model for new QueryBuilder
 */
abstract class BaseModel
{

    use MagicTrait;

    /** 
     * Default primary key column
     */
    protected static $key = "id";

    protected $hidden = [];

    private $saved = true;


    private static $db;

    public static abstract function getTableName();

    /**
     * create model
     * 
     * @param array $data initial model data
     * @param boolean $saved true if inserted into database previously
     */
    public function __construct(array $data = [], $saved = false)
    {
        $this->saved = $saved;
        if (isAssoc($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(...$hide): array
    {
        $attributes = get_object_vars($this);
        unset($attributes['hidden'], $attributes['key'], $attributes['saved']);
        foreach ($hide as $key) {
            unset($attributes[$key]);
        }
        return $attributes;
    }

    /**
     * initalize model
     */
    public static function __init()
    {
        $config = Config::get('~database');
        $dbname =  $config['database'];
        $dbuser =  $config['username'];
        $dbpass = $config['password'];
        self::$db = new Query(new PDO("mysql:dbname=$dbname", $dbuser, $dbpass));
    }

    /**
     * Insert new row if not exists
     * 
     * @return int primary key
     */
    public function save(): int
    {
        if (!$this->saved) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->{static::$key} = static::query()->insertInto(static::getTableName())->values($this->toArray("id"))->execute();
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
            static::query()->update(static::getTableName())->where(static::$key . " = ?", [$this->{static::$key}])->set($this->toArray())->getQuery();
        }
        return $this->{$this::$key};
    }

    /**
     * Create and insert model
     * 
     * @param array $data model data
     */
    public static function create(array $data = []): BaseModel
    {
        $model = new self();
        if (isAssoc($data)) {
            foreach ($data as $key => $value) {
                $model->{$key} = $value;
            }
        }
        $model->save();
        return $model;
    }

    /**
     * Default key column is id
     * @param int key
     * 
     * @return BaseModel
     */
    public static function findByKey($id)
    {
        return static::query()->from(static::getTableName(), null)->asObject(static::class)->where(static::$key . " = ?", $id)->fetch();
    }

    public static function delete(): Delete
    {
        return static::query()->deleteFrom(static::getTableName(), null);
    }

    public static function select(): Select
    {
        return static::query()->from(static::getTableName(), null)->asObject(static::class);
    }

    private static function query(): Query
    {
        return static::$db;
    }
}
