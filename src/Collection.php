<?php
/*****************************************************************************\
 *                                                                           *
 *  Collection.php                                                           *
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

class Collection implements \SeekableIterator
{
    protected $class;
    protected $orm;
    protected $select;
    protected $offset = 0;
    protected $row;
    protected $rows;
    protected $loadOnce = true;
    protected $init = false;

    public function __construct($class, $select = null)
    {
        if (is_object($class)) {
            $this->class = get_class($class);
            $this->orm = $class;
        } else {
            $this->class = $class;
            $this->orm = new $this->class();
        }
        if (null === $select) {
            $select = "SELECT * FROM " . $this->orm->getTable();
        }
        $this->select = $select;
    }

    public function init()
    {
        if ($this->loadOnce) {
            $this->rows = $this->orm->dbAdapter()->fetchAll($this->select);
        }
        $this->init = true;
    }

    public function setFilters(array $filters)
    {
        $where = array();
        $fields = $this->orm->fields();
        foreach ($filters as $key => $value) {
            if (in_array($key, $fields)) {
                if (is_array($value)) {
                    $fieldValues = array();
                    foreach ($value as $val) {
                        $val = $this->orm->dbAdapter()->quote($val);
                        $fieldValues[] = $val;
                    }
                    $where[] = "`{$key}` IN ("
                             . implode(', ', $fieldValues) . ')';
                } else {
                    $where[] = "`{$key}` = "
                             . $this->orm->dbAdapter()->quote($value);
                }
            }
        }
        if ($where) {
            $this->select .= " WHERE " . implode(' AND ', $where);
        }
    }

    public function getLoadOnce()
    {
        return $this->loadOnce;
    }

    public function setLoadOnce($loadOnce)
    {
        $this->loadOnce = (bool)$loadOnce;
    }

    public function rewind()
    {
        $this->offset = 0;
        return $this;
    }

    public function first()
    {
        $obj = new $this->class();
        if ($this->valid()) {
            $sql = "{$this->select} LIMIT 0, 1";
            $row = $obj->dbAdapter()->fetchOne($sql);
            $obj->populateWithArray($row);
        }
        return $obj;
    }

    public function valid()
    {
        if (!$this->init) {
            $this->init();
        }
        $obj = new $this->class();
        $sql = "{$this->select} LIMIT {$this->offset}, 1";
        $this->row = $obj->dbAdapter()->fetchOne($sql);
        if ($this->row) {
            return true;
        }
        return false;
    }

    public function key()
    {
        return $this->offset;
    }

    public function current()
    {
        $obj = new $this->class();
        $obj->populateWithArray($this->row);
        return $obj;
    }

    public function seek($offset)
    {
        $this->offset = $offset;
        return $this->current();
    }

    public function next()
    {
        $this->offset++;
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
