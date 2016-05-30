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
\*****************************************************************************/

namespace Storm;

abstract class Orm
{
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

    abstract public function init();
    abstract public function dbAdapter();

    abstract public function ormSequence();
    abstract public function ormAudit();
    abstract public function ormAuditValue();

    abstract public function setPersistMode($mode);
    abstract public function populateWithArray($array);
    abstract public function populate();
    abstract public function postPopulate();
    abstract public function populateWithSql($sql);
    abstract public function persist();
    abstract public function audit($obj);
    abstract public function fields();
    abstract public function toString();
    abstract public function toArray();
    abstract public function getCollection($dbSelect = null);
    abstract public function postPersist();
    abstract public function shouldAudit();
    abstract public function toXml();
}
