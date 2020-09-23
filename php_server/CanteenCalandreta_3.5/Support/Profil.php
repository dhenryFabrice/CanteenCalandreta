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
 * Support module : allow a logged supporter to view or modify his profil
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2005-02-16 : redirect the user to the login page index.php if he isn't loggued
 *     - 2007-01-16 : new interface
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-03-01 : Param_Profil
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2004-02-21
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array(
                            'Verifications.js', '../Common/JSMD5/MD5.js'
                           )
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#ProfilDetails', 'Accessibility');

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu();

 // Content of the web page
 openArea('id="content"');

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("parameters", 0, Param_Profil);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_PROFIL_PAGE_TITLE, 2);

 openParagraph();
 displayStyledText($LANG_PROFIL_PAGE_INTRODUCTION, '');
 closeParagraph();

 displaySeparator($LANG_PROFIL);

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Connection to the database
     $DbCon = dbConnection();

     displaySupportMemberProfil($DbCon, $_SESSION["SupportMemberID"]);

     displaySeparator($LANG_LOGIN);

     displaySupportMemberLoginPwd($DbCon, $_SESSION["SupportMemberID"]);

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
 else
 {
     // The user isn't logged
     openParagraph('InfoMsg');
     displayStyledText($LANG_ERROR_NOT_LOGGED, 'ErrorMsg');
     closeParagraph();
 }

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Close the <div> "Page"
     closeArea();
 }

 // Close the <div> "content"
 closeArea();

 // Footer of the application
 displayFooter($LANG_INTRANET_FOOTER);

 // Close the web page
 closeWebPage();

 closeGraphicInterface();
?>