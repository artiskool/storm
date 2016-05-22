<?php
/*****************************************************************************\
 *                                                                           *
 *  User.php                                                                 *
 *                                                                           *
 *  @author     Arthur Layese (arthur@layese.com) 2016                       *
 *  @package    Storm                                                        *
 *  @copyright  (c) 2016 Arthur Layese (http://storm.com.ph)                 *
 *  @license    This file is licensed under the GPL V3, you can find a copy  *
 *              of that license by visiting:                                 *
 *              http://www.fsf.org/licensing/licenses/gpl.html               *
 *                                                                           *
\*****************************************************************************/

namespace Storm\Model;

class User extends Model
{
    protected $user_id;
    protected $person_id;
    protected $person;
    protected $username;
    protected $password;
    protected $salt;
    protected $status;

    protected $_primaryKeys = array('user_id');
    protected $_table = 'hive_users';

    public function init()
    {
        $this->person = new Person();
    }

    public function populateWithUsername($username = null)
    {
        if ($username === null) {
            $username = $this->username;
        }
        $db = Zend_Registry::get('dbAdapter');
        $sql = "SELECT * from " . $this->_table . " WHERE 1 "
             . " and username = " . $db->quote($username);
        $this->populateWithSql($sql);
        $this->person->person_id = $this->person_id;
        $this->person->populate();
    }

    public function populateWithPersonId($personId = null)
    {
        if ($personId === null) {
            $personId = $this->person_id;
        }
        $db = Zend_Registry::get('dbAdapter');
        $sql = "SELECT * from " . $this->_table . " WHERE 1 "
             . " and person_id = " . $db->quote($personId);
        $this->populateWithSql($sql);
        $this->person->person_id = $this->person_id;
        $this->person->populate();
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
