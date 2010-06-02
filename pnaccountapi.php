<?php
/**
 * @package      Communicator
 * @version      $Id: pnoutputapi.php 4 2010-05-26 16:29:35Z quan $
 * @author       Florian Schießl
 * @link         http://www.ifs-net.de
 * @copyright    Copyright (C) 20010
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Return an array of items to show in the your account panel
 *
 * @return   array   array of items, or false on failure
 */
function Communicator_accountapi_getall($args)
{
    // the array that will hold the options
    $items = null;

    // show link for users only
    if(!pnUserLoggedIn()) {
        // not logged in
        return $items;
    }

    // Create an array of links to return
    if(SecurityUtil::checkPermission('Communicator::', '::', ACCESS_OVERVIEW)) {
        $dom = ZLanguage::getModuleDomain('Communicator');
        return array(
            array(  'url'     => pnModURL('Communicator'),
                    'title'   => __('Inbox', $dom),
                    'icon'    => 'letter.gif'));
    }

    // Return the items
    return $items;
}
