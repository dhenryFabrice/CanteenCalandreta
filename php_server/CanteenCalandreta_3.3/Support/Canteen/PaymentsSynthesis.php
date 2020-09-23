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
 * Support module : display a synthesis of the payments for the selected month
 * to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
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
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 if (!empty($_POST["lYearMonth"]))
 {
     $YearMonth = trim(strip_tags($_POST["lYearMonth"]));
 }
 else
 {
     if (!empty($_GET["lYearMonth"]))
     {
         $YearMonth = trim(strip_tags($_GET["lYearMonth"]));
     }
     else
     {
         $YearMonth = date("Y-m", strtotime("last month"));  // Last month

         // To patch a bug of PHP : sometimes, the last month = current month (ex : for 2012-05-31)
         if (date('Y-m') === $YearMonth)
         {
             // One month ago from yesterday
             $YearMonth = date('Y-m', strtotime("last month", strtotime("1 day ago")));
         }
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

 // Year-Month
 $ArrayTmp = explode('-', $YearMonth);
 $Year = $ArrayTmp[0];
 $Month = $ArrayTmp[1];
 unset($ArrayTmp);

 if ($Year < 2003)
 {
     $Year = (integer)(date("Y"));
 }

 if ($Year > 2037)
 {
     $Year = (integer)(date("Y"));
 }

 if ($Month < 1)
 {
     $Month = (integer)(date("m"));
 }

 if ($Month > 12)
 {
     $Month = (integer)(date("m"));
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
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#PaymentsList', 'Accessibility');

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

         displaySupportMemberContextualMenu("canteen", 1, Canteen_PaymentsSynthesis);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_PAYMENTS_SYNTHESIS_PAGE_TITLE, 2);

     openParagraph();
     displayStyledText($LANG_SUPPORT_PAYMENTS_SYNTHESIS_PAGE_INTRODUCTION." ".$CONF_PLANNING_MONTHS[$Month - 1]." $Year.");
     closeParagraph();

     // We display the payments synthesis of the month
     displayPaymentsSynthesisForm($DbCon, "PaymentsSynthesis.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_PAYMENT],
                                  "UpdateFamily.php");

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

     printPaymentsynthesis($DbCon, "PaymentsSynthesis.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_PAYMENT]);

     closeArea();
     closeWebPage();
     closeGraphicInterface();

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>