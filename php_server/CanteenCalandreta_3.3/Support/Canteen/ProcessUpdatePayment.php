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
 * Support module : process the update of a payment. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 *     - 2013-01-21 : allow to add/remove one or several bills to the payment, check if a payment
 *                    is unique for a family and a bank and taken into account the new field
 *                    "PaymentReceiptDate"
 *     - 2013-12-18 : taken into account the new way how functions dbSetBillsPaid()
 *     - 2016-10-12 : taken into account to enter manualy part amount of a payment allocated to a bill
 *                    ($CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT) and load some configuration variables
 *                    from database
 *     - 2019-07-16 : taken into account the FamilyAnnualContributionBalance field to split payments for bills
 *                    and payments for annual contributions
 *
 * @since 2012-05-30
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
     $CryptedID = "";
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = "";
 }

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
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

         // We get the ID of the family of the payment
         $FamilyID = $_POST["hidFamilyID"];
         if ($FamilyID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $PaymentType = NULL;
         if (isset($_POST["lPaymentType"]))
         {
             $PaymentType = strip_tags($_POST["lPaymentType"]);
         }

         $PaymentMode = strip_tags($_POST["lPaymentMode"]);
         $BankID = strip_tags($_POST["lBankID"]);

         $sCheckNb = trim(strip_tags($_POST["sCheckNb"]));
         if (in_array($PaymentMode, $CONF_PAYMENTS_MODES_BANK_REQUIRED))
         {
             if (($BankID <= 0) || (empty($sCheckNb)))
             {
                 $ContinueProcess = FALSE;
             }
         }

         $fAmount = (float)trim(strip_tags($_POST["fAmount"]));
         if ((empty($fAmount)) || ($fAmount <= 0.0))
         {
             $ContinueProcess = FALSE;
         }

         // We have to convert the payment receipt date in english format (format used in the database)
         $PaymentReceiptDate = nullFormatText(formatedDate2EngDate($_POST["paymentDate"]), "NULL");
         if (empty($PaymentReceiptDate))
         {
             $ContinueProcess = FALSE;
         }

         // To take into account the selected bills ID if the field is in the form
         $ArraySelectedBillID = array();
         if ((isset($_POST["lmBillID"])) && (!empty($_POST["lmBillID"])))
         {
             // We use the selected bills
             $ArraySelectedBillID = $_POST["lmBillID"];
         }
         elseif ((isset($ArrayBills["BillID"])) && (!empty($ArrayBills["BillID"])))
         {
             // We use the bills linked to the payment
             $ArraySelectedBillID = $ArrayBills["BillID"];
         }

         $ManualBillPartAmount = NULL;
         if ((!empty($ArraySelectedBillID)) && ($CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT))
         {
             // Get the part amount of the payment to allocate to the selected bill
             if (is_array($ArraySelectedBillID))
             {
                 // There is several linked bills
                 $CurrentAllocatedAmount = 0.0;
                 foreach($_POST as $Fname => $CurrentFieldValue)
                 {
                     if (stripos($Fname, "fBillPartAmount") !== FALSE)
                     {
                         $ArrayTmp = explode('_', $Fname);  // Fieldname_BillID
                         $TmpBillID = $ArrayTmp[1];
                         unset($ArrayTmp);

                         $fTmpValue = (float)trim(strip_tags($_POST[$Fname]));
                         if (!empty($fTmpValue))
                         {
                             $ManualBillPartAmount[$TmpBillID] = $fTmpValue;
                             $CurrentAllocatedAmount += $fTmpValue;

                             // Part amount can't be = 0.0, can't be > amount to pay on the bill,
                             // can't be > total amount of the payment
                             if (($ManualBillPartAmount[$TmpBillID] <= 0.0) || ($CurrentAllocatedAmount > $fAmount))
                             {
                                 $ContinueProcess = FALSE;
                             }
                         }
                     }
                 }
             }
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // Get previous amount of the payment
             $RecordOldPayment = getTableRecordInfos($DbCon, "Payments", $PaymentID);

             // Check if the payment is unique
             if (isUniquePayment($DbCon, $FamilyID, $BankID, $sCheckNb, $PaymentID))
             {
                 // Update the payment
                 $PaymentID = dbUpdatePayment($DbCon, $PaymentID, NULL, $PaymentReceiptDate, $FamilyID, $fAmount, $PaymentType,
                                              $PaymentMode, $sCheckNb, $BankID);
                 if ($PaymentID != 0)
                 {
                     // Log event
                     logEvent($DbCon, EVT_PAYMENT, EVT_SERV_PAYMENT, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $PaymentID);

                     // Change the balance of the family
                     $fOldPaymentAmount = $RecordOldPayment['PaymentAmount'];
                     $fUpdatePaymentAmount = 0.00;

                     if ($fOldPaymentAmount != $fAmount)
                     {
                         // Old payment amount and new amount are different (payment type are the same)
                         $fUpdatePaymentAmount = $fAmount - $fOldPaymentAmount;

                         switch($PaymentType)
                         {
                             case 0:
                                 // Payment type is annual contribution : we update the amount
                                 $fNewAnnualContributionBalance = updateFamilyAnnualContributionBalance($DbCon, $FamilyID, $fUpdatePaymentAmount);
                                 break;

                             case 1:
                             default:
                                 // Payment type is bill or other : we update the amount
                                 $fNewBalance = updateFamilyBalance($DbCon, $FamilyID, $fUpdatePaymentAmount);
                                 break;
                         }
                     }

                     // Change the links between bills and the payment
                     // Make diff between the 2 sets of bills :
                     // * $ArrayBills contains current bills linked to the payment
                     // * $ArraySelectedBillID contains bills will are linked to the payment
                     $ArrayEvalBillsPayments = array($PaymentID);

                     if (isset($ArrayBills['BillID']))
                     {
                         foreach($ArraySelectedBillID as $b => $CurrentNewBillID)
                         {
                             // The current selected bill is already linked to the payment?
                             if (!in_array($CurrentNewBillID, $ArrayBills['BillID']))
                             {
                                 // No, so we create the link
                                 $TmpPartAmount = NULL;
                                 if (isset($ManualBillPartAmount[$CurrentNewBillID]))
                                 {
                                     $TmpPartAmount = $ManualBillPartAmount[$CurrentNewBillID];
                                 }

                                 dbAddPaymentBill($DbCon, $PaymentID, $CurrentNewBillID, $TmpPartAmount);
                             }
                         }

                         foreach($ArrayBills['BillID'] as $b => $CurrentOldBillID)
                         {
                             // The current linked bill is still linked to the payment?
                             if (!in_array($CurrentOldBillID, $ArraySelectedBillID))
                             {
                                 // No, so we remove the link
                                 dbRemovePaymentBill($DbCon, $PaymentID, $CurrentOldBillID);
                             }
                         }
                     }
                     else
                     {
                         foreach($ArraySelectedBillID as $b => $CurrentNewBillID)
                         {
                             // The current selected bill must be linked to the payment
                             // so we create the link
                             $TmpPartAmount = NULL;
                             if (isset($ManualBillPartAmount[$CurrentNewBillID]))
                             {
                                 $TmpPartAmount = $ManualBillPartAmount[$CurrentNewBillID];
                             }

                             dbAddPaymentBill($DbCon, $PaymentID, $CurrentNewBillID, $TmpPartAmount);
                         }
                     }

                     // Set the "Paid" flag of the bill(s) to 1 if the payment is for a bill
                     if (!empty($ArraySelectedBillID))
                     {
                         // Update context
                         dbSetBillsPaid($DbCon, $PaymentID, $fUpdatePaymentAmount, NULL, $ManualBillPartAmount);
                     }

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
             else
             {
                 // The payment can't be updated because it already exists (not unique)
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_NOT_UNIQUE;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "UpdatePayment.php?".$QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($PaymentReceiptDate))
             {
                 // The date is empty
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_DATE;
             }
             elseif ((empty($fAmount)) || ($fAmount <= 0))
             {
                 // Wrong amount
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_AMOUNT;
             }
             elseif ((in_array($PaymentMode, $CONF_PAYMENTS_MODES_BANK_REQUIRED)) && ($BankID <= 0))
             {
                 // No selected bank
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_BANK;
             }
             elseif ((in_array($PaymentMode, $CONF_PAYMENTS_MODES_BANK_REQUIRED)) && (empty($sCheckNb)))
             {
                 // No check number
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_CHECK_NB;
             }
             elseif ($CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT)
             {
                 // There is a pb with the reparition of the part amounts of a payment linked to bills
                 $ConfirmationSentence = $LANG_ERROR_PAYMENT_BILLS_PART_AMOUNTS;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "UpdatePayment.php?".$QUERY_STRING; // For the redirection
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