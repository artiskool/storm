<?php
/*****************************************************************************\
 *                                                                           *
 *  AuditValue.php                                                           *
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

abstract class AuditValue extends Entity
{
    protected $audit_value_id;
    protected $audit_id;
    protected $key;
    protected $value;
    protected $_table = 'audit_values';
    protected $_primaryKeys = array('audit_value_id');
    protected $_persistMode = Orm::INSERT;
    protected $_ormPersist = false;

    public function persist()
    {
        if ($this->_ormPersist) {
            return parent::persist();
        }
        if ($this->shouldAudit()) {
            $sql = $this->toSQL();
            AuditLog::appendSql($sql);
        }
    }
}
