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
 * Support module : display a synthesis for the selected day to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2012-10-17 : take into account the "type" list to display : children eating to the canteen
 *                    or children don't eating to the canteen
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-02-03
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
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 if (!empty($_POST["lDay"]))
 {
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     $ParamsPOST_GET = "_GET";
 }

 // To take into account the day to display
 if (!empty(${$ParamsPOST_GET}["lDay"]))
 {
     // We get the given day
     $ArrayYearMonthDay = explode("-", strip_tags(${$ParamsPOST_GET}["lDay"]));

     $Year = (integer)$ArrayYearMonthDay[0];
     if ($Year > date("Y"))
     {
         $Year = date("Y");
     }

     $Month = (integer)$ArrayYearMonthDay[1];
     if (($Month < 1) || ($Month > 12))
     {
         // Wrong month : we get the current month
         $Month = date("m");
     }

     $Day = (integer)$ArrayYearMonthDay[2];
     if (($Day < 1) || (($Month == date("m")) && ($Day > date("t"))))
     {
         // Wrong day : we get the current day
         $Day = date("d");
     }
 }
 else
 {
     // We get the current day
     $Year = date("Y");
     $Month = date("m");
     $Day = date("d");
 }

 // To take into account the type of list to display
 $TypeOfDisplay = 0;  // Default display is "children eating to the canteen"
 if (isset(${$ParamsPOST_GET}["lDisplayType"]))
 {
     $TypeOfDisplay = strip_tags(${$ParamsPOST_GET}["lDisplayType"]);
     if ((is_null($TypeOfDisplay)) || ($TypeOfDisplay < 0))
     {
         $TypeOfDisplay = 0;  // Default display
     }
 }

 // We have to print the synthesis of the day?
 $bOnPrint = FALSE;
 if (!empty($_POST["hidOnPrint"]))
 {
     if ($_POST["hidOnPrint"] == 1)
     {
         $bOnPrint = TRUE;
     }
 }

 // Patch for the day < 10 : the number of the day must be on 2 digits
 if ($Day < 10)
 {
     // The cast in integer must be done for php5
     $Day = "0".(integer)$Day;
 }

 // Patch for the month < 10 : the number of the day must be on 2 digits
 if ($Month < 10)
 {
     // The cast in integer must be done for php5
     $Month = "0".(integer)$Month;
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
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#lDay', 'Accessibility');

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

         displaySupportMemberContextualMenu("canteen", 1, Canteen_DaySynthesis);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_DAY_SYNTHESIS_PAGE_TITLE, 2);

     openParagraph();

     switch($TypeOfDisplay)
     {
         case 1:
             // Display children don't eating to the canteen for the selected day
             displayStyledText($LANG_SUPPORT_DAY_SYNTHESIS_PAGE_INTRODUCTION_DONT_EATING." "
                               .date($CONF_DATE_DISPLAY_FORMAT, strtotime("$Year-$Month-$Day")).".");
             break;

         case 0:
         default:
             // Display children eating to the canteen for the selected day
             displayStyledText($LANG_SUPPORT_DAY_SYNTHESIS_PAGE_INTRODUCTION." "
                               .date($CONF_DATE_DISPLAY_FORMAT, strtotime("$Year-$Month-$Day")).".");
             break;
     }

     closeParagraph();

     // We display the synthesis of the day
     displayCanteenDaySynthesisForm($DbCon, "DaySynthesis.php", $Day, $Month, $Year, $TypeOfDisplay,
                                    $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING]);

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

     printCanteenDaySynthesis($DbCon, "DaySynthesis.php", $Day, $Month, $Year, $TypeOfDisplay,
                              $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING]);

     closeArea();
     closeWebPage();
     closeGraphicInterface();

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>