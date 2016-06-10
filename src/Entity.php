<?php
/*****************************************************************************\
 *                                                                           *
 *  Entity.php                                                               *
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

abstract class Entity extends Orm implements \Iterator
{
    private $_tracking = null;

    public function __construct()
    {
        $this->init();
        if (count($this->_primaryKeys) == 0) {
            $key = strtolower(self::toClassName($this)) . '_id';
            $this->_primaryKeys[] = $key;
        }
        if (empty($this->_table)) {
            $this->_table = strtolower(self::toClassName($this));
        }
    }

    public function __set($key, $value)
    {
        $newKey = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        $method = "set{$newKey}";
        if (method_exists($this, $method)) {
            $this->$method($value);
            return $this;
        }
        $newKey = strtolower(preg_replace('/([A-Z]{1})/','_\1', $key));
        if (strpos($newKey, '_') !== false
            && in_array($newKey, $this->fields())
        ) {
            $this->$newKey = $value;
            return $this;
        }
        $this->$key = $value;
        return $this;
    }

    public function __get($key)
    {
        $newKey = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        $method = "get{$newKey}";
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        $newKey = strtolower(preg_replace('/([A-Z]{1})/','_\1', $key));
        if (strpos($newKey, '_') !== false
            && in_array($newKey, $this->fields())
        ) {
            return $this->$newKey;
        }
        $ret = null;
        if (isset($this->$key)) {
            $ret = $this->$key;
        }
        return $ret;
    }

    public function __isset($key)
    {
        return $this->__get($key);
    }

    public static function toClassName($obj)
    {
        return (new \ReflectionClass($obj))->getShortName();
    }

    public function populateWithArray($array)
    {
        foreach ($array as $key => $value) {
            if ($this->$key instanceof Orm) {
                $this->$key->populateWithArray($value);
            } else {
                $this->__set($key, $value);
            }
        }
        $this->postPopulate();
    }

    public function populate()
    {
        $sql = "SELECT * FROM `{$this->_table}` WHERE 1 ";
        $doPopulate = false;
        foreach ($this->_primaryKeys as $key) {
            $value = $this->$key;
            if ($value > 0 || strlen($value) > 0) {
                $doPopulate = true;
                $sql .= " AND $key = '"
                     . preg_replace('/[^0-9a-z_A-Z-\.]/', '', $value)
                     . "'";
            }
        }
        if ($doPopulate == false) {
            return false;
        }
        $ret = $this->populateWithSql($sql);
        $this->postPopulate();
        return $ret;
    }

    public function postPopulate()
    {
        if (!$this->_cascadePopulate) {
            return true;
        }
        $fields = $this->fields();
        foreach ($fields as $field) {
            $obj = $this->__get($field);
            if (!$obj instanceof Orm) {
                continue;
            }
            // check for entity class name
            $childId = $field . '_id';
            if (in_array($childId, $fields)) {
                foreach ($obj->_primaryKeys as $key) {
                    $obj->$key = $this->$childId;
                }
            } else {
                foreach ($obj->_primaryKeys as $key) {
                    if (in_array($key, $fields)) {
                        $obj->$key = $this->$key;
                    } else {
                        // check if there's an underscore
                        if (strpos($key,'_') !== false) { // table_id
                            $newKey = str_replace(' ','',ucwords(str_replace('_',' ',$key)));
                            // lower case the first character
                            $newKey[0] = strtolower($newKey[0]);
                        } else {
                            $newKey = preg_replace('/([A-Z]{1})/','_\1',$key);
                        }
                        if (in_array($newKey, $fields)) {
                            $obj->$newKey = $this->$newKey;
                        }
                    }
                }
            }
            $obj->populate();
        }
    }

    public function populateWithSql($sql)
    {
        $row = $this->dbAdapter()->fetchOne($sql);
        $fields = $this->fields();
        $retval = false;
        if ($row) {
            $retval = true;
            foreach ($row as $col => $val) {
                if (!in_array($col, $fields)) {
                    continue;
                }
                unset($fields[$col]);
                $this->__set($col, $val);
            }
        }
        return $retval;
    }

    public function persist()
    {
        $sql = $this->toSql();
        $this->dbAdapter()->statement($sql);
        $this->postPersist();
        if ($this->shouldAudit()
            && self::toClassName($this) != 'Audit'
            && self::toClassName($this) != 'AuditValue'
        ) {
            self::audit($this);
        }
        return $this;
    }

    public function setPersistMode($mode)
    {
        $this->_persistMode = $mode;
    }

    public function audit($obj)
    {
        $auditId = $this->ormSequence()->nextId(2);
        $class = $this->ormAudit();
        $audit = new $class();
        $audit->auditId = $auditId;
        $audit->objectClass = self::toClassName($obj);
        $objectIdKey = $obj->_primaryKeys[0];
        $audit->objectId = $obj->$objectIdKey;
        $audit->userId = Registry::get('user_id');
        $audit->type = $obj->_persistMode;
        $audit->datetime = date('Y-m-d H:i:s');
        if ($obj instanceof Orm) {
            foreach ($obj->fields() as $field) {
                $class = $this->ormAuditValue();
                $auditValue = new $class();
                $auditValue->auditId = $auditId;
                $auditValue->key = $field;
                $value = $obj->$field;
                if (is_object($value)) {
                    $auditValue->value = self::toClassName($value);
                } else {
                    if (is_array($value)) {
                        if ($item = array_shift($value)) {
                            $auditValue->value = self::toClassName($item);
                        } else {
                            $auditValue->value = 'Array';
                        }
                    } else {
                        $auditValue->value = (string)$obj->$field;
                    }
                }
                $auditValue->persist();
            }
        }
        $audit->persist();
    }

    public function fields()
    {
        static $fields = null;
        if ($fields === null) {
            $class = new \ReflectionObject($this);
            $properties = $class->getProperties();
            $fields = array();
            foreach ($properties as $property) {
                if (substr($property->name, 0, 1) == '_') {
                    continue;
                }
                $fields[] = $property->name;
            }
        }
        return $fields;
    }

    public function toString()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $fields = $this->fields();
        $array = array();
        foreach ($fields as $field) {
            $value = $this->$field;
            if ($value instanceof Orm) {
                if ($this->_cascadeToArray) {
                    $array[$field] = $value->toArray();
                }
            } elseif (is_array($value)) {
                $array[$field] = array();
                foreach ($value as $val) {
                    if (!($val instanceof Orm)) {
                        continue;
                    }
                    if ($this->_cascadeToArray) {
                        $array[$field][] = $val->toArray();
                    }
                }
            } else {
                $array[$field] = $value;
            }
        }
        return $array;
    }

    protected function toSql()
    {
        $fields = $this->fields();
        $where = array('1');
        $fieldValues = array();
        $fieldNames = array();
        for ($i = 0, $ctr = count($fields); $i < $ctr; $i++) {
            $field = $fields[$i];
            $val = $this->__get($field);
            if (is_object($val)) {
                if ($val instanceof Orm && $this->_cascadePersist) {
                    $val->_persistMode = $this->_persistMode;
                    $val->persist();
                    $foreignKey = $field . '_id';
                    if (in_array($foreignKey, $fields)) {
                        $primaryKey = $val->_primaryKeys[0];
                        $this->$foreignKey = $val->$primaryKey;
                    }
                }
                continue;
            } elseif (is_array($val)) {
                foreach ($val as $item) {
                    if ($item instanceof Orm && $this->_cascadePersist) {
                        $item->persist();
                    }
                }
                continue;
            }
            if ($this->_persistMode == self::DELETE) {
                if (in_array($field, $this->_primaryKeys)) {
                    $where[] = " AND `$field` = "
                             . $this->dbAdapter()->quote($val);
                }
                // code below is just for replace/insert
                continue;
            }

            if (in_array($field, $this->_primaryKeys) && !$val > 0) {
                $where[] = " AND `$field` = ".$this->dbAdapter()->quote($val);
                $seqTable = 1;
                if (self::toClassName($this) == 'Audit'
                    || self::toClassName($this) == 'AuditValue'
                ) {
                    $seqTable = 2;
                }
                if (self::toClassName($this) == 'Audit'
                    || self::toClassName($this) == 'AuditValue'
                    || $this->_persistMode != self::DELETE
                ) {
                    $lastId = $this->ormSequence()->nextId($seqTable);
                    $this->__set($field, $lastId);
                    $val = $lastId;
                }
            }

            if (substr($field, 0, 1) != '_') {
                $fieldValues[] = " `$field` = "
                               . $this->dbAdapter()->quote($val);
                $fieldNames[] = $field;
            }
        }

        switch ($this->_persistMode) {
            case self::REPLACE:
                $sql = "REPLACE INTO `{$this->_table}` SET ";
                break;
            case self::INSERT:
                $sql = "INSERT INTO `{$this->_table}` SET ";
                break;
            case self::UPDATE:
                $sql = "UPDATE `{$this->_table}` SET ";
                break;
            case self::DELETE:
                $sql = "DELETE FROM `{$this->_table}` ";
                break;
        }

        if ($fieldNames) {
            $fieldValues = array();
            foreach ($fieldNames as $field) {
                $val = $this->__get($field);
                $fieldValues[] = " `{$field}` = "
                               . $this->dbAdapter()->quote($val);
            }
            $sql .= implode(', ', $fieldValues);
        }

        if ($this->_persistMode == self::UPDATE
            || $this->_persistMode == self::DELETE
        ) {
            $sql .= " WHERE " . implode(' ', $where);
        }
        return $sql;
    }

    public function __toString()
    {
        return $this->toString();
    }

    protected function toGetSet($key, Array $value)
    {
        if (array_key_exists(0, $value)) {
            $this->$key = $value[0];
            return $this;
        }
        return $this->$key;
    }

    public function __call($method, $args)
    {
        $fields = $this->fields();

        if (in_array($method, $fields)) {
            return $this->toGetSet($method, $args);
        }
        $method2 = strtolower($method);
        if ($method != $method2 && in_array($method2, $fields)) {
            return $this->toGetSet($method2, $args);
        }
        $method2 = preg_replace('/([A-Z]{1})/','_\1', $method);
        if ($method != $method2 && in_array($method2, $fields)) {
            return $this->toGetSet($method2, $args);
        }
        $method2 = strtolower($method2);
        if ($method != $method2 && in_array($method2, $fields)) {
            return $this->toGetSet($method2, $args);
        }
        throw new \Exception('Call to undefined method '.get_class($this).'::'.$method.'()');
    }

    /**
     * Get collection
     *
     * @param string            $where  OPTIONAL An SQL WHERE clause.
     * @param string            $order  OPTIONAL An SQL ORDER clause.
     * @param int               $count  OPTIONAL An SQL LIMIT count.
     * @param int               $offset OPTIONAL An SQL LIMIT offset.
     */
    public function getCollection(
        $where = null,
        $order = null,
        $count = null,
        $offset = null
    ) {
        $class = get_class($this) . 'Collection';
        if (class_exists($class)) {
            $collection = new $class();
        } else {
            $collection = new Collection(
                get_class($this),
                $where,
                $order,
                $count,
                $offset
            );
        }
        return $collection;
    }

    public function postPersist()
    {
    }

    public function shouldAudit()
    {
        return $this->_shouldAudit;
    }

    public function rewind()
    {
        if ($this->_tracking === null) {
            $this->_tracking = $this->fields();
        }
        reset($this->_tracking);
    }

    public function current()
    {
        $key = current($this->_tracking);
        $var = $this->__get($key);
        return $var;
    }

    public function key()
    {
        $var = current($this->_tracking);
        return $var;
    }

    public function next()
    {
        $var = next($this->_tracking);
        return $var;
    }

    public function valid()
    {
        $var = current($this->_tracking) !== false;
        return $var;
    }

    public function toXml()
    {
        return self::recurseXml($this);
    }

    protected static function recurseXml($data,
        $rootNodeName = 'data',
        $xml = null
    ) {
        if ($xml === null) {
            $root = "<?xml version='1.0' encoding='utf-8'?><$rootNodeName />";
            $xml = simplexml_load_string($root);
        }
        // loop through the data passed in.

        foreach ($data as $key => $value) {
            if (is_object($data) && method_exists($data, "get_".$key)) {
                //$value = call_user_method("get", $data,$key);
                $value = $data->get($key);
            }
            // no numeric keys in our xml please!
            if (is_numeric($key)) {
                $key = 'array';
            }
            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z_0-9]/i', '', $key);

            // if there is another array found recrusively call this function
            if (strpos($key, '_') !== 0) {
                if (is_array($value) || is_object($value)) {
                    $node = $xml->addChild($key);
                    // recrusive call.
                    self::recurseXml($value, $rootNodeName, $node);
                } else {
                    // add single node.
                    if (is_resource($value)) {
                        $value = 'resource';
                    }
                    $value = iconv("UTF-8", "ASCII//TRANSLIT", $value);
                    $value = htmlentities($value);
                    $xml->addChild($key, $value);
                }
            }
        }
        // pass back as string. or simple xml object if you want!
        $xmlstr = $xml->asXML();
        return preg_replace('/<\?.*\?>/', '', $xmlstr);
    }
}
