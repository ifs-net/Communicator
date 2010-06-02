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
 * display message
 *
 * @param   $args['message']             array
 * @param   $args['printer_view']        array
 * @param   $args['ajax']                int        optional for ajax loader
 *
 * @return  output
 */
function Communicator_outputapi_showMessage($args)
{
    // get Parameters
    $message      =       $args['message'];
    $printer_view = (int) $args['printer_view'];
    $ajax         = (int) $args['ajax'];
    // get rendering instance
    $render = pnRender::getInstance('Communicator');
    $render->assign('message',      $message);
    $render->assign('printer_view', $printer_view);
    $render->assign('ajax',         $ajax);
    $render->assign('dateformat',   pnModGetVar('Communicator','dateformat'));
    if ($printer_view == 1) {
        return $render->fetch('communicator_output_message.htm');
    } else {
        $output = $render->display('communicator_output_message.htm');
        return $output;
    }
}

/**
 * display intro message
 *
 * @return  output
 */
function Communicator_outputapi_showIntroText($args)
{
    // get rendering instance and return output
    $render = pnRender::getInstance('Communicator');
    $output = $render->display('communicator_output_introtext.htm');
    return $output;
}

/**
 * display folder Menu
 *
 * @return  output
 */
function Communicator_outputapi_showAjaxFolderMenu($args) {
    // get rendering instance and return output
    $render = pnRender::getInstance('Communicator');
    $folders = pnModAPIFunc('Communicator','user','getFolders');
    $render->assign('folders', $folders);
    $output = $render->display('communicator_output_ajax_folder_menu.htm');
    return $output;
}

/**
 * display popup if there are new messages for a user
 *
 * @return output
 */
function Communicator_outputapi_popup()
{
    $module = strtolower(FormUtil::getPassedValue('module'));
    if (pnUserLoggedIn() && ($module != 'communicator')) {
        $uid = pnUserGetVar('uid');
        pnModDBInfoLoad('Communicator');
        $tables = pnDBGetTables();
        $header_column = $tables['communicator_mail_header_column'];
        $where = $header_column['popup']." = 0 AND ".$header_column['read']." = 0 AND ".$header_column['uid']." = ".$uid;
        $countAction = DBUtil::selectObjectCount('communicator_mail_header',$where);
        if ($countAction > 0) {
            // get unread messages
            $messageinfo = pnModAPIFunc('Communicator','user','getMessageCount');
            // create output
            $render = pnRender::getInstance('Communicator');
            $output = $render->fetch('communicator_output_popup.htm');
            // now mark the as "popped up"
            $sql = "UPDATE ".$tables['communicator_mail_header']." SET ".$header_column['popup']." = 1 WHERE ".$header_column['popup']." = 0 AND ".$header_column['uid']." = $uid";
            DBUtil::executeSQL($sql);
            // return output
            return $output;
        }
    }
}
