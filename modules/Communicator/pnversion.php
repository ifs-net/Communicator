<?php

$domain = ZLanguage::getModuleDomain('Communicator');

$modversion['name']           = 'Communicator';
$modversion['displayname']    = __('Communicator', $domain);
$modversion['url']            = 'Communicator';
$modversion['description']    = __('More than just private messaging', $domain);

$modversion['version']        = '0.9.0';

$modversion['changelog']      = 'docs/changelog.txt';
$modversion['credits']        = 'docs/credits.txt';
$modversion['help']           = 'docs/help.txt';
$modversion['license']        = 'docs/license.txt';
$modversion['official']       = 0;
$modversion['author']         = 'Florian Schiessl';
$modversion['contact']        = 'http://www.ifs-net.de/';
$modversion['admin']          = 1;
$modversion['profile']        = 0;
$modversion['message']        = 1;

$modversion['dependencies'] = array(
    array( 'modname'    => 'ContactList',
           'minversion' => '1.6', 'maxversion' => '',
           'status'     => PNMODULE_DEPENDENCY_RECOMMENDED)
);

$modversion['securityschema'] = array('Communicator::' => 'item name::item ID');
