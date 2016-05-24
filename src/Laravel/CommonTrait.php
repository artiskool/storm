<?php
/*****************************************************************************\
 *                                                                           *
 *  CommonTrait.php                                                          *
 *                                                                           *
 *  @author     Arthur Layese (arthur@layese.com) 2016                       *
 *  @package    Storm\Laravel                                                *
 *  @copyright  (c) 2016 Arthur Layese (http://storm.com.ph)                 *
 *  @license    This file is licensed under the GPL V3, you can find a copy  *
 *              of that license by visiting:                                 *
 *              http://www.fsf.org/licensing/licenses/gpl.html               *
 *                                                                           *
\*****************************************************************************/

namespace Storm\Laravel;

use Storm\AuditLog;

trait CommonTrait
{
    public function dbAdapter()
    {
        static $dbAdapter = null;
        if ($dbAdapter === null) {
            $dbAdapter = new Db();
        }
        return $dbAdapter;
    }

    public function init()
    {
        if (!defined('STORM_REGISTER_SHUTDOWN')) {
            $dbConfig = $this->dbAdapter()->getConfig();
            // this MUST be required as this is used as DB connection
            AuditLog::setDbConfig($dbConfig);
            register_shutdown_function(array(
                'Storm\AuditLog',
                'closeConnection'
            ));
            define('STORM_REGISTER_SHUTDOWN', true);
        }
    }

    public function ormSequence()
    {
        return new Sequence($this->dbAdapter());
    }

    public function ormAudit()
    {
        return new Audit();
    }

    public function ormAuditValue()
    {
        return new AuditValue();
    }
}
