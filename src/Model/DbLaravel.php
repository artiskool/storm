<?php
/*****************************************************************************\
 *                                                                           *
 *  DbLaravel.php                                                            *
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

use Storm\DbAdapter\Laravel as DbAdapterLaravel;

class DbLaravel extends DbAdapterLaravel
{
    public function getConfig()
    {
        $driver = \Config::get('database.default');
        $config = \Config::get('database.connections.'.$driver);
        return array(
            'hostname' => $config['host'],
            'username' => $config['username'],
            'password' => $config['password'],
            'database' => $config['database'],
        );
    }
}
