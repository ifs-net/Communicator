<?php

/**
 * get available admin panel links
 *
 * @return array array of admin links
 */
function Communicator_adminapi_getlinks()
{

    // Language Domain
    $dom = ZLanguage::getModuleDomain('Communicator');
    
    $links = array();
    if (SecurityUtil::checkPermission('Communicator::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('Communicator', 'admin', 'main'), 'text' => __('Main Modul Configuration', $dom));
        $links[] = array('url' => pnModURL('Communicator', 'admin', 'intercom'), 'text' => __('Import Mails from InterCom'));
    }
    return $links;
}