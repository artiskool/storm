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

abstract class Entity implements Orm, \Iterator
{
    abstract public function dbAdapter();

    abstract public function ormSequence();
    abstract public function ormAudit();
    abstract public function ormAuditValue();

    const REPLACE = 1;
    const INSERT = 2;
    const UPDATE = 3;
    const DELETE = 4;
    protected $_persistMode = self::REPLACE;
    protected $_primaryKeys = array();
    protected $_table;
    protected $_cascadePersist = true;
    protected $_cascadePopulate = true;
    protected $_cascadeToArray = true;
    protected $_shouldAudit = true;
    protected $_foreignKey;

    private $_tracking;

    public function __construct()
    {
        $primaryKeys = $this->getPrimaryKeys();
        if (count($primaryKeys) == 0) {
            $key = strtolower(self::toClassName($this)) . '_id';
            $primaryKeys[] = $key;
            $this->setPrimaryKeys($primaryKeys);
        }
        if (empty($this->getTable())) {
            $this->setTable(strtolower(self::toClassName($this)));
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

    public function getPersistMode()
    {
        return $this->_persistMode;
    }

    public function setPersistMode($persistMode)
    {
        $this->_persistMode = $persistMode;
        return $this;
    }

    public function getPrimaryKeys()
    {
        return $this->_primaryKeys;
    }

    public function setPrimaryKeys($primaryKeys)
    {
        $this->_primaryKeys = $primaryKeys;
        return $this;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function setTable($table)
    {
        $this->_table = $table;
        return $this;
    }

    public function getCascadePersist()
    {
        return $this->_cascadePersist;
    }

    public function setCascadePersist($cascadePersist)
    {
        $this->_cascadePersist = $cascadePersist;
        return $this;
    }

    public function getCascadePopulate()
    {
        return $this->_cascadePopulate;
    }

    public function setCascadePopulate($cascadePopulate)
    {
        $this->_cascadePopulate = $cascadePopulate;
        return $this;
    }

    public function getCascadeToArray()
    {
        return $this->_cascadeToArray;
    }

    public function setCascadeToArray($cascadeToArray)
    {
        $this->_cascadeToArray = $cascadeToArray;
        return $this;
    }

    public function getShouldAudit()
    {
        return $this->_shouldAudit;
    }

    public function setShouldAudit($shouldAudit)
    {
        $this->_shouldAudit = $shouldAudit;
        return $this;
    }

    public function getForeignKey()
    {
        return $this->_foreignKey;
    }

    public function setForeignKey($foreignKey)
    {
        $this->_foreignKey = $foreignKey;
        return $this;
    }

    public static function toClassName($obj)
    {
        return (new \ReflectionClass($obj))->getShortName();
    }

    public function populateWithArray($array)
    {
        foreach ($array as $key => $value) {
            if ($this->$key() instanceof Orm) {
                $this->$key()->populateWithArray($value);
            } else {
                $this->$key($value);
            }
        }
        $this->postPopulate();
    }

    public function populate()
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE 1 ";
        $doPopulate = false;
        foreach ($this->getPrimaryKeys() as $key) {
            $value = $this->$key();
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
        if (!$this->getCascadePopulate()) {
            return true;
        }
        $fields = $this->fields();
        foreach ($fields as $field) {
            $obj = $this->$field();
            if (!$obj instanceof Orm) {
                continue;
            }
            foreach ($obj->getPrimaryKeys() as $key) {
                $newKey = $key;
                if ($key == 'id') { // get the foreign key
                    if ($obj->getForeignKey()) {
                        $newKey = $obj->getForeignKey();
                    } else {
                        $newKey = strtolower(self::toClassName($obj)) . '_id';
                    }
                }
                if (in_array($newKey, $fields)) {
                    $obj->$key($this->$newKey());
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
                $this->$col($val);
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

    public function audit($obj)
    {
        $primaryKeys = $obj->getPrimaryKeys();
        $objectIdKey = $primaryKeys[0];

        $auditId = $this->ormSequence()->nextId(2);
        $class = $this->ormAudit();
        $audit = new $class();
        $audit->auditId($auditId)
            ->objectClass(self::toClassName($obj))
            ->objectId($obj->$objectIdKey())
            ->userId(Registry::get('user_id'))
            ->type($obj->getPersistMode())
            ->datetime(date('Y-m-d H:i:s'));
        if ($obj instanceof Orm) {
            foreach ($obj->fields() as $field) {
                $class = $this->ormAuditValue();
                $auditValue = new $class();
                $auditValue->auditId($auditId)
                    ->key($field);
                if (is_object($obj->$field)) {
                    $auditValue->value(self::toClassName($obj->$field()));
                } else {
                    $auditValue->value((string)$obj->$field());
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
            $value = $this->$field();
            if ($value instanceof Orm) {
                if ($this->getCascadeToArray()) {
                    $array[$field] = $value->toArray();
                }
            } else {
                $array[$field] = $value;
            }
        }
        return $array;
    }

    public function toSql()
    {
        $fields = $this->fields();
        $where = array('1');
        $fieldValues = array();
        $fieldNames = array();
        for ($i = 0, $ctr = count($fields); $i < $ctr; $i++) {
            $field = $fields[$i];
            $val = $this->$field();
            if (is_object($val)) {
                if ($val instanceof Orm && $this->getCascadePersist()) {
                    $val->setPersistMode($this->getPersistMode());
                    $val->persist();
                    $foreignKey = $field.'_id';
                    if (in_array($foreignKey, $fields)) {
                        $primaryKeys = $val->getPrimaryKeys();
                        $primaryKey = $primaryKeys[0];
                        $this->$foreignKey($val->$primaryKey());
                    }
                }
                continue;
            } elseif (is_array($val)) {
                foreach ($val as $item) {
                    if ($item instanceof Orm) {
                        $item->persist();
                    }
                }
                continue;
            }
            if ($this->getPersistMode() == self::DELETE) {
                if (in_array($field, $this->getPrimaryKeys()) && ($val > 0
                    || (!is_numeric($val) && strlen($val) > 0))
                ) {
                    $where[] = " AND `$field` = "
                             . $this->dbAdapter()->quote($val);
                }
                // code below is just for replace/insert
                continue;
            }

            if (in_array($field, $this->getPrimaryKeys()) && !$val > 0) {
                $where[] = " AND `$field` = ".$this->dbAdapter()->quote($val);
                $seqTable = 1;
                if (self::toClassName($this) == 'Audit'
                    || self::toClassName($this) == 'AuditValue'
                ) {
                    $seqTable = 2;
                }
                if (self::toClassName($this) == 'Audit'
                    || self::toClassName($this) == 'AuditValue'
                    || $this->getPersistMode() != self::DELETE
                ) {
                    $lastId = $this->ormSequence()->nextId($seqTable);
                    $this->$field($lastId);
                    $val = $lastId;
                }
            }

            if (substr($field, 0, 1) != '_') {
                $fieldValues[] = " `$field` = "
                               . $this->dbAdapter()->quote($val);
                $fieldNames[] = $field;
            }
        }

        if ($this->getPersistMode() == self::REPLACE) {
            $sql = "REPLACE INTO `" . $this->getTable() . "` SET ";
        } elseif ($this->getPersistMode() == self::INSERT) {
            $sql = "INSERT INTO `" . $this->getTable() . "` SET ";
        } elseif ($this->getPersistMode() == self::UPDATE) {
            $sql = "UPDATE `" . $this->getTable() . "` SET ";
        } elseif ($this->getPersistMode() == self::DELETE) {
            $sql = "DELETE FROM `" . $this->getTable() . "` ";
        }

        if ($fieldNames) {
            $fieldValues = array();
            foreach ($fieldNames as $field) {
                $val = $this->$field();
                $fieldValues[] = " `{$field}` = "
                               . $this->dbAdapter()->quote($val);
            }
            $sql .= implode(', ', $fieldValues);
        }

        if ($this->getPersistMode() == self::UPDATE
            || $this->getPersistMode() == self::DELETE
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

    public function getCollection($dbSelect = null)
    {
        $class = get_class($this) . 'Collection';
        if (class_exists($class)) {
            $collection = new $class();
        } else {
            $collection = new Collection(get_class($this), $dbSelect);
        }
        return $collection;
    }

    public function postPersist()
    {
    }

    public function shouldAudit()
    {
        return $this->getShouldAudit();
    }

    public function rewind()
    {
        if ($this->_tracking == null) {
            $this->_tracking = $this->fields();
        }
        reset($this->_tracking);
    }

    public function current()
    {
        $key = current($this->_tracking);
        $var = $this->$key();
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
