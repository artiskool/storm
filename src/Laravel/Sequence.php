<?php
/*****************************************************************************\
 *                                                                           *
 *  Sequence.php                                                             *
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

use Storm\Sequence as StormSequence;

class Sequence extends StormSequence
{
	protected $_table = 'hive_sequences';
}
