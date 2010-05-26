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
 * administrator main page
 * 
 * @return       output
 */
function Communicator_admin_main()
{
    return pnRedirect(pnModURL('Communicator','admin','settings'));
}

/**
 * administrator settings and configuration
 * 
 * @return       output
 */
function Communicator_admin_settings()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

	// Create output object
	$render = FormUtil::newpnForm('Communicator');
	Loader::requireOnce('modules/Communicator/includes/classes/admin/settings.php');
	return $render->pnFormExecute('communicator_admin_settings.htm',new communicator_admin_settings_handler());
}