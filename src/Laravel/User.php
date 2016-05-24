<?php
/*****************************************************************************\
 *                                                                           *
 *  User.php                                                                 *
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

class User extends Model
{
    protected $id;
    protected $person_id;
    protected $person;
    protected $username;
    protected $password;
    protected $salt;
    protected $status;

    protected $_primaryKeys = array('id');
    protected $_table = 'hive_users';
    protected $_foreignKey = 'user_id';

    public function init()
    {
        $this->person = new Person();
    }

    public function __get($key)
    {
        if (in_array($key, $this->fields())) {
            return $this->$key;
        } elseif (in_array($key,$this->person->fields())) {
            return $this->person->__get($key);
        } elseif (!is_null(parent::__get($key))) {
            return parent::__get($key);
        } elseif (!is_null($this->person->__get($key))) {
            return $this->person->__get($key);
        }
        return parent::__get($key);
    }
}
