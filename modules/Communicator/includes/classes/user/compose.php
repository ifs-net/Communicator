<?php
/**
 * @package      Communicator
 * @version      $Id$
 * @author       Florian Schie�l
 * @link         http://www.ifs-net.de
 * @copyright    Copyright (C) 20010
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class communicator_user_compose_handler
{
    var $user;
    var $reference;
    var $action;
    var $bbsmiles;
    var $hooks;
    function initialize(&$render)
    {
        // Language Domain
        $dom = ZLanguage::getModuleDomain('Communicator');

        // Some default variables
        $this->user['id'] = pnUserGetVar('uid');
        $this->hooks['bbcode']   = (pnModAvailable('bbcode')   && pnModIsHooked('bbcode', 'Communicator'));
        $this->hooks['bbsmile']  = (pnModAvailable('bbsmile')  && pnModIsHooked('bbsmile','Communicator'));
        
        // Assign some default values to template
        $tpl_vars = $this->hooks;
        // priority
        $tpl_vars['items_priority'] = array (
//                array('text' => __('low priority', dom),    'value' => 1),
                array('text' => __('normal priority',$dom), 'value' => 2),
                array('text' => __('high priority',$dom),   'value' => 3)
            );
        $tpl_vars['priority'] = 2;  // priority default value
        
        // integrate quota function
        $usercount = pnModAPIFunc('Communicator','user','count');
        $userquota = pnModGetVar('Communicator','quota');
        if (($userquota > 0) && ($usercount > $userquota)) {
            LogUtil::registerError(__("You have too many mail stored in your inbox, sent and maybe custom user folders. There is a maximum of $userquota mails you are allowed to have in your folders - actually you have $usercount mails stored. Please delete old mails before you try to compsoe a new mail. If this error does not disappear even after you have deleted some mails please reload this page and try again!",$dom));
        }
        
        // contactlist integration
        $obj = $render->pnFormGetValues();
        if (pnModAvailable('ContactList')) {
            $buddies = pnModAPIFunc('ContactList','user','getall',array('state' => 1, 'uid' => $this->user['id'], 'sort' => 'uname'));
            $buddylist = array();
            foreach ($buddies as $buddy) {
                $buddy['formID'] = "X".$buddy['bid']."";
                $buddylist[] = $buddy;
                if ($obj[$buddy['formID']] > 0) {
                    $tpl_vars[$buddy['formID']] = 1;
                    $tpl_vars['openBuddyList'] = 1;
                }
            }
            if (count($buddylist) > 0) {
                $tpl_vars['buddylist'] = $buddylist;
            }
        }
        
        // Should this be a reply?
        $includeID = (int) FormUtil::getPassedValue('include');
        if ($includeID > 0) {
            $includeMail = pnModAPIFunc('Communicator','user','get',array('id' => $includeID));
            if (($includeMail['id'] == $includeID) && ($includeMail['uid'] == $this->user['id'])) {
                // Permission check passed  - Add text now to template
                if ($this->hooks['bbcode']) {
                    $includeMail['body'] = "\n\n[quote=".$includeMail['from_name']." ".__('wrote at',$dom)." ".$includeMail['date']."]".$includeMail['body']."[/quote]\n\n";
                } else {
                    $includeMail['body'] = $includeMail['from_name']." ".__('wrote at',$dom)." ".$includeMail['date'].":\n>\n>".str_replace("\n","\n> ",$includeMail['body']);
                }
                $tpl_vars['mailbody'] = $includeMail['body'];
                $action = FormUtil::getPassedValue('action');
                if ($action == 'reply') {
                    $re = __('Re:',$dom);
                    $tpl_vars['recipients']= pnUserGetVar('uname',$includeMail['from']);
                    if (!preg_match('/^'.$re.'/',$includeMail['subject'])) {
                        $tpl_vars['subject']   = $re." ".$includeMail['subject'];
                    } else {
                        $tpl_vars['subject']   = $includeMail['subject'];
                    }
                    $this->reference = $includeMail;
                    $this->action = 'reply';
                } else if ($action == 'forward') {
                    $fwd = __('Fwd:',$dom);
                    if (!preg_match('/^'.$fwd.'/',$includeMail['subject'])) {
                        $tpl_vars['subject']   = $fwd." ".$includeMail['subject'];
                    } else {
                        $tpl_vars['subject']   = $includeMail['subject'];
                    }
                    $this->reference = $includeMail;
                    $this->action = 'forward';
                }
            } else {
                    LogUtil::registerError(__('You have no permission to include the specified message id',$dom));
            }
        } else {
            // Add recipients via $_GET
            $uid = (int)FormUtil::getPassedValue('uid');
            if ($uid > 1) {
                $uname = pnUserGetVar('uname',$uid);
                if (isset($uname) && ($uname != '')) {
                    $tpl_vars['recipients'] = $uname;
                }
            }
        }
        // Add variables to templates
        $render->assign($tpl_vars);
		return true;
    }

    function handleCommand(&$render, &$args)
    {
        // Language Domain
        $dom = ZLanguage::getModuleDomain('Communicator');
        $render->assign('send', 1);
		if ($args['commandName']=='preview') {
		    $obj = $render->pnFormGetValues();
		    $obj['body'] = $obj['mailbody'];
		    $obj['id'] = 999; // fake id for template
		    $obj['to_name'] = $obj['recipients'];
		    $obj['from_name'] = pnUserGetVar('uname');
		    $obj['date'] = date("Y-m-d H:i:s",time());
		    $obj['composemode'] = 1;
		    $render->assign('message', $obj);
		    $render->assign('preview', 1);
		    $render->assign('printer_view', 1);
		} else if ($args['commandName']=='cancel') {
		    return $render->pnFormRedirect(pnModURL('Communicator'));
		} else if ($args['commandName']=='send') {
		    $obj = $render->pnFormGetValues();
		    $message['composemode'] = 1;
		    $render->assign('message',$message);
		    $error = false;
		    // validate message body
		    if (!isset($obj['mailbody']) || ($obj['mailbody'] == '')) {
		      LogUtil::registerError(__('Message body is mandatory',$dom));
		      $error = true;
            }
            // validate and get recipients
            $to = array();
            $toContactList = 0;
            // get recipients from buddylist
            foreach ($obj as $key=>$value) {
                $checked = (int)$value;
                if (($key[0] == 'X') && ($value == 1)) {
                    $dummy = explode('X',$key);
                    $uid = $dummy[1];
                    if (pnUserGetVar('uname',$uid) != '')  {
                        $to[$uid] = $uid;
                        $toContactList++;
                    }
                }
            }
            // get normal recipients
            if (trim($obj['recipients']) != '') {
                $recipients = explode(',',$obj['recipients']);                
            }
            $toRegular = 0;
            // get regular users
            $ContactListAvailable = pnModAvailable('ContactList');
            foreach ($recipients as $recipient) {
                $recipient = trim($recipient);
                $uid = pnModAPIFunc('Communicator','user','getIDFromUname',array('uname' => $recipient));
                if ($uid > 1) {
                    // Check ignorelist settings
                    if ($ContactListAvailable && (pnModAPIFunc('ContactList','user','isIgnored',array('uid' => $uid, 'iuid' =>$this->user['id'])) == true)) {
                        // Sending user is ignored by recipient - don't send!
                        LogUtil::registerError(__('You cannot send mails to the following user because this user does not want to recieve emails from you:',$dom).' '.pnUserGetVar('uname',$uid));
                    } else {
                        // Send mail (later)
                        if ($to[$uid] != $uid) {    // otherwise user already added via contactlist
                            $to[$uid] = $uid;
                            $toRegular++;
                        }
                    }
                } else {
                    LogUtil::registerError(__('Please check recipients: user not found:'.' '.$recipient,$dom));
                    $error = true;
                }
            }
            // SPAM protection
            $spamCheck = pnModAPIFunc('Communicator','user','spamCheck',array('uid' => $this->user['id'], 'to' => $to));
            if (!$spamCheck) {
                $spam_allowed_time = pnModGetVar('Communicator', 'spam_allowed_time');
                $spam_allow_max    = pnModGetVar('Communicator', 'spam_allow_max');
                LogUtil::registerError(__("You cannot sent so many mails - spam guard does not allow you to send more than $spam_allow_max messages in $spam_allowed_time minutes to other users that are not confirmed contacts.",$dom));
                $error = true;
            }
            
            // integrate quota function
            $usercount = pnModAPIFunc('Communicator','user','count');
            $userquota = pnModGetVar('Communicator','quota');
            if (($userquota > 0) && ($usercount > $userquota)) {
                LogUtil::registerError(__("You have too many mail stored in your inbox, sent and maybe custom user folders. There is a maximum of $userquota mails you are allowed to have in your folders - actually you have $usercount mails stored. Please delete old mails before you try to compsoe a new mail. If this error does not disappear even after you have deleted some mails please reload this page and try again!",$dom));
                $error = true;
            }

            // send message
            $args = array(
                'from'      => pnUserGetVar('uid'),
                'to'        => $to,
                'subject'   => $obj['subject'],
                'body'      => $obj['mailbody'],
                'priority'  => $obj['priority'],
                'receipt'   => $obj['receipt']
            );
            if (!$error) {
                $sendAction = pnModAPIFunc('Communicator','user','send',$args);
                if (!$sendAction) {
                    LogUtil::registerError(__('An error occured while trying to send the message. Please try again and contact the webmaster if sending fails again.',$dom));
                } else {
                    $to_unames = array();
                    foreach ($to as $to_uid) {
                        $to_unames[] = pnUserGetVar('uname',$to_uid);
                    }
                    $to_unames = implode(', ',$to_unames).'.';
                    LogUtil::registerStatus(__('Message sent succesfully to ',$dom).' '.$to_unames);
                    
                    // register new status for mail
                    if ($this->reference['id'] > 0) {
                        $mail_header = DBUtil::selectObjectByID('communicator_mail_header',$this->reference['id']);
                        if ($this->action == 'reply') {
                            $mail_header['replied'] = 1;
                        } else if ($this->action == 'forward') {
                            $mail_header['forwarded'] = 1;
                        }
                        DBUtil::updateObject($mail_header,'communicator_mail_header');
                    }
                    
                    $render->pnFormRedirect(pnModURL('Communicator'));
                    return true;
                }
            }
		}
		return false;
    }
}