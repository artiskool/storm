<?php
/*****************************************************************************\
 *                                                                           *
 *  UserIterator.php                                                         *
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

class UserIterator extends ModelIterator
{
    public function __construct($select = null)
    {
        $user = new User();
        if (null === $select) {
            $select = "SELECT * FROM {$user->_table}";
        }
        parent::__construct($user, $select);
    }
}
