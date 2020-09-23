<?php
/* Copyright (C) 2012 Calandreta Del Païs Murethin
 *
 * This file is part of CanteenCalandreta.
 *
 * CanteenCalandreta is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CanteenCalandreta is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CanteenCalandreta; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Support module : display the form to search a family or an alias to set as recipient
 * of a message to send.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2016-03-14
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "Supporter" session or use the opened "Supporter" session
 session_start();

 // This array contains the parameters of the search
 $ArrayParams = array();
 $DbCon = NULL;

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["sName"]))
 {
     $sName = trim(strip_tags($_POST["sName"]));
     if (strlen($sName) >= 3)
     {
         $ArrayParams = array(
                              "SchoolYear" => array(getSchoolYear(date('Y-m-d'))),
                              "SupportMemberActivated" => array(1),
                              "Name" => "%$sName%"
                             );

         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));
     }
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array('Verifications.js'),
                      'WhitePage',
                      'document.forms[0].sName.focus();'
                     );

 // Content of the web page
 openArea('id="content"');

 displaySearchRecipientMessageForm($DbCon, "SearchMessageRecipients.php", $ArrayParams, $CONF_ACCESS_APPL_PAGES[FCT_MESSAGE]);

 // Release the connection to the database
 if (isset($DbCon))
 {
     dbDisconnection($DbCon);
 }

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 // Close the <div> "content"
 closeArea();

 closeGraphicInterface();
?>
