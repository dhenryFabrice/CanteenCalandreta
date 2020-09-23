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
 * Support module : allow a supporter to update a meeting room registration. The supporter must be logged
 * to update the meeting room registration.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-25
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 // To take into account the crypted and no-crypted meeting room registration ID
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
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../../Common/JSCalendar/dynCalendar.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array(
                            '../../Common/JSCalendar/browserSniffer.js',
                            '../../Common/JSCalendar/dynCalendar.js',
                            '../../Common/JSCalendar/UseCalendar.js',
                            '../Verifications.js'
                           ),
                      'WhitePage'
                     );

 // Content of the web page
 openArea('id="content"');

 // The ID and the md5 crypted ID must be equal
 if (md5($Id) == $CryptedID)
 {
      // Display the meeting room registration form
      displayDetailsMeetingRoomRegistrationForm($DbCon, $Id, "ProcessUpdateMeetingRoomRegistration.php", $CONF_ACCESS_APPL_PAGES[FCT_MEETING], array());

      // Display hyperlink to return on the planning
      if ($Id >= 0)
      {
          displayBR(2);
          openParagraph('InfoMsg');


          displayStyledLinkText($LANG_CONTEXTUAL_MENU_SUPPORT_COOP_MEETING_ROOMS_PLANNING, 'MeetingRoomsPlanning.php', '',
                                $LANG_CONTEXTUAL_MENU_SUPPORT_COOP_MEETING_ROOMS_PLANNING_TIP);
          closeParagraph();
      }
 }
 else
 {
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_NOT_VIEW_MEETING_ROOM_REGISTRATION, 'ErrorMsg');
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