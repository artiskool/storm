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

interface Orm
{
    public function dbAdapter();

    public function ormSequence();
    public function ormAudit();
    public function ormAuditValue();

    public function persist();
    public function setPersistMode($mode);
    public function populate();
    public function fields();
    public function postPersist();
    public function populateWithArray($array);
    public function populate();
    public function postPopulate();
    public function populateWithSql($sql);
    public function persist();
    public function audit($obj);
    public function fields();
    public function toString();
    public function toArray();
    public function toSql();
    public function getCollection($dbSelect = null);
    public function postPersist();
    public function shouldAudit();
    public function toXml();
}
