<?php
/*****************************************************************************\
 *                                                                           *
 *  Main.php                                                                 *
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

use Storm\Registry;
use Storm\AuditLog;

class Main
{
    public static function run()
    {
        $user = new User();
        Registry::set('user_id', 123);

        $dbConfig = $user->dbAdapter()->getConfig();

        AuditLog::setDbConfig($dbConfig); // this MUST be required as this is used as DB connection
        register_shutdown_function(array('Storm\AuditLog', 'closeConnection'));

        /*
        $iterator = $user->getIterator();
        $iterator->setFilters(array('user_id' => array(1,2, 1146)));
        foreach ($iterator as $obj) {
            print_r($obj->toArray());
        }
        exit;
        */

        //$user->user_id = 3;
        //$user->id = 1442;
        $user->populate();
        $user->username = 'test';
        $user->password = 'test';
/*
        //$user->populate();
        $user->person->email = 'me@art.net.ph';
        $user->person->lastName = 'Layese';
        $user->person->firstName = 'Art';
        $user->persist();
*/

        echo $user;
        //print_r($user->toArray());
        echo '<br><br>';
    }
}
