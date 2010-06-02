<?php
/**
 * @package      Communicator
 * @version      $Id$
 * @author       Florian Schießl
 * @link         http://www.ifs-net.de
 * @copyright    Copyright (C) 20010
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/** get user list for ajax autocompleter
 * 
 * @return       output
 */
function Communicator_ajax_getUsers()
{
	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
         print __('You have no permissions for this action',$dom);
        return true;
   }

    // Parameters
    $strlen = 1;    // beginn with X characters as minimum
    $max    = 90;   // maximum users to show

    $param = FormUtil::getPassedValue('parameter');
    $uname = FormUtil::getPassedValue($param);
    

    $output='<ul>';

    // if there is a user existing with this username this has to be the first result
    $uid = pnModAPIFunc('Communicator','user','getIDFromUname',array('uname' => $uname));
    if ($uid > 1) {
      $output.= '<li class="c_autocomplete_highlighter">'.pnUserGetVar('uname',$uid).'</li>';
    }
    
    // get all other users
    if (strlen($uname) >= $strlen) {
        $tables = pnDBGetTables();
        $column = $tables['users_column'];
        $where = $column['uid']." > 1 AND lower(".$column['uname'].') like \'%'.strtolower($uname).'%\' AND lower('.$column['uname'].') != \''.strtolower($uname).'\''; // get every user with characters in its username
        $orderby = $column['uname']." ASC";    // sort by user name
        $columnArray = array('uname');  // for performance reasons get username only
        $userList = DBUtil::selectObjectArray('users',$where,$orderby, -1,$max,$columnarray);
        if ($userList) {
            foreach ($userList as $user) {
                $output.='<li class="c_autocomplete">'.str_replace($uname,'<span class="c_autocomplete_highlighter">'.$uname.'</span>',$user['uname']).'</li>';
            }
        }
    }
    $output.='</ul>';
    print $output;
    return true;
}

/**
 * get message body via ajax
 * @param   $args['id']     int
 * 
 * @return  output
 */
function Communicator_ajax_getMessage()
{
    // Parameter
    $id = (int)FormUtil::getPassedValue('id');
    // Get Message and mark as read
    $message = pnModAPIFunc('Communicator','user','get',array('id' => $id, 'read' => 1));
    $output = pnModAPIFunc('Communicator','output','showMessage',array('message' => $message, 'ajax' => 1));
    print $output;
    return true;
}

/**
 * delete message via ajax
 * @param   $args['id']     int
 * 
 * @return  output
 */
function Communicator_ajax_delMessage()
{
    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
         print __('You have no permissions for this action',$dom);
        return true;
    }

    // Parameter
    $id = (int)FormUtil::getPassedValue('id');

    // Call API
    $deleteAction = pnModAPIFunc('Communicator','user','del',array('id' => $id));

    if ($deleteAction) {
        $output = 'ok';
    } else {
        $output = __('Message could not be deleted',$dom);
    }
    print $output;
    return true;
}


/**
 * flag message via ajax
 * @param   $args['id']     int
 * 
 * @return  output
 */
function Communicator_ajax_flagMessage()
{
    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
         print __('You have no permissions for this action', $dom);
        return true;
    }

    // Parameter
    $id   = (int) FormUtil::getPassedValue('id');
    $flag = (int) FormUtil::getPassedValue('flag');
    if ($flag != 1) {
        $deflag = 1;
        $flag   = 0;
    } else {
        $flag   = 1;
        $deflag = 0;
    }

    // Call API
    $flagAction = pnModAPIFunc('Communicator','user','get',array('id' => $id, 'flag' => $flag, 'deflag' => $deflag, 'read' => 1));
    if ($flagAction) {
        $output = 'ok';
    } else {
        $output = __('Message could not be (de)marked', $dom);
    }
    print $output;
    return true;
}

/**
 * get message header lines for ajax refresh
 *
 * @reutrn  output
 */
function Communicator_ajax_getHeaders()
{

    // get messages
    $folder =          FormUtil::getPassedValue('folder');
    $sort   = (string) FormUtil::getPassedValue('sort');
    $mode   = (string) FormUtil::getPassedValue('mode');

    // Create output element
    $render = pnRender::getInstance('Communicator');

    // get messages
    $uid = pnUserGetVar('uid');
    if (($folder == -1) && ($sort == 'uname')) {
        $sort = 'to_name';
    } else if ($sort == 'uname') {
        $sort = 'from_name';
    }
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
    $render->assign('dateformat',   pnModGetVar('Communicator','dateformat'));
    $render->assign('ajax',         1);
    // Return output
    $output = $render->display('communicator_output_messages_list.htm');
    print $output;
    return true;
}

/**
 * display intro message
 *
 * @return  output
 */
function Communicator_ajax_getIntroText($args)
{
    // get rendering instance and return output
    $render = pnRender::getInstance('Communicator');
    $render->assign('ajax',1 );
    $output = $render->display('communicator_output_introtext.htm');
    print $output;
    return true;
}

/**
 * store user settings
 *
 * @return output
 */
function Communicator_ajax_storeSettings()
{
    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
        print __('You have no permissions for this action', $dom);
        return true;
    }

    $settings['disableNotification'] = (int)    FormUtil::getPassedValue('disableNotification');
    $settings['enableAutoresponder'] = (int)    FormUtil::getPassedValue('enableAutoresponder');
    $settings['autoresponder_text']  = (string) FormUtil::getPassedValue('autoresponder_text');
    $result = pnModAPIFunc('Communicator','user','storeSettings',$settings);
    if (!$result) { 
        $output = __('Settings could not be stored!', $dom);
    } else {
        $output = 'ok';
    }
    print $output;
    return true;    
}

/**
 * create a new folder
 *
 * @param   $args['folder']     string
 * @return output
 */
function Communicator_ajax_createFolder()
{
    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
         print __('You have no permissions for this action', $dom);
        return true;
    }
    $folder = (string) FormUtil::getPassedValue('folder');
    $result = pnModAPIFunc('Communicator','user','createFolder',array('folder' => $folder));
    if (!$result) { 
        $output = __('Folder could not be created!', $dom);
    } else {
        $output = 'ok';
    }
    print $output;
    return true;    
}

/**
 * rename a new folder
 *
 * @param   $args['id']     int     folder id
 * @return output
 */
function Communicator_ajax_modifyFolder()
{
    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

	// Security check 
	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_COMMENT)) {
         print __('You have no permissions for this action', $dom);
        return true;
    }
    $id     = (int)    FormUtil::getPassedValue('id');
    $delete = (int)    FormUtil::getPassedValue('delete');
    $title  = (string) FormUtil::getPassedValue('title');

    if ($delete == 1) {
        $result = pnModAPIFunc('Communicator','user','delFolder',array('id' => $id));
        if (!$result) { 
            $output = __('Folder could not be deleted!', $dom);
        } else {
            $output = 'ok';
        }
    } else {
        $result = pnModAPIFunc('Communicator','user','renameFolder',array('id' => $id, 'title' => $title));
        if (!$result) { 
            $output = __('Folder could not be renamed!', $dom);
        } else {
            $output = 'ok';
        }
    }
    print $output;
    return true;    
}

/**
 * update height of preview window for a user
 *
 * @param   $args['height']
 * @return  void
 */
function Communicator_ajax_updatePreviewHeight()
{
    if (pnUserLoggedIn()) {
        $uid = pnUserGetVar('uid');
        $height = (int) FormUtil::getPassedValue('height');
        if ($height < 50) {
            $height = 50;
        }
        if ($height > 400) {
            $height = 400;
        }
        pnUserSetVar('communicator_messages_list_window_height',$height,$uid);
    }
    return true;
}

/**
 * move message into another folder
 *
 * @param   $args['message_id']     int     message id
 * @param   $args['folder_id']      int     folder id
 *
 * @return output
 */
function Communicator_ajax_moveMessageToFolder()
{
    // Get Parameters
    $message_id = FormUtil::getPassedValue('message_id');
    $folder_id  = FormUtil::getPassedValue('folder_id');
    
    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    // get message first
    $result = pnModAPIFunc('Communicator','user','moveMessageToFolder',array('message_id' => $message_id, 'folder_id' => $folder_id));
    if ($result) {
        $output = 'ok';
    } else {
        $output = __('Message could not be moved to target folder!', $dom);
    }
    print $output;
    return true;
}

/**
 * refresh folder menu list
 *
 * @return  output
 */
function Communicator_ajax_showAjaxFoldersMenu()
{
    $output = pnModAPIFunc('Communicator','output','showAjaxFolderMenu');
    print $output;
    return true;
}

/**
 * send notice of receipt
 *
 * @param   $args['id']     int 
 * @return output;
 */
function Communicator_ajax_sendReceipt()
{
    // Get parameters
    $id = (int) FormUtil::getPassedValue('id');

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    // get message and let API send the receipt
    $message = pnModAPIFunc('Communicator','user','get',array('id' => $id, 'receipt' => 1));
    if (!$message || (!($message['id'] == $id))) {
        $output = __('Notice of receipt could not be send!', $dom);
    } else {
        $output = 'ok';
    }
    print $output;
    return true;
}

/**
 * forward an email in user's real email box
 *
 * @param   $args['id']     int
 * @return output;
 */
function Communicator_ajax_forwardAsMail()
{
    // Get parameters
    $id = (int) FormUtil::getPassedValue('id');

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    // get message and let API send the receipt
    $forwardAction = pnModAPIFunc('Communicator','user','sendMailAtHome',array('id' => $id));
    if (!$forwardAction) {
        $output = __('Mail could not be read for forwarding!', $dom);
    } else {
        $output = __('Mail was forwarded to your mailbox:', $dom).' '.$forwardAction;
    }
    print $output;
    return true;
}