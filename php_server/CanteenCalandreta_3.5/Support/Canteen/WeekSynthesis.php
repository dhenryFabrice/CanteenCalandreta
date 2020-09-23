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
 * Support module : display a synthesis of the canteen registrations for the selected week to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2012-12-31 : patch a bug to get the good number of week in the year when the current date
 *                    is yyyy-12-31
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-02-02
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Include the stats library
 include_once('../Stats/StatsLibrary.php');

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 if (!empty($_POST["lWeek"]))
 {
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     $ParamsPOST_GET = "_GET";
 }

 // Get the current year
 $CurrentYear = date("Y");

 // To take into account the week to display
 if (!empty(${$ParamsPOST_GET}["lWeek"]))
 {
     // We get the given week
     $ArrayWeekYear = explode("-", strip_tags(${$ParamsPOST_GET}["lWeek"]));
     $Year = (integer)$ArrayWeekYear[0];
     $Week = (integer)$ArrayWeekYear[1];

     if (($Year > $CurrentYear) && ($Week > 1) && (date("W") > 1))
     {
         // The year can be > current year in the case of yyyy-12-31 where the week is 1
         $Year = $CurrentYear;
     }

     // We check the max week in the current year
     if (($Week < 1) || (($Year == $CurrentYear) && ($Week > getNbWeeksOfYear($CurrentYear))))
     {
         // Wrong week : we get the current week
         $Week = date("W");
     }
 }
 else
 {
     // We get the current week
     $Week = date("W");
     $Year = $CurrentYear;

     if (($Week == 1) && (date("m") == 12))
     {
         // The year can be > current year in the case of yyyy-12-31 where the week is 1
         $Year++;
     }
 }

 // We have to print the week synthesis?
 $bOnPrint = FALSE;
 if (!empty($_POST["hidOnPrint"]))
 {
     if ($_POST["hidOnPrint"] == 1)
     {
         $bOnPrint = TRUE;
     }
 }

 // Patch for the weeks < 10 : the number of the week must be on 2 digits
 if ($Week < 10)
 {
     // The cast in integer must be done for php5
     $Week = "0".(integer)$Week;
 }

 if (!$bOnPrint)
 {
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array('../Verifications.js')
                         );
     openWebPage();

     // Display invisible link to go directly to content
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#lWeek', 'Accessibility');

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

         displaySupportMemberContextualMenu("canteen", 1, Canteen_WeekSynthesis);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_TITLE, 2);

     openParagraph();
     displayStyledText($LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_INTRODUCTION." S$Week de $Year.");
     closeParagraph();

     // We display the synthesis of the week
     displayCanteenWeekSynthesisForm($DbCon, "WeekSynthesis.php", $Week, $Year, $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING]);

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
 }
 else
 {
     // Print the synthesis
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen',
                                '../../Templates/PrintStyles.css' => 'print'
                               ),
                          array()
                         );

     openWebPage();
     openArea('id="content"');

     printCanteenWeekSynthesis($DbCon, "WeekSynthesis.php", $Week, $Year, $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING]);

     closeArea();
     closeWebPage();
     closeGraphicInterface();

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>