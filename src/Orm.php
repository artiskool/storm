<?php
/*****************************************************************************\
 *                                                                           *
 *  Orm.php                                                                  *
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

abstract class Orm implements OrmInterface, \Iterator
{
    abstract public function dbAdapter();
    abstract public function init();

    abstract public function ormSequence();
    abstract public function ormAudit();
    abstract public function ormAuditValue();

    const REPLACE = 1;
    const INSERT = 2;
    const UPDATE = 3;
    const DELETE = 4;
    protected $_inPersist = false;
    protected $_persistMode = self::REPLACE;
    protected $_primaryKeys = array();
    protected $_table;
    protected $_cascadePersist = true;
    protected $_shouldAudit = true;
    protected $_cascadePopulate = true;
    public static $_nsdrNamespace = false;
    protected $_audit;
    protected $_auditValue;
    protected $_foreignKey;

    public function __construct()
    {
        $this->init();

        if (count($this->_primaryKeys) == 0) {
            $key = strtolower(self::className($this)) . '_id';
            $this->_primaryKeys[] = $key;
        }
        if (empty($this->_table)) {
            $this->_table = strtolower(self::className($this));
        }
    }

    public static function className($obj)
    {
        return (new \ReflectionClass($obj))->getShortName();
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

    public function populateWithArray($array)
    {
        foreach ($array as $key => $value) {
            if ($this->$key instanceof OrmInterface) {
                $this->$key->populateWithArray($value);
            } else {
                $this->__set($key, $value);
            }
        }
        $this->postPopulate();
    }

    public function populate()
    {
        $sql = "SELECT * FROM " . $this->_table . " WHERE 1 ";
        $doPopulate = false;
        foreach ($this->_primaryKeys as $key) {
            if ($this->$key > 0 || strlen($this->$key) > 0) {
                $doPopulate = true;
                $sql .= " AND $key = '"
                     . preg_replace('/[^0-9a-z_A-Z-\.]/', '', $this->$key)
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
            if (!$obj instanceof OrmInterface) {
                continue;
            }
            foreach ($obj->_primaryKeys as $key) {
                $newKey = $key;
                if ($key == 'id') { // get the foreign key
                    if ($obj->_foreignKey) {
                        $newKey = $obj->_foreignKey;
                    } else {
                        $newKey = strtolower(self::className($obj)) . '_id';
                    }
                }
                if (in_array($newKey, $fields)) {
                    $obj->$key = $this->$newKey;
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
        $this->_inPersist = true;
        $sql = $this->toSQL();

        $this->_inPersist = false;
        $this->dbAdapter()->statement($sql);
        $this->postPersist();
        if ($this->shouldAudit()
            && self::className($this) != 'Audit'
            && self::className($this) != 'AuditValue'
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
        $class = $this->ormAudit();
        $audit = new $class();
        $audit->objectClass = self::className($obj);
        $objectIdKey = $obj->_primaryKeys[0];
        $audit->auditId = $this->ormSequence()->nextId(2);
        $audit->objectId = $obj->$objectIdKey;
        $audit->userId = Registry::get('user_id');
        $audit->type = $obj->_persistMode;
        $audit->datetime = date('Y-m-d H:i:s');
        if ($obj instanceof OrmInterface) {
            foreach ($obj->fields() as $field) {
                $class = $this->ormAuditValue();
                $auditValue = new $class();
                $auditValue->auditId = $audit->auditId;
                $auditValue->key = $field;
                if (is_object($obj->$field)) {
                    $auditValue->value = self::className($obj->$field);
                } else {
                    $auditValue->value = (string)$obj->$field;
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
        return print_r($this->toArray(), true);
    }

    public function toArray()
    {
        $fields = $this->fields();
        $array = array();
        foreach ($fields as $value) {
            if ($this->$value instanceof OrmInterface) {
                $array[$value] = $this->$value->toArray();
            } else {
                $array[$value] = $this->$value;
            }
        }
        return $array;
    }

    public function toSQL()
    {
        $fields = $this->fields();
        $where = array('1');
        $fieldValues = array();
        $fieldNames = array();
        for ($i = 0, $ctr = count($fields); $i < $ctr; $i++) {
            $field = $fields[$i];
            $val = $this->__get($field);
            if (is_object($val)) {
                if ($val instanceof OrmInterface
                    && $this->_cascadePersist
                ) {
                    $val->setPersistMode($this->_persistMode);
                    $val->persist();
                    $foreignKey = $field.'_id';
                    if (in_array($foreignKey, $fields)) {
                        $primaryKey = $val->_primaryKeys[0];
                        $this->$foreignKey = $val->$primaryKey;
                    }
                }
                continue;
            } elseif (is_array($val)) {
                foreach ($val as $item) {
                    if ($item instanceof OrmInterface) {
                        $item->persist();
                    }
                }
                continue;
            }
            if ($this->_persistMode == self::DELETE) {
                if (in_array($field,$this->_primaryKeys) && ($val > 0
                    || (!is_numeric($val) && strlen($val) > 0))
                ) {
                    $where[] = " AND `$field` = "
                             . $this->dbAdapter()->quote($val);
                }
                // code below is just for replace/insert
                continue;
            }

            if (in_array($field, $this->_primaryKeys) && !$val > 0) {
                $where[] = " AND `$field` = ".$this->dbAdapter()->quote($val);
                $seqTable = 1;
                if (self::className($this) == 'Audit'
                    || self::className($this) == 'AuditValue'
                ) {
                    $seqTable = 2;
                }
                if (self::className($this) == 'Audit'
                    || self::className($this) == 'AuditValue'
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

        if ($this->_persistMode == self::REPLACE) {
            $sql = "REPLACE INTO `{$this->_table}` SET ";
        } elseif ($this->_persistMode == self::INSERT) {
            $sql = "INSERT INTO `{$this->_table}` SET ";
        } elseif ($this->_persistMode == self::UPDATE) {
            $sql = "UPDATE `{$this->_table}` SET ";
        } elseif ($this->_persistMode == self::DELETE) {
            $sql = "DELETE FROM `{$this->_table}` ";
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
        return json_encode($this->toArray());
    }

    public function clearPrimaryKeys()
    {
        $this->_primaryKeys = array();
    }

    public function getIterator($dbSelect = null)
    {
        $class = get_class($this) . 'Iterator';
        if (class_exists($class)) {
            $iterator = new $class();
        } else {
            $iterator = new OrmIterator(get_class($this), $dbSelect);
        }
        return $iterator;
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
        if ($this->_tracking == null) {
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

    public function getControllerName()
    {
        $className = get_class($this);
        $controllerName = $className . 'Controller';
        return $controllerName;
    }

    public function signatureNeeded()
    {
        return true;
    }

    public function toXml()
    {
        return self::recurseXML($this);
    }

    public static function recurseXml($data,
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
