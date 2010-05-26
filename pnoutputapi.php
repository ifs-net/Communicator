<?php

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