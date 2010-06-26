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
 * get message
 *
 * @param   $args['id']             int     message ID
 * @param   $args['read']           int     mark as read
 * @param   $args['flag']           int     mark as flagged
 * @param   $args['receipt']        int     send receipt (if needed)
 *
 * @return  array
 */
function Communicator_userapi_get($args)
{
    // get Parameters
    $id          = (int) $args['id'];
    $read        = (int) $args['read'];
    $deflag      = (int) $args['deflag'];
    $flag        = (int) $args['flag'];
    $sendreceipt = (int) $args['receipt'];
    
    // get table information
    $tables = pnDBGetTables();
    $tbl_header = $tables['communicator_mail_header_column'];
    $tbl_body   = $tables['communicator_mail_body_column'];
    $tbl_folder = $tables['communicator_folder_column'];

    // Join information    
    $joinInfo = array();
	$joinInfo[] = array (	'join_table'          =>  'communicator_mail_body',
							'join_field'          =>  array('date','subject','body'),
                         	'object_field_name'   =>  array('date','subject','body'),
                         	'compare_field_table' =>  'mid',
                         	'compare_field_join'  =>  'id');
	$joinInfo[] = array (	'join_table'          =>  'communicator_folders',
							'join_field'          =>  array('title'),
                         	'object_field_name'   =>  array('folder_title'),
                         	'compare_field_table' =>  'mid',
                         	'compare_field_join'  =>  'id');

    $message = DBUtil::selectExpandedObjectByID('communicator_mail_header',$joinInfo,$id);
    if (!$message) {
        return false;
    } else {
    	// Security check 
    	if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_ADMIN) && ($message['uid'] != pnUserGetVar('uid'))) {
            return LogUtil::registerPermissionError();
        }
      
    }
    // Mark as read or flag message
    if (
        (($read == 1) && ($message['read'] == 0))
        ||
        (($deflag == 1) || ($flag == 1))
        ||
        ($sendreceipt == 1)
        ) {
        $header = DBUtil::selectObjectByID('communicator_mail_header',$id);
        $header['read'] = $read;
        // flag or mark?
        if ($flag == 1) {
            $header['flagged'] = 1;
            $message['flagged'] = 1;
        } else if ($deflag == 1) {
            $header['flagged'] = 0;
            $message['flagged'] = 0;
        }
        // return receipt?
        if (($sendreceipt == 1) && ($message['receipt'] == 1) && ($message['receipt_sent'] == 0)) {
            $message['receipt_sent'] = 1;
            $header['receipt_sent'] = 1;
            // now we need mail_body for subject
            pnModAPIFunc('Communicator','user','sendReceipt',$message);
        }
        DBUtil::updateObject($header,'communicator_mail_header');
        // put changed values also into message to avoid double loading
        $message['read'] = $read;
    }
    return $message;
}

/**
 * get messages
 *
 * @param   $args['uid']        int     user ID
 * @param   $args['header_ob1'] string  order by
 * @param   $args['header_ob2'] string  ASC or DESC
 * @param   $args['header_grp'] int     group messages
 * @param   $args['folder']     int     folder id (0 = inbox, -1 = outbox, folder-id otherwise)
 *
 * @return  array
 */
function Communicator_userapi_getAll($args)
{
    // get parameters
    $uid        = (int)    $args['uid'];
    $header_grp = (int)    $args['header_grp'];
    $header_ob1 = (string) $args['header_ob1'];
    $header_ob2 = (string) $args['header_ob2'];
    $folder     = (int)    FormUtil::getPassedValue('folder');
    $sort       = (string) $args['sort'];
    $mode       = (string) $args['mode'];
   
    // validate parameters
    if (!($uid > 1)) {
        return false;
    }
    
    // get table information
    $tables = pnDBGetTables();
    $tbl_header = $tables['communicator_mail_header_column'];
    $tbl_body   = $tables['communicator_mail_body_column'];
    $tbl_folder = $tables['communicator_folder_column'];

    // Join information    
    $joinInfo = array();
	$joinInfo[] = array (	'join_table'          =>  'communicator_mail_body',
							'join_field'          =>  array('date','subject','body'),
                         	'object_field_name'   =>  array('date','subject','body'),
                         	'compare_field_table' =>  'mid',
                         	'compare_field_join'  =>  'id');
	$joinInfo[] = array (	'join_table'          =>  'communicator_folders',
							'join_field'          =>  array('title'),
                         	'object_field_name'   =>  array('folder_title'),
                         	'compare_field_table' =>  'mid',
                         	'compare_field_join'  =>  'id');

    // SQL select restrictions
    $whereArray = array();
    // Get specified folder only and restrict to uid
    if ($folder == -1) {
        $whereArray[] = 'tbl.'.$tbl_header['folder'].' = -1';
        $whereArray[] = 'tbl.'.$tbl_header['from'].' = '.$uid;
    } else {
        $whereArray[] = 'tbl.'.$tbl_header['folder'].' = '.(int)$folder;
        $whereArray[] = 'tbl.'.$tbl_header['to'].' = '.$uid;
    }
    $where = implode(' AND ',$whereArray);
    // order result
    if ($header_ob1 == 'subject'){
        $header_ob1 = 'a.'.$tbl_body['subject'];
    } else if ($header_ob1 == 'from_name'){
        $header_ob1 = 'tbl.'.$tbl_header['from_name'];
    } else if ($header_ob1 == 'to_name'){
        $header_ob1 = 'tbl.'.$tbl_header['to_name'];
    } else {
        $header_ob1 = 'a.'.$tbl_body['date'];
    }
    if (strtolower($header_ob2) != 'asc') {
        $header_ob2 = 'desc';
    }
    $orderby = $header_ob1.' '.$header_ob2;
    $messages = DBUtil::selectExpandedObjectArray('communicator_mail_header',$joinInfo,$where,$orderby);

    // Return messages
    return $messages;
}

/**
 * count  messages
 *
 * @param   $args['uid']    int     optional
 * @return  array
 */
function Communicator_userapi_count($args)
{
    // get Parameters
    $uid = (int) $args['uid'];
    if (!pnUserLoggedIn()) {
        return false;
    }
    if (!($uid > 0)) {
        $uid = pnUserGetVar('uid');
    }
    
    // get table information
    $tables = pnDBGetTables();
    $tbl_header = $tables['communicator_mail_header_column'];

    // Count messages
    $where = $tbl_header['to']." = ".$uid;
    $messages = DBUtil::selectObjectCount('communicator_mail_header',$where);
    return $messages;
}

/**
 * delete a message
 *
 * @param   $args['id']         int     message id
 *
 * @return  boolean
 */
function Communicator_userapi_del($args)
{
    // Get Parameter
    $id = (int) $args['id'];
    
    // We just need to delete the header - mail body will automatically be 
    // deleted by the cargabe collector that runs with system init hook
    $header = DBUtil::selectObjectByID('communicator_mail_header',$id);
    // ToDo: Securitycheck

    // delete header    
    $deleteAction = DBUtil::deleteObject($header,'communicator_mail_header');

    // return result
    return $deleteAction;  
}

/**
 * send notice of receipt to a message sender
 *
 * @param   $args['subject']        string
 * @param   $args['from']           int
 * @param   $args['to']             int
 * @retrurn boolean
 */
function Communicator_userapi_sendReceipt($args)
{
    // Get Parameters
    $obj['to']    = (int)    $args['from'];
    $obj['from']  = (int)    $args['to'];
    $subject      = (string) $args['subject'];

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    // create and send receipt    
    $render = pnRender::getInstance('Communicator');
    $render->assign('subject',   $subject);
    $render->assign('dateformat',pnModGetVar('Communicator','dateformat'));
    $render->assign('date_now', date("Y-m-d H:i:s",time()));
    $obj['body'] = $render->fetch('communicator_email_receipt.htm');
    $obj['systemmail'] = 1;
    $obj['subject'] = __('Notice of receipt',$dom);
    $sendAction = pnModAPIFunc('Communicator','user','send',$obj);
    return $sendAction;
}

/**
 * send message function
 *
 * This function sends a message to all given recipients and puts
 * the message into the Sent folder of the sending user
 * 
 * @param   $args['from']       int     user ID
 * @param   $args['to']         array   user IDs
 * @param   $args['subject']    string  messag subject
 * @param   $args['body']       string  message body
 * @param   $args['priority']   int     message priority
 * @param   $args['receipt']    int     return receipt reqeusted
 * @param   $args['systemmail'] int     systemmails will not be autoresponded
 *
 * @return  boolean
 */
function Communicator_userapi_send($args)
{
    // get parameters
    $from       = (int)    $args['from'];
    $to         = (array)  $args['to'];
    $subject    = (string) $args['subject'];
    $body       = (string) $args['body'];
    $priority   = (int)    $args['priority'];
    $receipt    = (int)    $args['receipt'];
    $systemmail = (int)    $args['systemmail'];

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    // validate parameters
    if (!($from > 0)) {
        return false;      
    }
    if ($subject == '') {
        $subject = '('.__('no subject',$dom).')';
    }
    if (!(count($to) > 0)) {
        return false;
    }
    if ($body == '') {
        return false;
    }
    if (($priority < 1) || ($priority > 3)) {
        $priority = 2;
    }
    if (($receipt != 0) && ($receipt != 1)) {
        $receipt = 0;
    }

    // store
    $failure = false;
    // store message body just for one time - other mails will reference the same text
    $mail_body = array(
        'subject'   => $subject,
        'body'      => $body,
        'date'      => date("Y-m-d H:i:s")
    );
    $insertBodyAction = DBUtil::insertObject($mail_body,'communicator_mail_body');
    if (!$insertBodyAction || (!$insertBodyAction['id'] > 0)) {
        return LogUtil::registerError(__('Mail sending failed - mail body could not be stored.',$dom));
    } else {
        $id = $insertBodyAction['id'];
        $from_uname = pnUserGetVar('uname',$from);
    }
    $to_unames = array();
    $to_uids   = array();
    foreach ($to as $recipient) {
        // store mesages that refer to mail body
        $to_uname = pnUserGetVar('uname',$recipient);
        $mail_header = array(
            'uid'        => $recipient,
            'from'       => $from,
            'from_name'  => $from_uname,
            'to'         => $recipient,
            'to_name'    => $to_uname,
            'mid'        => $id,
            'priority'   => $priority,
            'receipt'    => $receipt,
            'systemmail' => $systemmail
        );
        $insertMailAction = DBUtil::insertObject($mail_header,'communicator_mail_header');
        if (!$insertMailAction) {
            LogUtil::registerError(__('Mail sending failed for user',$dom).' '.$to_name);
            $failure = true;
        } else {
            $to_unames[] = $to_uname;
            $to_uids[]   = $recipient;
        }
    }
    // Store in sent folder        
    $to_unames_string = implode(', ',$to_unames);
    $mail_header = array(
        'uid'       => $from,
        'from'      => $from,
        'from_name' => $from_uname,
        'to'        => $recipient,
        'to_name'   => $to_unames_string,
        'mid'       => $id,
        'priority'  => $priority,
        'read'      => 1,
        'folder'    => -1
    );
    $insertMailAction = DBUtil::insertObject($mail_header,'communicator_mail_header');
    if (!$insertMailAction) {
        LogUtil::registerError(__('Mail sending failed for user',$dom).' '.$to_name);
        $failure = true;
    }
    
    // Send notifications or send autoresponder messages if needed
    foreach ($to_uids as $to) {
        $communicator_disableNotification = (int) pnUserGetVar('communicator_disableNotification', $to);
        $communicator_enableAutoresponder = (int) pnUserGetVar('communicator_enableAutoresponder', $to);
        if ($communicator_enableAutoresponder == 1) {
            $communicator_autoresponder_text = pnUserGetVar('communicator_autoresponder_text',$to);
        }
        // send notification message
        if ($communicator_disableNotification != 1) {
            // Generate rendering object
            $render = pnRender::getInstance('Communicator');
            // get message info
            $messageinfo = pnModAPIFunc('Communicator','user','getmessagecount',array('uid' => $to));
            // fake last header
            $mail_header['to'] = $to;
            $mail_header['to_name'] = pnUserGetVar('uname',$to);
            // Assign to template
            $render->assign('mail_body',    $mail_body);
            $render->assign('mail_header',  $mail_header);
            $render->assign('messageinfo',  $messageinfo);
            // get content for email
            $content = $render->fetch('communicator_email_notification.htm');
            // send mail
            $args = array (
                'toname'    => $mail_header['to_name'],
                'toaddress' => pnUserGetVar('email',$mail_header['to']),
                'subject'   => __('A private message is waiting for you!',$dom),
                'body'      => $content
            );
            $result = pnModAPIFunc('Mailer','user','sendmessage',$args);
            if (!$result) {
                LogUtil::registerError(__('Sending email notification failed!',$dom));
            }
        }
        // send autoresponder
        if (($communicator_enableAutoresponder == 1) && ($communicator_autoresponder_text != '') && ($systemmail != 1)) {
            $args['from']       = $to;
            $args['to']         = $mail_header['from'];
            $args['subject']    = __('Autoresponse',$dom).': '.$mail_body['subject'];
            $args['body']       = $communicator_autoresponder_text;
            $args['systemmail'] = 1;
            $result = pnModAPIFunc('Communicator','user','send',$args);
            if (!$result) {
                LogUtil::registerError(__('Sending autoresponse failed!',$dom));
            }
        }
    }    
    return (!$failure);
}

/**
 * helper function to get user id from user name - zikula core
 * function (case sensitive because datafield for username is 
 * uft8_bin) doesn't always work well...
 *
 * @param   uname   string
 * @return  int
 */
function Communicator_userapi_getIDFromUname($args)
{
    $uname = (string)$args['uname'];
    $tables = pnDBGetTables();
    $column = $tables['users_column'];
    $where_cs = $column['uname']." = '".$uname."'";
    $where_ci = "lower(".$column['uname'].") LIKE '".strtolower($uname)."'";
    $res_cs = DBUtil::selectObjectArray('users',$where_cs);
    if (!$res_cs || (count($res_cs) == 0)) {
        // now use case insensitive where...
        $res_ci = DBUtil::selectObjectArray('users',$where_ci);
        if (!$res_ci || (count($res_ci) == 0)) {
            return false;
        } else {
            return $res_ci[0]['uid'];
        }
    } else {
        return $res_cs[0]['uid'];
    }
}

/**
 * store user settings
 *
 * @param   $args['settings']    array
 *
 * @return  boolean
 */
function Communicator_userapi_storeSettings($args)
{
    // get User object from DB
    $settings['communicator_disableNotification'] = (int)    $args['disableNotification'];
    $settings['communicator_enableAutoresponder'] = (int)    $args['enableAutoresponder'];
    $settings['communicator_autoresponder_text']  = (string) $args['autoresponder_text'];
    $uid                             = (int)    $args['uid'];
    
    if (!($uid > 1)) {
        if (!pnUserLoggedIn()) {
            return false;
        } else {
            $uid = pnUserGetVar('uid');          
        }
    }
    // store as uservariables (pnAPI will store them as user attribute)
    foreach ($settings as $key=>$value) {
        pnUserSetVar($key,$value,$uid);
    }
    return true;
}

/**
 * manage custom folders
 *
 * @param   $args['folder']     string
 * @param   $args['uid']        int     optional
 *
 * @return  boolean;
 */
function Communicator_userapi_createFolder($args)
{
    $folder = (string) $args['folder'];
    $uid    = (int)    $args['uid'];
    if (!($uid > 1)) {
        if (pnUserLoggedIn()) {
            $uid = pnUserGetVar('uid');
        } else {
            return false;
        }
    }
    
    if ($folder == '') {
      return false;
    }
    
    $obj = array(
        'title' => $folder,
        'uid'   => $uid
    );
    $insertAction = DBUtil::insertObject($obj,'communicator_folders');
    return $insertAction;
}

/**
 * manage custom folders
 *
 * @param   $args['uid']        int     optional
 *
 * @return  boolean;
 */
function Communicator_userapi_getFolders($args)
{
    $uid    = (int)    $args['uid'];
    if (!($uid > 1)) {
        if (pnUserLoggedIn()) {
            $uid = pnUserGetVar('uid');
        } else {
            return false;
        }
    }
    $tables  = pnDBGetTables();
    $column  = $tables['communicator_folders_column'];
    $where   = $column['uid'].' = '.$uid;
    $orderby = $column['title'].' ASC';
    $folders = DBUtil::selectObjectArray('communicator_folders',$where,$orderby);
    return $folders;
}

/**
 * delete a folder
 *
 * @param   $args['id']     int     folder id
 * @param   $args['uid']    int     user id     optional
 *
 * @return boolesn
 */
function Communicator_userapi_delFolder($args)
{
    $id  = (int) $args['id'];
    $uid = (int) $args['uid'];
    if (!($uid > 0)) {
        if (pnUserLoggedIn()) {
            $uid = pnUserGetVar('uid');
        } else {
            return false;
        }
    }
    $folder = DBUtil::selectObjectByID('communicator_folders',$id);
    if (!$folder || ($folder['uid'] != $uid)) {
        return false;
    } else {
        $deleteAction = DBUtil::deleteObject($folder,'communicator_folders');
        if ($deleteAction) {
            // move all mails from this folder to inbox
            $tables = pnDBGetTables();
            $column = $tables['communicator_mail_header_column'];
            $sql    = "UPDATE ".$tables['communicator_mail_header']." SET ".$column['folder']." = 0 WHERE ".$column['folder']." = ".$id;
            $moveAction = DBUtil::executeSQL($sql);
            return $moveAction;
        }
        return $deleteAction;
    }
}

/**
 * rename a folder
 *
 * @param   $args['id']     int     folder id
 * @param   $args['uid']    int     user id     optional
 * @param   $args['title']  string  new title
 *
 * @return boolesn
 */
function Communicator_userapi_renameFolder($args)
{
    $id    = (int)    $args['id'];
    $uid   = (int)    $args['uid'];
    $title = (string) $args['title'];
    $folder = DBUtil::selectObjectByID('communicator_folders',$id);
    if (!$folder || ($folder['uid'] == $uid) || ($title == '')) {
        return false;
    } else {
        $folder['title'] = $title;
        $updateAction = DBUtil::updateObject($folder,'communicator_folders');
        return $updateAction;
    }
}

/**
 * Move message to another folder
 *
 * @param   $args['message_id']     int     message id
 * @param   $args['folder_id']      int     folder id
 * 
 * @return  boolean
 */
function Communicator_userapi_moveMessageToFolder($args)
{
    // Parameters
    $mid = (int) $args['message_id'];
    $fid = $args['folder_id'];
    $uid = pnUserGetVar('uid');
    
    // Get message header
    $header = DBUtil::selectObjectByID('communicator_mail_header',$mid);
    if (!$header || ($header['uid'] != $uid)) {
        return false;
    } else {
        if (($fid == -1) || ($fid == 0)) {
            // Make fake folder for inbox and sent folder!
            $folder = array('uid' => $uid, 'id' => $fid);
        } else {
            $folder = DBUtil::selectObjectByID('communicator_folders',$fid);
        }
        if (!$folder || ($folder['uid'] != $uid)) {
            return false;
        } else {
            $header['folder'] = $folder['id'];
            $updateAction = DBUtil::updateObject($header,'communicator_mail_header');
            return $updateAction;
        }
    }
    
}

/**
 * get spam points
 * 
 * @args    $param['uid']   int     user id
 * @return  int
 */
function Communicator_userapi_spamCheck($args)
{
    // Get Parameters
    $uid = (int) $args['uid'];
    $to  = $args['to'];
    if (!pnUserLoggedIn()) {
        return false;
    } else if (!($uid > 1)) {
        $uid = pnUserGetVar('uid');
    }

    // Get Module Vars
    $spam_allowed_time = (int) pnModGetVar('Communicator', 'spam_allowed_time');
    $spam_allow_max    = (int) pnModGetVar('Communicator', 'spam_allow_max');
    
    if (($spam_allowed_time == 0) || ($spam_allow_max == 0)) {
        return true;    // No Spam Check configured
    }

	$joinInfo[] = array (	'join_table'          =>  'communicator_mail_body',
							'join_field'          =>  'date',
                         	'object_field_name'   =>  'date',
                         	'compare_field_table' =>  'mid',
                         	'compare_field_join'  =>  'id');
    
    $tables = pnDBGetTables();
    $header_column = $tables['communicator_mail_header_column'];
    $body_column   = $tables['communicator_mail_body_column'];
    $where = array();
    $where[] = $header_column['uid']." != ".$uid." AND ".$header_column['from']." = ".$uid;    
    // get contacts
    if (pnModAvailable('ContactList')) {
        $contacts = pnModAPIFunc('ContactList','user','getall',array('state' => 1, 'uid' => $uid));
        $buddies = array();
        foreach ($contacts as $buddy) {
            $buddies[] = $buddy['bid'];
        }
        if (count($buddies) > 0) {
            $where[] = $header_column['to']." NOT IN (".implode(',',$buddies).")";
        }
    }
    // Get valid time for spam protection
    $timestamp = date("Y-m-d H:i:s",time()-($spam_allowed_time*60));
    $where[] = $body_column['date']." > '".$timestamp."'";

    // Count sent mails now
    $whereString = implode(' AND ',$where);
    $mailsCounter = $spam_allow_max - (int) DBUtil::selectExpandedObjectCount('communicator_mail_header',$joinInfo,$whereString);
//    $mailsCounter = DBUtil::selectObjectCount('communicator_mail_header',$whereString);

    // Now remove all recipients that are no buddies from counter
    foreach ($to as $recipient) {
        if (in_array($recipient,$buddies)) {
            $mailsCounter++;
        }
    } 

//    LogUtil::registerStatus($mailsCounter);return false;
    return ($mailsCounter >= 0);
}

/**
 * forward mail in user's real email box
 *
 * @param   $args['id']         int         user-id
 * @return  output          user's email address or false otherwise
 */
function Communicator_userapi_sendMailAtHome($args)
{
    // Get parameters
    $id = (int) $args['id'];

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');

    // get message
    $message = pnModAPIFunc('Communicator','user','get',array('id' => $id));
    if (!$message || (!($message['id'] == $id))) {
        return false;
    } else {
        // Language Domain
        $dom = ZLanguage::getModuleDomain('Communicator');
        // Send as HTML mail
        $to = pnUserGetVar('email',$message['uid']);
        $body = pnModAPIFunc('Communicator','output','showMessage',array('message' => $message, 'printer_view' => 1));
        $params = array (
            'toname'    => pnUserGetVar('uname',$message['uid']),
            'toaddress' => $to,
            'html'      => true,
            'body'      => $body,
            'subject'   => __('Forwarded Mail', $dom).': '.$message['subject']
        );
        $sendAction = pnModAPIFunc('Mailer','user','sendmessage',$params);
        if (!$sendAction) {
            return false;
        } else {
            return $to;
        }
    }
}

/**
 * systeminit routine
 */
function Communicator_userapi_systeminit()
{
    // get last checkup
    $lastCheckUp   = pnModGetVar('Communicator','lastCheckUp');
    $noAutoCleanUp = (int) pnModGetVar('Communicator','no_auto_cleanup');
    
    if (($lastCheckUp != date('Y-m-d',time())) && ($noAutoCleanUp != 1)) {
 
        // set last checkup timestamp - next time tomorrow...
        pnModSetVar('Communicator','lastCheckUp',date('Y-m-d',time()));
 
        // get all messages that do not belong to a user any more
        $tables = pnDBGetTables();
        $header_column = $tables['communicator_mail_header_column'];
        $body_column   = $tables['communicator_mail_header_column'];
        $folders_column= $tables['communicator_folders_column'];
        $users_column  = $tables['users_column'];
 
        // delete old headers
        $deleteWhere   = $header_column['uid'].' NOT IN (SELECT '.$users_column['uid'].' FROM '.DBUtil::getLimitedTablename('users').')';
        $deleteAction  = DBUtil::deleteWhere('communicator_mail_header',$deleteWhere);
 
        // now delete all mails that do not belong to a header any more
        $deleteWhere   = $body_column['id'].' NOT IN (SELECT '.$header_column['mid'].' FROM '.DBUtil::getLimitedTablename('communicator_mail_header').')';
        $deleteAction  = DBUtil::deleteWhere('communicator_mail_body',$deleteWhere);
        
        // now delete folders that belong to users that are not existing any more
        $deleteWhere   = $folders_column['uid'].' NOT IN (SELECT '.$users_column['uid'].' FROM '.DBUtil::getLimitedTablename('users').')';
        $deleteAction  = DBUtil::deleteWhere('communicator_folders',$deleteWhere);
    }
}

/**
 * Popup if there are new mails arrived
 *
 * @return output
 */
function Communicator_userapi_popup()
{
    return pnModAPIFunc('Communicator','output','popup');
}

/** 
 * This function returns the amount of Messages within the inbox that are unread
 * 
 * @author Florian Schießl and Sven Strickroth
 *
 * @param   $args['uid']        int     user-id (optional)
 * @param   $args['folder']     int     folder-id
 * @param   $args['popup']      boolean optional
 * @return  array               array['unread'] containd number of unread mails.
 */ 
function Communicator_userapi_getMessageCount($args) 
{ 
    // Security check 
    if (!SecurityUtil::checkPermission('Communicator::', '::', ACCESS_READ)) { 
        return LogUtil::registerPermissionError();; 
    } 
    if (!pnUserLoggedIn()) { 
        return null; 
    } 

    $uid = (int)$args['uid'];
    if (!($uid > 1)) {
        $uid = pnUserGetVar('uid');
    }

    // get table information 
    $tables = pnDBGetTables(); 
    $tbl_header = $tables['communicator_mail_header_column']; 

    // Count messages 
    $where_unread_all = $tbl_header['to']." = ".$uid." and ".$tbl_header['read']." = 0"; 

    // form a variable to return 
    $returnArray = array(); 
    $returnArray['unread'] = DBUtil::selectObjectCount('communicator_mail_header',$where_unread_all); 

    // Return the variable 
    return $returnArray; 
}

