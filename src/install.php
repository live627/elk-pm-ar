<?php

/**
 * @package PM Auto Respond
 * @version 1.0-beta.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If we have found SSI.php and we are outside of ElkArte, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
    require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('ELK')) // If we are outside ElkArte and can't find SSI.php, then throw an error
    die('<b>Error:</b> Cannot install - please verify you put this file in the same place as ElkArte\'s SSI.php.');

$dbtbl = db_table();

$columns = array(
    array(
        'name' => 'id_rule',
        'type' => 'int',
        'size' => '10',
        'unsigned' => true,
        'auto' => true,
    ),
    array(
        'name' => 'id_member',
        'type' => 'int',
        'size' => '10',
        'unsigned' => true,
    ),
    array(
        'name' => 'rule_name',
        'type' => 'varchar',
        'size' => '60',
    ),
    array(
        'name' => 'criteria',
        'type' => 'text',
    ),
    array(
        'name' => 'is_or',
        'type' => 'tinyint',
        'size' => '1',
        'default' => '0',
        'unsigned' => true,
    ),
    array(
        'name' => 'subject',
        'type' => 'varchar',
        'size' => '60',
    ),
    array(
        'name' => 'body',
        'type' => 'text',
    ),
    array(
        'name' => 'save_in_outbox',
        'type' => 'tinyint',
        'size' => '1',
        'default' => '0',
        'unsigned' => true,
    ),
);

$indexes = array(
    array(
        'type' => 'primary',
        'columns' => array('id_rule')
    ),
    array(
        'columns' => array('id_member')
    ),
);

$dbtbl->db_create_table('{db_prefix}pm_ar_rules', $columns, $indexes, array(), 'update_remove');

if (!empty($ssi))
    echo 'Database installation complete!';

