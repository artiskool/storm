<?php
/*****************************************************************************\
 *                                                                           *
 *  Person.php                                                               *
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

class Person extends Model
{
    protected $person_id;
    protected $last_name;
    protected $first_name;
    protected $middle_name;
    protected $birthdate;
    protected $marital_status;
    protected $gender;
    protected $email;
    protected $photo;
    protected $status;

    protected $_primaryKeys = array('person_id');
    protected $_table = 'hive_persons';
}
