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
 * initialise block
 * 
 */
function Communicator_newmessagesblock_init()
{
    // Security
    pnSecAddSchema('Communicator:newmessagesblock:', 'Block title::');
}

/**
 * get information on block
 * 
 * @return       array       The block information
 */
function Communicator_newmessagesblock_info()
{
    $dom = ZLanguage::getModuleDomain('Communicator');
    return array('text_type'      => 'newmessages',
                 'module'         => 'Communicator',
                 'text_type_long' => __('Unread Messages Block',$dom),
                 'allow_multiple' => true,
                 'form_content'   => false,
                 'form_refresh'   => false,
                 'show_preview'   => true);
}

/**
 * display block
 * 
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered bock
 */
function Communicator_newmessagesblock_display($blockinfo)
{
    // Check if the Communicator module is available. 
	$act_mod  = strtolower(pnModGetName());
    if (!pnModAvailable('Communicator') || ($act_mod == 'communicator')) {
        return false;
    }

    // Security check
    if (!SecurityUtil::checkPermission('Communicator:newmessagesblock', "$blockinfo[title]::", ACCESS_READ)) return false;
    
    // Create output object
    $render =  pnRender::getInstance('Communicator',false);

    // get number of unread messages.
    $messageinfo = pnModAPIFunc('Communicator','user','getMessageCount',array('uid' => pnUserGetVar('uid')));
    
    $render->assign($messageinfo);
    
    // Populate block info and pass to theme
    $blockinfo['content'] = $render->fetch('communicator_block_newmessages.htm');

    return themesideblock($blockinfo);
}


/**
 * update block settings
 * 
 * @param        array       $blockinfo     a blockinfo structure
 * @return       $blockinfo  the modified blockinfo structure
 */
function Communicator_newmessagesblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);
	
    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $render = pnRender::getInstance('Communicator');
    $render->clear_cache('communicator_block_newmessages.htm');
	
    return $blockinfo;
}
