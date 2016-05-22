<?php
/*****************************************************************************\
 *                                                                           *
 *  Registry.php                                                             *
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

class Registry
{
    protected static $container = array();

    public static function get($key, $default = null)
    {
        $value = $default;
        if (array_key_exists($key, self::$container)) {
            $value = self::$container[$key];
        }
        return $value;
    }

    public static function set($key, $value)
    {
        self::$container[$key] = $value;
    }
}
