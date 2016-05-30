<?php
/*****************************************************************************\
 *                                                                           *
 *  Db.php                                                                   *
 *                                                                           *
 *  @author     Arthur Layese (arthur@layese.com) 2016                       *
 *  @package    Storm                                                        *
 *  @copyright  (c) 2016 Arthur Layese (http://storm.com.ph)                 *
 *  @license    This file is licensed under the GPL V3, you can find a copy  *
 *              of that license by visiting:                                 *
 *              http://www.fsf.org/licensing/licenses/gpl.html               *
 *                                                                           *
\*****************************************************************************/

namespace Storm;

interface Db
{
    public function getConfig();

    public function beginTransaction();
    public function rollback();
    public function commit();

    public function select($sql, $data = array());
    public function replace($sql, $data = array());
    public function insert($sql, $data = array());
    public function update($sql, $data = array());
    public function delete($sql, $data = array());
    public function statement($sql);
    public function quote($value);
    public function fetchOne($sql, $data = array());
    public function fetchAll($sql, $data = array());
}
