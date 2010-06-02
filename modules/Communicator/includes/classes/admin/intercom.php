<?php
/**
 * @package      Communicator
 * @version      $Id: settings.php 4 2010-05-26 16:29:35Z quan $
 * @author       Florian Schießl
 * @link         http://www.ifs-net.de
 * @copyright    Copyright (C) 20010
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class communicator_admin_intercom_handler
{
    var $counter;
    function initialize(&$render)
    {
        // Language Domain
        $dom = ZLanguage::getModuleDomain('Communicator');

        if (pnModAvailable('InterCom')) {
            // Register starting Variable
            $counter = (int) pnModGetVar('Communicator','intercom');
            if (FormUtil::getPassedValue('reset') == 1) {
                $counter = 0;
                pnModDelVar('Communicator','intercom');
            }
            $render->assign('c_counter', $counter);
            // Get number of tasks per request
            $interval = (int)pnModGetVar('Communicator','intercom_interval');
            if (!($interval > 1)) {
                $interval = 2000;
            }
            $render->assign('interval', $interval);
        } else {
            LogUtil::registerError(__('InterCom module not found!',$dom));
            return $render->pnFormRedirect(pnModURL('Communicator','admin'));
        }
        
        // Add variables to template
        $render->assign($tpl_vars);
        $this->counter = $counter;
		return true;
    }

    function handleCommand(&$render, &$args)
    {
        // Language Domain
        $dom = ZLanguage::getModuleDomain('Communicator');
		if ($args['commandName']=='reset') {
		    pnModDelVar('Communicator','intercom');
		    // Register Status
		    LogUtil::registerStatus(__('Reset done!',$dom));
		} else if ($args['commandName']=='import') {
		    $obj = $render->pnFormGetValues();
		    $counter = $this->counter;
            $internalcounter = 0;
		    $interval = (int) $obj['interval'];
		    if (!($interval > 0)) {
                $interval = 2000;
            }
            pnModSetVar('Communicator','intercom_interval',$interval);
            // Load DB
            pnModDBInfoLoad('InterCom');
            $tables = pnDBGetTables();
            $table  = $tables['intercom'];
            $column = $tables['intercom_column'];
            // SQL Query
            // get next 2000 mails
            $orderby = $column['msg_id']." ASC";
            $numrows = $interval;
            $where = $column['msg_id']." > ".$counter;
            $ic_messages = DBUtil::selectObjectArray('intercom',$where,$orderby,-1,$numrows);
            if (count($ic_messages) == 0) {
                LogUtil::registerStatus(__('Import done! No InterCom mails left for import!',$dom));
                return $render->pnFormRedirect(pnModURL('Communicator','admin','intercom'));
            }
//            prayer($ic_messages);die();
            foreach ($ic_messages as $ic) {
                // build text
                $new_body = array (
                    'subject'   => $ic['msg_subject'],
                    'body'      => $ic['msg_text'],
                    'date'      => $ic['msg_time']
                );
                // Write to DB
                $writeBodyAction = DBUtil::insertObject($new_body,'communicator_mail_body');
                if ($writeBodyAction) {
                    // build headers
                    $new_headers = array();
                    $toname   = pnUserGetVar('uname',$ic['to_userid']);
                    $fromname = pnUserGetVar('uname',$ic['from_userid']);
                    if (!isset($toname) || ($toname == '')) {
                        $toname = 'USER ID '.$ic['to_userid'];
                    }
                    if (!isset($fromname) || ($fromname == '')) {
                        $fromname = 'USER ID '.$ic['from_userid'];
                    }
                    // INBOX
                    if (($ic['msg_inbox'] == 1) || ($ic['msg_stored'] == 1)) {
                        $new_headers[] = array (
                            'uid'       => $ic['to_userid'],
                            'from'      => $ic['from_userid'],
                            'to'        => $ic['to_userid'],
                            'from_name' => $fromname,
                            'to_name'   => $toname,
                            'mid'       => $new_body['id'],
                            'mail_read' => $ic['msg_read'],
                            'replied'   => $ic['msg_replied'],
                            'popup'     => 0,
                            'folder'    => 0
                        );
                    }
                    // OUTBOX
                    if ($ic['msg_outbox'] == 1) {
                        $new_headers[] = array (
                            'uid'       => $ic['from_userid'],
                            'from'      => $ic['from_userid'],
                            'to'        => $ic['to_userid'],
                            'from_name' => $fromname,
                            'to_name'   => $toname,
                            'mid'       => $new_body['id'],
                            'mail_read' => 1,
                            'replied'   => 0,
                            'popup'     => $ic['msg_popup'],
                            'folder'    => -1
                        );
                    }
                    $result = DBUtil::insertObjectArray($new_headers,'communicator_mail_header');
                    $internalcounter++;
                }
                // Write to DB
                // update internal counter
                $counter = $ic['msg_id'];
                pnModSetVar('Communicator','intercom',$counter);
            }
        }
        LogUtil::registerStatus($internalcounter." ".__('Messages imported successfully',$dom));
        return $render->pnFormRedirect(pnModURL('Communicator','admin','intercom'));
		return true;
    }
}