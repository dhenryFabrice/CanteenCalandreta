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
 * Support module : allow a supporter to reset the affectation of a payment to bills.
 * Delete links between payment and bills, set to 0.00 the used amount of the payment,
 * remove used part amount of the payment to paid amount of the linked bills.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-01-20
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted payment ID
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
     $Id = 0;
 }

 //################################ FORM PROCESSING ##########################
 // The ID and the md5 crypted ID must be equal
 if ((md5($Id) == $CryptedID) && ($Id > 0))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
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

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the payment
         if (isExistingPayment($DbCon, $Id))
         {
             // The payment exists
             $PaymentID = $Id;

             // We get the bills linked to the payment
             $ArrayBills = getBillsOfPayment($DbCon, $PaymentID, array(), 'BillID');
         }
         else
         {
             // ERROR : the payment doesn't exist
             $ContinueProcess = FALSE;
         }

         if ($ContinueProcess)
         {
             if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
             {
                 // Delete links between the payment and the bills
                 $bUpdated = TRUE;
                 foreach($ArrayBills['BillID'] as $b => $BillID)
                 {
                     if (!dbRemovePaymentBill($DbCon, $PaymentID, $BillID))
                     {
                         $bUpdated = FALSE;
                     }
                 }
             }

             if ($bUpdated)
             {
                 // Log event
                 logEvent($DbCon, EVT_PAYMENT, EVT_SERV_PAYMENT, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $PaymentID);

                 // The payment is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_PAYMENT_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdatePayment.php?Cr=".md5($PaymentID)."&Id=$PaymentID"; // For the redirection
             }
             else
             {
                 // The payment can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_PAYMENT;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "UpdatePayment.php?".$QUERY_STRING; // For the redirection
             }
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = "UpdatePayment.php?".$QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the UpdatePayment.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = "UpdatePayment.php?".$QUERY_STRING; // For the redirection
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/$UrlParameters', $CONF_TIME_LAG)"
                     );

 // Content of the web page
 openArea('id="content"');

 openFrame($ConfirmationCaption);
 displayStyledText($ConfirmationSentence, $ConfirmationStyle);
 closeFrame();

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