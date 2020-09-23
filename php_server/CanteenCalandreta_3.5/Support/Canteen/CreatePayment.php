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
 * Support module : display the form to add a payment for one or several bills of a family
 * to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-03-08
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

 // To take into account the selected family ID
 if (!empty($_POST["lFamilyID"]))
 {
     $SelectedFamilyID = trim(strip_tags($_POST["lFamilyID"]));
 }
 else
 {
     if (!empty($_GET["lFamilyID"]))
     {
         $SelectedFamilyID = trim(strip_tags($_GET["lFamilyID"]));
     }
     else
     {
         $SelectedFamilyID = 0;
     }
 }

 // To take into account the selected bills ID
 if (!empty($_POST["lmBillID"]))
 {
     $ArraySelectedBillID = $_POST["lmBillID"];
 }
 else
 {
     if (!empty($_GET["lmBillID"]))
     {
         $ArraySelectedBillID = $_GET["lmBillID"];
     }
     else
     {
         $ArraySelectedBillID = array();
     }
 }

 //################################ FORM PROCESSING ##########################
 $bDisplayAddPaymentForm = FALSE;
 if (!empty($_POST["bSubmit"]))
 {
     // We check there is a selected family and at least one selected bill
     if (($SelectedFamilyID > 0) && (!empty($ArraySelectedBillID)))
     {
         $PaymentType = 1;  // Payment for a bill
         $bDisplayAddPaymentForm = TRUE;
     }
 }

 //################################ END FORM PROCESSING ##########################

 $ArrayPageCSS = array(
                       '../../GUI/Styles/styles.css' => 'screen',
                       '../Styles_Support.css' => 'screen'
                      );

 $ArrayPageJS = array('../Verifications.js');
 if ($bDisplayAddPaymentForm)
 {
     $ArrayPageCSS['../../Common/JSCalendar/dynCalendar.css'] = 'screen';

     $ArrayPageJS = array_merge($ArrayPageJS,
                                array(
                                      '../../Common/JSCalendar/browserSniffer.js',
                                      '../../Common/JSCalendar/dynCalendar.js',
                                      '../../Common/JSCalendar/UseCalendar.js'
                                     ));
 }

 initGraphicInterface($LANG_INTRANET_NAME, $ArrayPageCSS, $ArrayPageJS);
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#lFamilyID', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_AddPayment);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_CREATE_PAYMENT_PAGE_TITLE, 2);

 // We generate the bills of the selected year and month
 openParagraph();

 $Introduction = $LANG_SUPPORT_CREATE_PAYMENT_PAGE_INTRODUCTION;
 if (!empty($SelectedFamilyID))
 {
     $RecordFamily = getTableRecordInfos($DbCon, "Families", $SelectedFamilyID);
     if (!empty($RecordFamily))
     {
         $Introduction .= " $LANG_SUPPORT_CREATE_PAYMENT_PAGE_SELECTED_FAMILY <strong>".$RecordFamily['FamilyLastname']."</strong>.";
     }

     unset($RecordFamily);
 }

 displayStyledText($Introduction);
 closeParagraph();

 if ($bDisplayAddPaymentForm)
 {
     // Display data about the selected bills
     $ArrayBills = getBills($DbCon, NULL, NULL, 'BillForDate', NO_DATES, array("BillID" => $ArraySelectedBillID));
     if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
     {
         echo "<dl class=\"BillListForPayment\">\n<dt>$LANG_SUPPORT_CREATE_PAYMENT_PAGE_CONCERNED_BILLS_BY_PAYMENT</dt>\n";
         foreach($ArrayBills['BillID'] as $b => $BillID)
         {
             $Month = date("m", strtotime($ArrayBills["BillForDate"][$b]));
             $Year = date("Y", strtotime($ArrayBills["BillForDate"][$b]));

             $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";

             // Compute the amount of the bill
             $BillAmount = $ArrayBills['BillAmount'][$b];

             $BillCaption .= " : $BillAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'];

             echo "<dd>$BillCaption</dd>\n";
         }

         echo "</dl>\n";
     }

     // We display the form to create a payment for the selected family and bill(s)
     displayDetailsPaymentForm($DbCon, 0, $SelectedFamilyID, $PaymentType, $ArraySelectedBillID, "ProcessNewBillsPayment.php",
                               $CONF_ACCESS_APPL_PAGES[FCT_PAYMENT]);
 }
 else
 {
     // We display the form to select a family and at least one bill
     displaySelectBillsOfFamilyForm($DbCon, "CreatePayment.php", $SelectedFamilyID, $ArraySelectedBillID,
                                    $CONF_ACCESS_APPL_PAGES[FCT_PAYMENT]);
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