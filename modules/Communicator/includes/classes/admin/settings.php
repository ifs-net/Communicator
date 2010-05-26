<?php
class communicator_admin_settings_handler
{
    function initialize(&$render)
    {
        // Language Domain
        $dom = ZLanguage::getModuleDomain('Communicator');
        
        // Assign module variable values to template
        $tpl_vars['dateformat']         = pnModGetVar('Communicator','dateformat');
        $tpl_vars['quota']              = pnModGetVar('Communicator','quota');
        $tpl_vars['allow_html']         = pnModGetVar('Communicator','allow_html');
        $tpl_vars['spam_allowed_time']  = pnModGetVar('Communicator','spam_allowed_time');
        $tpl_vars['spam_allow_max']     = pnModGetVar('Communicator','spam_allow_max');
                
        // Add variables to template
        $render->assign($tpl_vars);
		return true;
    }

    function handleCommand(&$render, &$args)
    {
		if ($args['commandName']=='update') {
		    if (!$render->pnFormIsValid()) return false;
		    $obj = $render->pnFormGetValues();
		    pnModSetVar('Communicator','dateformat',          $obj['dateformat']);
		    pnModSetVar('Communicator','quota',               $obj['quota']);
		    pnModSetVar('Communicator','allow_html',          $obj['allow_html']);
		    pnModSetVar('Communicator','spam_allowed_time',   $obj['spam_allowed_time']);
		    pnModSetVar('Communicator','spam_allow_max',      $obj['spam_allow_max']);
		    
		    // Register Status
		    LogUtil::registerStatus(__('Settings updated',$dom));
		}
		$render->pnFormRedirect(pnModURL('Communicator','admin','settings'));
		return true;
    }
}