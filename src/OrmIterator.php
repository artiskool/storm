<?php
/*****************************************************************************\
 *                                                                           *
 *  OrmIterator.php                                                          *
 *                                                                           *
 *  @author     Arthur Layese (arthur@layese.com) 2016                       *
 *  @package    Storm                                                        *
 *  @copyright  (c) 2016 Arthur Layese (http://storm.com.ph)                 *
 *  @license    This file is licensed under the GPL V3, you can find a copy  *
 *              of that license by visiting:                                 *
 *              http://www.fsf.org/licensing/licenses/gpl.html               *
 *                                                                           *
 *****************************************************************************
 *                                                                           *
 *       Author:  ClearHealth Inc. (www.clear-health.com)        2009        *
 *                                                                           *
 *       ClearHealth(TM), HealthCloud(TM), WebVista(TM) and their            *
 *       respective logos, icons, and terms are registered trademarks        *
 *       of ClearHealth Inc.                                                 *
 *                                                                           *
 *       Though this software is open source you MAY NOT use our             *
 *       trademarks, graphics, logos and icons without explicit permission.  *
 *       Derivitive works MUST NOT be primarily identified using our         *
 *       trademarks, though statements such as "Based on ClearHealth(TM)     *
 *       Technology" or "incoporating ClearHealth(TM) source code"           *
 *       are permissible.                                                    *
 *                                                                           *
 *       This file is licensed under the GPL V3, you can find                *
 *       a copy of that license by visiting:                                 *
 *       http://www.fsf.org/licensing/licenses/gpl.html                      *
 *                                                                           *
\*****************************************************************************/

namespace Storm;

class OrmIterator implements \SeekableIterator
{
    protected $_class;
    protected $_orm;
    protected $_select;
    protected $_offset = 0;
    protected $_row;
    protected $_rows;
    protected $_loadOnce = true;
    protected $_init = false;

    public function __construct($class, $select = null)
    {
        if (is_object($class)) {
            $this->_class = get_class($class);
            $this->_orm = $class;
        } else {
            $this->_class = $class;
            $this->_orm = new $this->_class();
        }
        if (null === $select) {
            $select = "SELECT * FROM {$this->_orm->_table}";
        }
        $this->_select = $select;
    }

    public function init()
    {
        if ($this->_loadOnce) {
            $this->_rows = $this->_orm->dbAdapter()->fetchAll($this->_select);
        }
        $this->_init = true;
    }

    public function setFilters(array $filters)
    {
        $where = array();
        $fields = $this->_orm->fields();
        foreach ($filters as $key => $value) {
            if (in_array($key, $fields)) {
                if (is_array($value)) {
                    $fieldValues = array();
                    foreach ($value as $val) {
                        $val = $this->_orm->dbAdapter()->quote($val);
                        $fieldValues[] = $val;
                    }
                    $where[] = "`{$key}` IN ("
                             . implode(', ', $fieldValues) . ')';
                } else {
                    $where[] = "`{$key}` = "
                             . $this->_orm->dbAdapter()->quote($value);
                }
            }
        }
        if ($where) {
            $this->_select .= " WHERE " . implode(' AND ', $where);
        }
    }

    public function getLoadOnce()
    {
        return $this->_loadOnce;
    }

    public function setLoadOnce($loadOnce)
    {
        $this->_loadOnce = (bool)$loadOnce;
    }

    public function rewind()
    {
        $this->_offset = 0;
        return $this;
    }

    public function first()
    {
        $obj = new $this->_class();
        if ($this->valid()) {
            $sql = "{$this->_select} LIMIT 0, 1";
            $row = $obj->dbAdapter()->fetchOne($sql);
            $obj->populateWithArray($row);
        }
        return $obj;
    }

    public function valid()
    {
        if (!$this->_init) {
            $this->init();
        }
        $obj = new $this->_class();
        $sql = "{$this->_select} LIMIT {$this->_offset}, 1";
        $this->_row = $obj->dbAdapter()->fetchOne($sql);
        if ($this->_row) {
            return true;
        }
        return false;
    }

    public function key()
    {
        return $this->_offset;
    }

    public function current()
    {
        $obj = new $this->_class();
        $obj->populateWithArray($this->_row);
        return $obj;
    }

    public function seek($offset)
    {
        $this->_offset = $offset;
        return $this->current();
    }

    public function next()
    {
        $this->_offset++;
    }

    public function toArray($key = null, $value)
    {
        $array = array();
        foreach ($this as $count => $obj) {
            if (is_null($key)) {
                $array[$count] = $obj->$value;
            } else {
                if (is_array($value)) {
                    foreach ($value as $val)  {
                        $array[$obj->$key][] = $obj->$val;
                    }
                } else {
                    $array[$obj->$key] = $obj->$value;
                }
            }
        }
        return $array;
    }

    public function toJsonArray($idKey, $value, $associative = false)
    {
        $array = array();
        foreach ($this as $count => $obj) {
            $tmpArray = array();
            $tmpArray['id'] = $obj->$idKey;
            foreach ($value as $val)  {
                if ($associative) {
                    $tmpArray[$val] = $obj->$val;
                } else {
                    $tmpArray['data'][] = $obj->$val;
                }
            }
            $array[] = $tmpArray;
        }
        return $array;
    }
}
