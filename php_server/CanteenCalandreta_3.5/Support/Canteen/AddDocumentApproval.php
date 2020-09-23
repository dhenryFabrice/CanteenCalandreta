<?php
/* Copyright (C) 2007  STNA/7SQ (IVDS)
 *
 * This file is part of ASTRES.
 *
 * ASTRES is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ASTRES is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ASTRES; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Support module : allow a supporter to add a document to be approved by families. The supporter must be logged
 * to add the document.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2019-05-07
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
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

 // To take into account the crypted and no-crypted family ID
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
                            '../Styles_Support.css' => 'screen'
                           ),
                      array(
                            '../Verifications.js'
                           )
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#DocumentDetails', 'Accessibility');

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu(1);

 // Content of the web page
 openArea('id="content"');

 // Display the "Canteen" and the "parameters" contextual menus if the supporter isn't logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("canteen", 1, Canteen_AddDocumentApproval);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_ADD_DOCUMENT_APPROVAL_PAGE_TITLE, 2);

 openParagraph();
 echo $LANG_SUPPORT_ADD_DOCUMENT_APPROVAL_PAGE_INTRODUCTION;
 closeParagraph();

 // the ID and the md5 crypted ID must be equal ; if the ID is empty, the document will be a new document
 if (md5($Id) == $CryptedID)
 {
     displayDetailsDocumentApprovalForm($DbCon, $Id, "ProcessNewDocumentApproval.php", $CONF_ACCESS_APPL_PAGES[FCT_DOCUMENT_APPROVAL]);
 }
 else
 {
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_NOT_VIEW_DOCUMENT_APPROVAL, "ErrorMsg");
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