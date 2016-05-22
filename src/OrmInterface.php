<?php
/*****************************************************************************\
 *                                                                           *
 *  OrmInterface.php                                                         *
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

interface OrmInterface
{
    public function persist();
    public function setPersistMode($mode);
    public function populate();
    public function fields();
    public function postPersist();
}
