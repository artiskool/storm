<?php
/*****************************************************************************\
 *                                                                           *
 *  Sequence.php                                                             *
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

abstract class Sequence
{
    protected $_table = 'sequences';
    protected $_primaryKey = 'id';
    protected $_dbAdapter;

    public function __construct($dbAdapter = null)
    {
        if ($dbAdapter && $dbAdapter instanceof Db) {
            $this->_dbAdapter = $dbAdapter;
        }
    }

    public function nextId($key = 1)
    {
        $id = null;
        $key = (int)$key;

        $tableName = $this->_table;
        $this->_dbAdapter->beginTransaction();
        try {
            $sql = "UPDATE {$tableName} SET sequence = sequence + 1"
                 . " WHERE {$this->_primaryKey} = {$key}";
            $this->_dbAdapter->update($sql);
            $sql = "SELECT sequence FROM {$tableName}"
                 . " WHERE {$this->_primaryKey} = {$key}";
            if ($row = $this->_dbAdapter->fetchOne($sql)) {
                $id = $row->sequence;
            }
            $this->_dbAdapter->commit();
        } catch (Exception $e) {
            $this->_dbAdapter->rollBack();
            //echo $e->getMessage();
        }
        return $id;
    }
}
