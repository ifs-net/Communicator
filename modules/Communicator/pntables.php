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
 * Populate tables array for MyProfile module
 *
 * @return       array       The table information.
 */
function Communicator_pntables()
{
    // Initialise table array
    $pntable = array();

    // Set the table name
    $pntable['communicator_mail_header']   = DBUtil::getLimitedTablename('communicator_mail_header');
    $pntable['communicator_mail_body']   = DBUtil::getLimitedTablename('communicator_mail_body');
    $pntable['communicator_folders'] = DBUtil::getLimitedTablename('communicator_folders');

    // Set the column names.  Note that the array has been formatted
    // on-screen to be very easy to read by a user.
    $pntable['communicator_mail_header_column'] = array(
        'id'                    => 'id',
        'uid'                   => 'c_uid',
        'from'                  => 'c_from',
        'to'                    => 'c_to',
        'from_name'             => 'c_from_name',
        'to_name'               => 'c_to_name',
        'mid'                   => 'c_mid',
        'read'                  => 'c_mail_read',
        'replied'               => 'c_replied',
        'forwarded'             => 'c_forwarded',
        'flagged'               => 'c_flagged',
        'priority'              => 'c_priority',
        'folder'                => 'c_folder',            // folder id or 0 = inbox, -1 = outbox.
        'systemmail'            => 'c_systemmail',
        'reference'             => 'c_reference',
        'receipt'               => 'c_receipt',
        'receipt_sent'          => 'c_receipt_sent',
        'popup'                 => 'c_popup'
        );
    $pntable['communicator_mail_header_column_def'] = array(
        'id'                    => "I AUTOINCREMENT PRIMARY",
        'uid'                   => "I NOTNULL DEFAULT 1",
        'from'                  => "I NOTNULL DEFAULT 1",
        'to'                    => "I NOTNULL DEFAULT 1",
        'from_name'             => "C(125) NOTNULL DEFAULT ''",
        'to_name'               => "C(125) NOTNULL DEFAULT ''",
        'mid'                   => "I NOTNULL DEFAULT 0",
        'read'                  => "I(1) NOTNULL DEFAULT 0",
        'replied'               => "I(1) NOTNULL DEFAULT 0",
        'forwarded'             => "I(1) NOTNULL DEFAULT 0",
        'flagged'               => "I(1) NOTNULL DEFAULT 0",
        'priority'              => "I(1) NOTNULL DEFAULT 2",
        'folder'                => "I NOTNULL DEFAULT 0",
        'systemmail'            => "I(1) NOTNULL DEFAULT 0",
        'reference'             => "I NOTNULL DEFAULT 0",
        'receipt'               => "I(1) NOTNULL DEFAULT 0",
        'receipt_sent'          => "I(1) NOTNULL DEFAULT 0",
        'popup'                 => "I(1) NOTNULL DEFAULT 0"
        );

    $pntable['communicator_mail_body_column'] = array(
        'id'                    => 'id',
        'subject'               => 'c_subject',
        'body'                  => 'c_body',
        'date'                  => 'c_date'
        );
    $pntable['communicator_mail_body_column_def'] = array(
        'id'                    => "I AUTOINCREMENT PRIMARY",
        'subject'               => "C(255) NOTNULL DEFAULT ''",
        'body'                  => "X NOTNULL",
        'date'                  => "T NOTNULL"
        );

    $pntable['communicator_folders_column'] = array(
        'id'                    => 'id',
        'uid'                   => 'c_uid',
        'title'                 => 'c_title'
        );
    $pntable['communicator_folders_column_def'] = array(
        'id'                    => "I AUTOINCREMENT PRIMARY",
        'uid'                   => "I NOTNULL DEFAULT 1",
        'title'                 => "C(125) DEFAULT ''"
        );

    // Return the table information
    return $pntable;
}