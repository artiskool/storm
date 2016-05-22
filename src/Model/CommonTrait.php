<?php
/*****************************************************************************\
 *                                                                           *
 *  CommonTrait.php                                                          *
 *                                                                           *
 *  @author     Arthur Layese (arthur@layese.com) 2016                       *
 *  @package    Storm\Model                                                  *
 *  @copyright  (c) 2016 Arthur Layese (http://storm.com.ph)                 *
 *  @license    This file is licensed under the GPL V3, you can find a copy  *
 *              of that license by visiting:                                 *
 *              http://www.fsf.org/licensing/licenses/gpl.html               *
 *                                                                           *
\*****************************************************************************/

namespace Storm\Model;

trait CommonTrait
{
    public function dbAdapter()
    {
        static $dbAdapter = null;
        if ($dbAdapter === null) {
            $dbAdapter = new DbLaravel();
        }
        return $dbAdapter;
    }

    public function init()
    {
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
