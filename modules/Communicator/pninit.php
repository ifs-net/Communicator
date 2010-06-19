<?php
/**
 * @package      Communicator
 * @version      $Id$
 * @author       Florian Schießl
 * @link         http://www.ifs-net.de
 * @copyright    Copyright (C) 20010
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * initialise the module
 *
 * @return       bool       true on success, false otherwise
 */
function Communicator_init()
{
  	if (!DBUtil::createTable('communicator_mail_header')) return false;
  	if (!DBUtil::createTable('communicator_mail_body')) return false;
  	if (!DBUtil::createTable('communicator_folders')) return false;

    // Module Variables
    pnModSetVar('Communicator', 'allow_html',       0);     // use scribite's WYSIWYG?
    pnModSetVar('Communicator', 'spam_allow_max',   20);    // allow maximum of X messages in ...
    pnModSetVar('Communicator', 'spam_allowed_time',60);    // ... X minutes as spam protection
    pnModSetVar('Communicator', 'quota',            0);     // messages a user is allowed to have, 0 = unlimited
    pnModSetVar('Communicator', 'dateformat',       '%d.%m.%y %H:%M');    // default date format

	// install system init hook
    $dom = ZLanguage::getModuleDomain('Communicator');

    if (!pnModRegisterHook('zikula', 'systeminit', 'GUI', 'Communicator', 'user', 'systeminit')) {
        return LogUtil::registerError(__('Error creating Hook!', $dom));
    }
    pnModAPIFunc('Modules', 'admin', 'enablehooks', array('callermodname' => 'zikula', 'hookmodname' => 'Communicator'));

    // Initialisation successful
    return true;
}

/**
 * delete the module
 *
 * @return       bool       true on success, false otherwise
 */
function Communicator_delete()
{
  	if (!DBUtil::dropTable('communicator_mail_header')) return false;
  	if (!DBUtil::dropTable('communicator_mail_body')) return false;
  	if (!DBUtil::dropTable('communicator_folders')) return false;

    // Delete any module variables
    pnModDelVar('Communicator');
    
    // delete the system init hook
    $dom = ZLanguage::getModuleDomain('Communicator');
    // delete the system init hook
    if (!pnModUnregisterHook('zikula', 'systeminit', 'GUI', 'Communicator', 'user', 'systeminit')) {
        return LogUtil::registerError(__('Error deleting Hook!', $dom));
    }

    // Deletion successful
    return true;
}

function Communicator_upgrade($oldversion)
{
    $dom = ZLanguage::getModuleDomain('Communicator');
    switch($oldversion) {
        case '0.9.0':
        case '0.9.1':
        case '1.0.0':
        default:
    	    return true;
    }
}
