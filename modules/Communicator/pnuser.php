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
 * Communicator main function
 *
 * @return  output
 */
function Communicator_user_main()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');
    
    // variables for display
    $uid        = pnUserGetVar('uid');
    $dateformat = pnModGetVar('Communicator','dateformat');
    $communicator_messages_list_window_height = (int)pnUserGetVar('communicator_messages_list_window_height',$uid);
    if (!($communicator_messages_list_window_height > 50)) {
        $communicator_messages_list_window_height = 100;
    }

    // Create output object
    $render = pnRender::getInstance('Communicator');
    $render->assign('communicator_messages_list_window_height', $communicator_messages_list_window_height);
    $render->assign('dateformat', $dateformat);
    // get single message if message was selected
    $id     = (int)    FormUtil::getPassedValue('id');
    $action = (string) FormUtil::getPassedValue('action');
    $folder =          FormUtil::getPassedValue('folder');
    if (!($folder >= -1)) {
        $folder = 0;
    }
    $sort   = (string) FormUtil::getPassedValue('sort');
    $mode   = (string) FormUtil::getPassedValue('mode');
    // Validate sort order will take place in API function
    // get | change a single message ?
    if ($id > 0) {
        $args = array (
            'id'    => $id,
            'read'  => 1
        );
        if ($action == 'flag') {
            $args['flag'] = 1;
            LogUtil::registerStatus(__('Message was marked!', $dom));
        } else if ($action == 'deflag') {
            $args['deflag'] = 1;
            LogUtil::registerStatus(__('Message was unmarked!', $dom));
        } else if ($action == 'sendreceipt') {
            $args['receipt'] = 1;
            LogUtil::registerStatus(__('Notice of receipt was sent to message sender.', $dom));
        } else if ($action == 'delete') {
            $deleteAction = pnModAPIFunc('Communicator','user','del',array('id' => $id));
            if (!$deleteAction) {
                LogUtil::registerError(__('Error: Message could not be deleted!', $dom));
            } else {
                LogUtil::registerStatus(__('Selected message was deleted!', $dom));
            }
        }
        $message = pnModAPIFunc('Communicator','user','get',$args);
        $render->assign('message',$message);
    }

    // redirect if action was set
    if (isset($action) && ($action != '')) {
        return pnRedirect(pnModURL('Communicator','user','main',array('folder' => $folder, 'id' => $id)));
    }

    // get messages
    $args = array(
        'uid'           => $uid,
        'header_ob1'    => $sort,
        'header_ob2'    => $mode,
        'header_grp'    => $header_grp,
        'folder'        => $folder
    );
    $messages = pnModAPIFunc('Communicator','user','getAll',$args);
    

    // Assign variables to template    
    $render->assign('header_ob1',   $header_ob1);
    $render->assign('header_ob2',   $header_ob2);
    $render->assign('header_grp',   $header_grp);
    $render->assign('messages',     $messages);
    $render->assign('folder',       $folder);
    $render->assign('id',           $id);
    return $render->fetch('communicator_user_main.htm');
}


/**
 * compose message function
 * 
 * @return       output
 */
function Communicator_user_compose()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }
    
	// Create output object
	$render = FormUtil::newpnForm('Communicator');
	Loader::requireOnce('modules/Communicator/includes/classes/user/compose.php');
	return $render->pnFormExecute('communicator_user_compose.htm',new communicator_user_compose_handler());
}


/**
 * store user's preferences
 *
 * @return  redirect
 */
function Communicator_user_preferences()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    $settings['disableNotification'] = (int)    FormUtil::getPassedValue('disableNotification');
    $settings['enableAutoresponder'] = (int)    FormUtil::getPassedValue('enableAutoresponder');
    $settings['autoresponder_text']  = (string) FormUtil::getPassedValue('autoresponder_text');
    $result = pnModAPIFunc('Communicator','user','storeSettings',$settings);
    if (!$result) { 
        LogUtil::registerError(__('Settings could not be stored!', $dom));
    } else {
        LogUtil::registerStatus(__('Your settings have been stored!', $dom));
    }
    // return to index = settings page
    return pnRedirect(pnModURL('Communicator'));
}

/**
 * create folder
 *
 * @return redirect
 */
function Communicator_user_createFolder()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    $folder = (string) FormUtil::getPassedValue('create_folder');
    $result = pnModAPIFunc('Communicator','user','createFolder',array('folder' => $folder));
    if (!$result) { 
        LogUtil::registerError(__('Folder could not be created!', $dom));
    } else {
        LogUtil::registerStatus(__('Folder was created.', $dom));
    }
    return pnRedirect(pnModURL('Communicator'));
}

/**
 * modify folder
 *
 * @return redirect
 */
function Communicator_user_modifyFolder()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }
    
    // Get parameters
    $id     = (int)    FormUtil::getPassedValue('id');
    $delete = (int)    FormUtil::getPassedValue('delete');
    $title  = (string) FormUtil::getPassedValue('folder');

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    if ($delete == 1) {
        $result = pnModAPIFunc('Communicator','user','delFolder',array('id' => $id));
        if (!$result) { 
            LogUtil::registerError(__('Folder could not be deleted!', $dom));
        } else {
            LogUtil::registerStatus(__('Folder deleted!', $dom));
        }
    } else {
        $result = pnModAPIFunc('Communicator','user','renameFolder',array('id' => $id, 'title' => $title));
        if (!$result) { 
            LogUtil::registerError(__('Folder could not be renamed!', $dom));
        } else {
            LogUtil::registerStatus(__('Folder was renamed!', $dom));
        }
    }
 
    return pnRedirect(pnModURL('Communicator'));
}

/**
 * move message into folder
 *
 * @return redirect
 */
function Communicator_user_moveToFolder()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters
    $message_id = FormUtil::getPassedValue('message_id');
    $folder_id  = FormUtil::getPassedValue('folder_id');

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');
    
    // get message first
    $result = pnModAPIFunc('Communicator','user','moveMessageToFolder',array('message_id' => $message_id, 'folder_id' => $folder_id));
    if ($result) {
        LogUtil::registerStatus(__('Message was moved to folder.', $dom));
    } else {
        LogUtil::registerError(__('Message could not be moved to target folder!', $dom));
    }
    return pnRedirect(pnModURL('Communicator'));
}

/**
 * show print view for message
 *
 * @return output
 */
function Communicator_user_print()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Parameters
    $id = (int) FormUtil::getPassedValue('id');

    // get message
    $message = pnModAPIFunc('Communicator','user','get',array('id' => $id));
    $output = pnModAPIFunc('Communicator','output','showMessage',array('message' => $message, 'printer_view' => 1));
    return $output;
    print $output;
    return true;
    
}

/**
 * System init hook function
 *
 */
function Communicator_user_systeminit()
{
    pnModAPIFunc('Communicator','user','systeminit');
}



/** **** **** THIS FUNCTIONS BELOW WERE MADE TO BE COMPATIBLE TO INTERCOM MODULE **** **** **/

/**
 * new pm / compose message redirect
 *
 */
function Communicator_user_newpm() {
    $uid   = (int) FormUtil::getPassedValue('uid');
    $uname =       FormUtil::getPassedValue('uname');
    if (!($uid > 1)) {
        $uid = (int) pnModAPIFunc('Communicator','user','getIDFromUname',array('uname' => $uname));
    }
    return pnRedirect(pnModURL('Communicator','user','compose',array('uid' => $uid)));
}