<?php
/*****************************************************************************\
 *                                                                           *
 *  Audit.php                                                                *
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

abstract class Audit extends Orm
{
    protected $audit_id;
    protected $object_class;
    protected $object_id;
    protected $user_id;
    protected $ref_id;
    protected $type;
    protected $message;
    protected $datetime;
    protected $ip_address;

    protected $_table = 'audits';
    protected $_primaryKeys = array('audit_id');
    protected $_persistMode = Orm::INSERT;
    protected $_ormPersist = false;
    public static $_processedAudits = false;
    public static $_synchronousAudits = false;

    public function persist()
    {
        if (!strlen($this->ip_address) > 0 && isset($_SERVER['REMOTE_ADDR'])) {
            $this->ip_address = $_SERVER['REMOTE_ADDR'];
        }
        if (!$this->userId) {
            $audit->userId = Registry::get('user_id');
        }
        if (self::$_synchronousAudits || $this->_ormPersist) {
            return parent::persist();
        }
        if ($this->shouldAudit()) {
            $sql = $this->toSQL();
            AuditLog::appendSql($sql);
        }
    }

    public function getRefId()
    {
        return (int)$this->ref_id;
    }
}
