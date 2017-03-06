<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{

    public static function getTableName()
    {
        return (new static)->getTable();
    }

    public static function getPriKeyName()
    {
        return (new static)->getKeyName();
    }

    public static function getColumnName($column)
    {
        return self::getTableName() . '.' . $column;
    }


    public static function tbl($column = null)
    {
        $tableName = null;
        if(isset(static::$tblName)) {
            $tableName = static::$tblName;
        } else {
            $tableName = with(new static)->getTable();
        }
        return $tableName. ( !$column ? '' : '.'.$column);
    }

    public static function col($column)
    {
        return self::tbl($column);
    }

}
