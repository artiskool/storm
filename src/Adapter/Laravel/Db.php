<?php
/*****************************************************************************\
 *                                                                           *
 *  Db.php                                                                   *
 *                                                                           *
 *  @author     Arthur Layese (arthur@layese.com) 2016                       *
 *  @package    Storm\Laravel                                                *
 *  @copyright  (c) 2016 Arthur Layese (http://storm.com.ph)                 *
 *  @license    This file is licensed under the GPL V3, you can find a copy  *
 *              of that license by visiting:                                 *
 *              http://www.fsf.org/licensing/licenses/gpl.html               *
 *                                                                           *
\*****************************************************************************/

namespace Storm\Adapter\Laravel;

use Storm\Db as StormDb;

class Db implements StormDb
{
    public function getConfig()
    {
        $driver = \Config::get('database.default');
        $config = \Config::get('database.connections.'.$driver);
        return array(
            'hostname' => $config['host'],
            'username' => $config['username'],
            'password' => $config['password'],
            'database' => $config['database'],
        );
    }

    public function beginTransaction()
    {
        return \DB::beginTransaction();
    }

    public function rollback()
    {
        return \DB::rollback();
    }

    public function commit()
    {
        return \DB::commit();
    }

    public function select($sql, $data = array())
    {
        return \DB::select($sql, $data);
    }

    public function replace($sql, $data = array())
    {
        return \DB::statement($sql);
    }

    public function insert($sql, $data = array())
    {
        return \DB::insert($sql, $data);
    }

    public function update($sql, $data = array())
    {
        return \DB::update($sql, $data);
    }

    public function delete($sql, $data = array())
    {
        return \DB::delete($sql, $data);
    }

    public function statement($sql)
    {
        return \DB::statement($sql);
    }

    public function quote($value)
    {
        return \DB::connection()->getPdo()->quote($value);
    }

    public function fetchOne($sql, $data = array())
    {
        $result = $this->select($sql, $data);
        if ($result && isset($result[0]))
            return $result[0];
        return array();
    }

    public function fetchAll($sql, $data = array())
    {
        return $this->select($sql, $data);
    }
}
