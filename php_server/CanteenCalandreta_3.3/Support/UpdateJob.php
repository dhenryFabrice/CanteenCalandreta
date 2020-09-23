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
 * Support module : allow a supporter to update a job. The supporter must be logged to update the job.
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2017-09-27
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Connection to the database
 $DbCon = dbConnection();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted job ID
 // Crypted ID
 if (!empty($_GET["Cr"]))
 {
     $CryptedID = (string)strip_tags($_GET["Cr"]);
 }
 else
 {
     $CryptedID = '';
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = '';
 }

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            '../Common/JSCalendar/dynCalendar.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array(
                            '../Common/JSCalendar/browserSniffer.js',
                            '../Common/JSCalendar/dynCalendar.js',
                            '../Common/JSCalendar/UseCalendar.js',
                            'Verifications.js'
                           ),
                      'WhitePage'
                     );

 // Content of the web page
 openArea('id="content"');

 // The ID and the md5 crypted ID must be equal
 if (md5($Id) == $CryptedID)
 {
      displayDetailsJobForm($DbCon, $Id, "ProcessUpdateJob.php", $CONF_ACCESS_APPL_PAGES[FCT_MESSAGE]);

      // Display hyperlink to return on the list of jobs
      displayBR(2);
      openParagraph('InfoMsg');
      displayStyledLinkText($LANG_CONTEXTUAL_MENU_MESSAGES_JOBS_LIST, 'MessagesJobsList.php', '', $LANG_CONTEXTUAL_MENU_MESSAGES_JOBS_LIST_TIP);
      closeParagraph();
 }
 else
 {
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_NOT_VIEW_JOB, 'ErrorMsg');
     closeFrame();
 }

 // Release the connection to the database
 dbDisconnection($DbCon);

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