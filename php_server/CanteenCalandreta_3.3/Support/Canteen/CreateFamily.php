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
 * Support module : allow a supporter to create a new family. The supporter must be logged
 * to create the family.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-01-16
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
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

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
                            '../../Common/JSCalendar/dynCalendar.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array(
                            '../../Common/JSCalendar/browserSniffer.js',
                            '../../Common/JSCalendar/dynCalendar.js',
                            '../../Common/JSCalendar/UseCalendar.js',
                            '../Verifications.js'
                           )
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#FamilyDetails', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_CreateFamily);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_CREATE_FAMILY_PAGE_TITLE, 2);

 // the ID and the md5 crypted ID must be equal ; if the ID is empty, the family will be a new family
 if (md5($Id) == $CryptedID)
 {
     displayDetailsFamilyForm($DbCon, $Id, "ProcessNewFamily.php", $CONF_ACCESS_APPL_PAGES[FCT_FAMILY]);
 }
 else
 {
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_NOT_VIEW_FAMILY, "ErrorMsg");
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