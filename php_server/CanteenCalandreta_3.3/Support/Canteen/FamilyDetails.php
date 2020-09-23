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
 * Support module : allow a logged supporter to view or modify his family details
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2013-09-18 : taken into account the FCT_ACT_PARTIAL_READ_ONLY access right
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-07-12
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Connection to the database
 $DbCon = dbConnection();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

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

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("parameters", 1, Param_FamilyDetails);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_FAMILY_DETAILS_PAGE_TITLE, 2);

 openParagraph();
 displayStyledText($LANG_FAMILY_DETAILS_PAGE_INTRODUCTION, '');
 closeParagraph();

 // Get the ID of the family linked to the logged supporter
 $ArrayFamilies = dbSearchFamily($DbCon, array("FamilyID" => $_SESSION['FamilyID']), "FamilyID", 1, 1);

 $Id = 0;
 $CryptedID = 0;
 if ((isset($ArrayFamilies['FamilyID'])) && (count($ArrayFamilies['FamilyID']) > 0))
 {
    $Id = $ArrayFamilies['FamilyID'][0];
    $CryptedID = md5($Id);
 }

 // The ID and the md5 crypted ID must be equal
 if ((!empty($Id)) && (md5($Id) == $CryptedID))
 {
      displayDetailsFamilyForm($DbCon, $Id, "ProcessUpdateFamily.php", $CONF_ACCESS_APPL_PAGES[FCT_FAMILY]);
 }
 else
 {
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_NOT_VIEW_FAMILY, 'ErrorMsg');
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