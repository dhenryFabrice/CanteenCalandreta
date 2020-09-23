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
 * Interface module : XHTML Graphic high level forms library used to manage the bills and payments.
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2012-01-27
 */


/**
 * Display the form to submit a new payment or update a payment, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 2.3
 *     - 2013-01-21 : allow to add/remove one or several bills to the payment, not allow
 *                    to change the type of payment after it's created and taken into account
 *                    the new field PaymentReceiptDate
 *     - 2013-11-22 : display the amount of the payment affected to each linked bill
 *     - 2013-12-16 : taken into account the "PaymentUsedAmount" field
 *     - 2015-01-19 : display the not used amount and the total of paid amounts of bills, display
 *                    a button to reset affectation of payment to bills, display warning about
 *                    the used amount of the payment. Don't display warning about removing a payment
 *                    with a part amount == 0.00 of a bill with an amount == 0.00
 *     - 2016-06-20 : remove htmlspecialchars() function
 *     - 2016-10-12 : allow to force the part amount of a payment linked to a bill (CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT)
 *     - 2019-11-08 : calendar has a unique callback function (calendarCallback) and has input field and $CONF_LANG
 *                    in parameter. The supporter can't select another payment type (select field removed, hidden field instead !)
 *     - 2020-01-30 : display the remaining amount of a not paid bill in the list to link bills to a payment
 *     - 2020-07-01 : taken into account $CONF_DEFAULT_VALUES_SET to set default values
 *
 * @since 2012-01-27
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $PaymentID                String                ID of the payment to display [0..n]
 * @param $FamilyID                 String                ID of the family of the payment [1..n]
 * @param $Type                     String                Default payment type [0..n]
 * @param $BillID                   String                ID of the bill linked to the payment [1..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update payments
 */
 function displayDetailsPaymentForm($DbConnection, $PaymentID, $FamilyID, $Type, $BillID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a payment
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($PaymentID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsPayment", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "VerificationPayment('".$GLOBALS["LANG_ERROR_JS_PAYMENT_DATE"]."', '".$GLOBALS["LANG_ERROR_JS_PAYMENT_AMOUNT"]."', '".$GLOBALS["LANG_ERROR_JS_PAYMENT_BANK"]."', '".$GLOBALS["LANG_ERROR_JS_PAYMENT_CHECK_NB"]."')");

             // <<< Payment ID >>>
             if ($PaymentID == 0)
             {
                 $Reference = "&nbsp;";

                 $PaymentModeDefaultValue = 0;
                 if (isset($GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['PaymentMode']))
                 {
                     $PaymentModeDefaultValue = $GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['PaymentMode'];
                 }

                 $PaymentRecord = array(
                                        "PaymentDate" => date('Y-m-d H:i:s'),
                                        "PaymentReceiptDate" => date('Y-m-d'),
                                        "PaymentType" => $Type,
                                        "PaymentMode" => $PaymentModeDefaultValue,
                                        "PaymentCheckNb" => "",
                                        "PaymentAmount" => "",
                                        "PaymentUsedAmount" => 0.00,
                                        "FamilyID" => $FamilyID,
                                        "BankID" => NULL
                                       );
             }
             else
             {
                 if (isExistingPayment($DbConnection, $PaymentID))
                 {
                     // We get the details of the payment
                     $PaymentRecord = getTableRecordInfos($DbConnection, "Payments", $PaymentID);
                     $Reference = $PaymentID;
                 }
                 else
                 {
                     // Error, the payment doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $PaymentRecord["FamilyID"]);

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_PAYMENT"]." ".$GLOBALS['LANG_FAMILY']." ".$FamilyRecord['FamilyLastname'],
                             "Frame", "Frame", "DetailsNews");

             unset($FamilyRecord);

             // <<< Payment date value >>>
             $PaymentDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                 strtotime($PaymentRecord["PaymentDate"]));

             // <<< Payment receipt date INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     if (!empty($PaymentRecord["PaymentReceiptDate"]))
                     {
                         $PaymentReceiptDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($PaymentRecord["PaymentReceiptDate"]));
                     }
                     else
                     {
                         $PaymentReceiptDate = '-';
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if (empty($PaymentRecord["PaymentReceiptDate"]))
                     {
                         $PaymentDateValue = '';
                     }
                     else
                     {
                         $PaymentDateValue = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($PaymentRecord["PaymentReceiptDate"]));
                     }

                     $PaymentReceiptDate = generateInputField("paymentDate", "text", "10", "10", $GLOBALS["LANG_PAYMENT_DATE_TIP"],
                                                              $PaymentDateValue, TRUE);

                     // Insert the javascript to use the calendar component
                     $PaymentReceiptDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t PaymentDateCalendar = new dynCalendar('PaymentDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'paymentDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                     break;
             }

             // <<< Payment type SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_UPDATE:
                     // No allowed to change the type after the payment is created (because it's not possible
                     // thank to GUI to remove links between the payment and bills
                     $PaymentType = $GLOBALS["CONF_PAYMENTS_TYPES"][$PaymentRecord["PaymentType"]];
                     $PaymentType .= generateInputField("lPaymentType", "hidden", "", "", "", $PaymentRecord["PaymentType"]);
                     break;

                 case FCT_ACT_CREATE:
                     /*$PaymentType = generateSelectField("lPaymentType", array_keys($GLOBALS["CONF_PAYMENTS_TYPES"]),
                                                        $GLOBALS["CONF_PAYMENTS_TYPES"], $PaymentRecord["PaymentType"], ""); */
                     $PaymentType = $GLOBALS["CONF_PAYMENTS_TYPES"][$PaymentRecord["PaymentType"]];
                     $PaymentType .= generateInputField("lPaymentType", "hidden", "", "", "", $PaymentRecord["PaymentType"]);
                     break;
             }

             // <<< Banks SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     if (!empty($PaymentRecord["BankID"]))
                     {
                         $RecordBank = getTableRecordInfos($DbConnection, "Banks", $PaymentRecord["BankID"]);
                         $Bank = $RecordBank['BankName'];
                         unset($RecordBank);
                     }
                     else
                     {
                         $Bank = '-';
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $ArrayBanks = getTableContent($DbConnection, 'Banks', 'BankName');
                     $ArrayBankID = array(0);
                     $ArrayBankNames = array('');
                     if ((isset($ArrayBanks['BankID'])) && (!empty($ArrayBanks['BankID'])))
                     {
                         foreach($ArrayBanks['BankID'] as $b => $BankID)
                         {
                             $ArrayBankID[] = $BankID;
                             $ArrayBankNames[] = $ArrayBanks['BankName'][$b];
                         }
                     }

                     $Bank = generateSelectField("lBankID", $ArrayBankID, $ArrayBankNames, $PaymentRecord["BankID"], "");
                     break;
             }

             // <<< Payment mode SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $PaymentMode = $GLOBALS["CONF_PAYMENTS_MODES"][$PaymentRecord["PaymentMode"]];
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $PaymentMode = generateSelectField("lPaymentMode", array_keys($GLOBALS["CONF_PAYMENTS_MODES"]),
                                                        $GLOBALS["CONF_PAYMENTS_MODES"], $PaymentRecord["PaymentMode"], "");
                     break;
             }

             // <<< Check number INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $CheckNb = stripslashes($PaymentRecord["PaymentCheckNb"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $CheckNb = generateInputField("sCheckNb", "text", "30", "20", $GLOBALS["LANG_PAYMENT_CHECK_NB_TIP"],
                                                   $PaymentRecord["PaymentCheckNb"]);
                     break;
             }

             // <<< Amount INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $Amount = stripslashes($PaymentRecord["PaymentAmount"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Amount = generateInputField("fAmount", "text", "10", "10", $GLOBALS["LANG_PAYMENT_AMOUNT_TIP"],
                                                   $PaymentRecord["PaymentAmount"]);
                     break;
             }

             // Get bills paid by the payment
             $BillsOfPayment = "";
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                     if (!empty($BillID))
                     {
                         // Get infos about the bill
                         if (is_array($BillID))
                         {
                             $ArrayParamsTmp = array("BillID" => $BillID);
                         }
                         else
                         {
                             $ArrayParamsTmp = array("BillID" => array($BillID));
                         }

                         $ArrayBills = getBills($DbConnection, NULL, NULL, 'BillForDate', NO_DATES, $ArrayParamsTmp);
                         if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
                         {
                             $BillsOfPayment = "<dl class=\"BillListForPayment\">\n<dt>".$GLOBALS['LANG_PAYMENT_LINKED_BILLS']."</dt>\n";
                             foreach($ArrayBills['BillID'] as $b => $CurrentBillID)
                             {
                                 // We check if the bill is paid
                                 $BillCaption = "";
                                 if ($ArrayBills["BillPaid"][$b] == 1)
                                 {
                                     // The bill is Paid
                                     $BillCaption = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                          $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                                 }

                                 $Month = date("m", strtotime($ArrayBills["BillForDate"][$b]));
                                 $Year = date("Y", strtotime($ArrayBills["BillForDate"][$b]));

                                 $BillCaption .= $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";

                                 // Compute the amount of the bill
                                 $BillAmount = $ArrayBills["BillAmount"][$b];
                                 $BillCaption .= " : $BillAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'];

                                 if ($GLOBALS['CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT'])
                                 {
                                     // Allow to enter manualy the part amount of the payment allocated to the selected bill
                                     $BillCaption .= " / ".$GLOBALS['LANG_PAYMENT_BILL_PART_AMOUNT_TO_ALLOCATED']
                                                     .generateInputField("fBillPartAmount_$CurrentBillID", "text", "10", "10",
                                                                         $GLOBALS["LANG_PAYMENT_BILL_PART_AMOUNT_TO_ALLOCATED_TIP"], "")
                                                     ." ".$GLOBALS['CONF_PAYMENTS_UNIT'];
                                 }

                                 $BillsOfPayment .= "<dd>$BillCaption</dd>\n";
                             }

                             $BillsOfPayment .= "</dl>\n";
                         }
                     }
                     break;

                 case FCT_ACT_UPDATE:
                     // We check if the payment is completely used
                     $BillsOfPayment = '';
                     $fNotUsedAmount = round($PaymentRecord["PaymentAmount"] - $PaymentRecord["PaymentUsedAmount"], 2);
                     if ($fNotUsedAmount == 0.00)
                     {
                         // Yes, the payment is completely used
                         $BillsOfPayment = "<p>".generateStyledText($GLOBALS['LANG_PAYMENT_COMPLETED'], 'CompletedPayment')."</p>";
                     }

                     // Get bills of the payment
                     $ArrayBills = getBillsOfPayment($DbConnection, $PaymentID, array(), 'BillForDate');

                     if (isset($ArrayBills['BillID']))
                     {
                         $fTotalPaidBillAmounts = 0.00;
                         $BillsOfPayment .= "<dl class=\"BillListForPayment\">\n<dt>".$GLOBALS['LANG_PAYMENT_LINKED_BILLS']."</dt>\n";

                         if (!empty($ArrayBills['BillID']))
                         {
                             foreach($ArrayBills['BillID'] as $b => $CurrentBillID)
                             {
                                 // We check if the bill is paid
                                 $BillCaption = "";
                                 if ($ArrayBills["BillPaid"][$b] == 1)
                                 {
                                     // The bill is Paid
                                     $BillCaption = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                          $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                                 }

                                 $Month = date("m", strtotime($ArrayBills["BillForDate"][$b]));
                                 $Year = date("Y", strtotime($ArrayBills["BillForDate"][$b]));

                                 $BillCaption .= $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";

                                 // Compute the amount of the bill
                                 $BillAmount = getBillAmount($DbConnection, $CurrentBillID);
                                 $BillCaption .= " : $BillAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'];

                                 // Compute the part of amount of the payment affected to this bill
                                 $sProgressBar = generateProgressVisualIndicator(NULL, $ArrayBills['PaymentAmount'][$b],
                                                                                 $ArrayBills['PaymentBillPartAmount'][$b],
                                                                                 max(0, $ArrayBills['PaymentAmount'][$b] - $ArrayBills['PaymentBillPartAmount'][$b]),
                                                                                 $GLOBALS['LANG_PAYMENT_BILL_PART_AMOUNT_TIP']." "
                                                                                 .$ArrayBills['PaymentBillPartAmount'][$b]." "
                                                                                 .$GLOBALS['CONF_PAYMENTS_UNIT']);

                                 // Display the amount of the payment affected to the bill
                                 if ($GLOBALS['CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT'])
                                 {
                                     // Allow to enter manualy the part amount of the payment allocated to the selected bill
                                     $sProgressBar .= " / "
                                                      .generateInputField("fBillPartAmount_$CurrentBillID", "text", "10", "10",
                                                                          $GLOBALS["LANG_PAYMENT_BILL_PART_AMOUNT_TO_ALLOCATED_TIP"],
                                                                          $ArrayBills['PaymentBillPartAmount'][$b])
                                                      ." ".$GLOBALS['CONF_PAYMENTS_UNIT'];
                                 }
                                 else
                                 {
                                     $sProgressBar .= " / ".$ArrayBills['PaymentBillPartAmount'][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                                 }

                                 $fTotalPaidBillAmounts += $ArrayBills['PaymentBillPartAmount'][$b];

                                 // We check if the payment must be remove of the bill because of a part amount = 0.00
                                 // Display this warning only for Bill with an amount <> 0.00 !
                                 if (($ArrayBills['PaymentBillPartAmount'][$b] == 0.00) && ($BillAmount != 0.00))
                                 {
                                     $sProgressBar .= "&nbsp;&nbsp;".generateStyledText("< ".$GLOBALS['LANG_PAYMENT_TO_REMOVE_OF_THIS_BILL'],
                                                                                        'PaymentToRemoveOfBill');

                                 }

                                 $BillsOfPayment .= "<dd>$BillCaption&nbsp;&nbsp;&nbsp;&nbsp;$sProgressBar</dd>\n";
                             }
                         }
                         else
                         {
                             // No linked bill
                             $BillsOfPayment .= "<dd>".$GLOBALS['LANG_PAYMENT_NO_LINKED_BILL']."</dd>\n";
                         }

                         $BillsOfPayment .= "</dl>\n";

                         // Display the total paid amounts of bills and the used amount of the payment (must be equal !)
                         $BillsOfPayment .= "<p><strong>".$GLOBALS['LANG_TOTAL']." : ".round($PaymentRecord["PaymentUsedAmount"], 2)." "
                                            .$GLOBALS['CONF_PAYMENTS_UNIT']."</strong>";

                         // Check if the 2 amounts are equals
                         if (round($fTotalPaidBillAmounts, 3) != round($PaymentRecord["PaymentUsedAmount"], 3))
                         {
                             // The 2 amounts are differents : display a warning !
                             // We display a warning with the total computed used amount
                             $BillsOfPayment .= "&nbsp;".generateStyledPicture($GLOBALS['CONF_WARNING_ICON'],
                                                                               round($fTotalPaidBillAmounts, 2)
                                                                               ." ".$GLOBALS['CONF_PAYMENTS_UNIT']." !", '');
                         }
                         elseif (round($PaymentRecord["PaymentUsedAmount"], 3) > round($PaymentRecord["PaymentAmount"], 3))
                         {
                             // The used amount is > the amount of the payment
                             // We display a warning with the payment amount
                             $BillsOfPayment .= "&nbsp;".generateStyledPicture($GLOBALS['CONF_WARNING_ICON'],
                                                                               round($PaymentRecord["PaymentAmount"], 2)
                                                                               ." ".$GLOBALS['CONF_PAYMENTS_UNIT']." !", '');
                         }

                         $BillsOfPayment .= "</p>\n";

                         // Display the not used amount of the payment
                         $BillsOfPayment .= "<p><strong>".$GLOBALS['LANG_FAMILY_PAYMENT_NOT_TOTALY_USED']." : "
                                            .round($PaymentRecord["PaymentAmount"] - $PaymentRecord["PaymentUsedAmount"], 2)." "
                                            .$GLOBALS['CONF_PAYMENTS_UNIT']."</strong></p>\n";

                         // We display the bills not paid to allow the user to add or remove not paid bills to the payment
                         $ArrayNotPaidBills = getBills($DbConnection, NULL, NULL, 'BillForDate', NO_DATES,
                                                       array(
                                                             "FamilyID" => array($PaymentRecord["FamilyID"]),
                                                             "BillPaid" => array(0),
                                                             "IncludeBillID" => $ArrayBills['BillID']
                                                            ));

                         if ((isset($ArrayNotPaidBills['BillID'])) && (!empty($ArrayNotPaidBills['BillID'])))
                         {
                             $ArrayBillsCaptions = array();
                             foreach($ArrayNotPaidBills['BillID'] as $b => $BillID)
                             {
                                 $Month = date("m", strtotime($ArrayNotPaidBills["BillForDate"][$b]));
                                 $Year = date("Y", strtotime($ArrayNotPaidBills["BillForDate"][$b]));

                                 $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";

                                 // Compute the amount of the bill
                                 $BillAmount = $ArrayNotPaidBills['BillMonthlyContribution'][$b]
                                               + $ArrayNotPaidBills['BillCanteenAmount'][$b]
                                               + $ArrayNotPaidBills['BillWithoutMealAmount'][$b]
                                               + $ArrayNotPaidBills['BillNurseryAmount'][$b]
                                               - $ArrayNotPaidBills['BillDeposit'][$b];

                                 $BillCaption .= " : $BillAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'];

                                 // Check if the bill is paid
                                 if ($ArrayNotPaidBills['BillPaid'][$b] > 0)
                                 {
                                     // Bill paid
                                     $BillCaption .= " (*)";
                                 }
                                 else
                                 {
                                     // Bill not paid : we display the remaining amount to pay if different from the baill amount
                                     if ($ArrayNotPaidBills['BillPaidAmount'][$b] > 0)
                                     {
                                         $BillCaption .= " (--> ".($BillAmount - $ArrayNotPaidBills['BillPaidAmount'][$b])." ".$GLOBALS['CONF_PAYMENTS_UNIT'].")";
                                     }
                                 }

                                 $ArrayBillsCaptions[] = $BillCaption;
                             }

                             // By default, we select the first bill if no selected bills
                             if (empty($ArrayBills['BillID']))
                             {
                                 $ArraySelectedBillID[] = $ArrayNotPaidBills['BillID'][0];
                             }
                             else
                             {
                                 $ArraySelectedBillID = $ArrayBills['BillID'];
                             }

                             $BillsOfPayment .= generateBR(1).$GLOBALS['LANG_SUPPORT_CREATE_PAYMENT_PAGE_SELECT_BILLS'].' ';
                             $BillsOfPayment .= generateMultipleSelectField("lmBillID", $ArrayNotPaidBills['BillID'],
                                                                            $ArrayBillsCaptions, 5, $ArraySelectedBillID, '');

                             // Display a button to reset affectation of the payment to bills
                             $BillsOfPayment .= "&nbsp;&nbsp;".generateStyledPictureHyperlink($GLOBALS["CONF_PAYMENT_RESET_ICON"],
                                                                                              "ResetPaymentBills.php?Cr=".md5($PaymentID)."&amp;Id=$PaymentID",
                                                                                              $GLOBALS["LANG_PAYMENT_RESET_TIP"],
                                                                                              'Affectation');
                         }
                         else
                         {
                             // No bill not paid
                             $BillsOfPayment .= generateBR(1).$GLOBALS['LANG_SUPPORT_CREATE_PAYMENT_PAGE_NO_BILL'];
                         }

                         $BillsOfPayment .= generateBR(3);
                     }
                     else
                     {
                         // No linked bill
                         $BillsOfPayment = $GLOBALS['LANG_PAYMENT_NO_LINKED_BILL'];
                     }
                     break;

                 case FCT_ACT_READ_ONLY:
                     // We check if the payment is completely used
                     $ArrayUsedAmount = getPaymentProgress($DbConnection, $PaymentID);
                     $BillsOfPayment = '';
                     if ((count($ArrayUsedAmount) == 2) && ($ArrayUsedAmount[1] == 0.00))
                     {
                         // Yes, the payment is completely used
                         $BillsOfPayment = "<p>".generateStyledText($GLOBALS['LANG_PAYMENT_COMPLETED'], 'CompletedPayment')."</p>";
                     }

                     // Get bills of the payment
                     $ArrayBills = getBillsOfPayment($DbConnection, $PaymentID, array(), 'BillForDate');

                     if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
                     {
                         $fTotalPaidBillAmounts = 0.00;
                         $BillsOfPayment .= "<dl class=\"BillListForPayment\">\n<dt>".$GLOBALS['LANG_PAYMENT_LINKED_BILLS']."</dt>\n";
                         foreach($ArrayBills['BillID'] as $b => $CurrentBillID)
                         {
                             // We check if the bill is paid
                             $BillCaption = "";
                             if ($ArrayBills["BillPaid"][$b] == 1)
                             {
                                 // The bill is Paid
                                 $BillCaption = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                      $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                             }

                             $Month = date("m", strtotime($ArrayBills["BillForDate"][$b]));
                             $Year = date("Y", strtotime($ArrayBills["BillForDate"][$b]));

                             $BillCaption .= $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";

                             // Compute the amount of the bill
                             $BillAmount = getBillAmount($DbConnection, $CurrentBillID);
                             $BillCaption .= " : $BillAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'];

                             // Compute the part of amount of the payment affected to this bill
                             $sProgressBar = generateProgressVisualIndicator(NULL, $ArrayBills['PaymentAmount'][$b],
                                                                             $ArrayBills['PaymentBillPartAmount'][$b],
                                                                             max(0, $ArrayBills['PaymentAmount'][$b] - $ArrayBills['PaymentBillPartAmount'][$b]),
                                                                             $GLOBALS['LANG_PAYMENT_BILL_PART_AMOUNT_TIP']." "
                                                                             .$ArrayBills['PaymentBillPartAmount'][$b]." "
                                                                             .$GLOBALS['CONF_PAYMENTS_UNIT']);

                             // Display the amount of the payment affected to the bill
                             $sProgressBar .= " / ".$ArrayBills['PaymentBillPartAmount'][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                             $fTotalPaidBillAmounts += $ArrayBills['PaymentBillPartAmount'][$b];

                             $BillsOfPayment .= "<dd>$BillCaption&nbsp;&nbsp;&nbsp;&nbsp;$sProgressBar</dd>\n";
                         }

                         $BillsOfPayment .= "</dl>\n";

                         // Display the total paid amounts of bills
                         $BillsOfPayment .= "<p><strong>".$GLOBALS['LANG_TOTAL']." : ".round($fTotalPaidBillAmounts, 2)." "
                                            .$GLOBALS['CONF_PAYMENTS_UNIT']."</strong></p>\n";

                         // Display the not used amount of the payment
                         $BillsOfPayment .= "<p><strong>".$GLOBALS['LANG_FAMILY_PAYMENT_NOT_TOTALY_USED']." : "
                                            .round($PaymentRecord["PaymentAmount"] - $PaymentRecord["PaymentUsedAmount"], 2)." "
                                            .$GLOBALS['CONF_PAYMENTS_UNIT']."</strong></p>\n";
                     }
                     else
                     {
                         // No linked bill
                         $BillsOfPayment = $GLOBALS['LANG_PAYMENT_NO_LINKED_BILL'];
                     }
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference ($PaymentDate)</td><td class=\"Label\">".$GLOBALS["LANG_PAYMENT_DATE"]."*</td><td class=\"Value\">$PaymentReceiptDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_PAYMENT_TYPE"]."</td><td class=\"Value\">$PaymentType</td><td class=\"Label\">".$GLOBALS["LANG_PAYMENT_AMOUNT"]."*</td><td class=\"Value\">$Amount ".$GLOBALS["CONF_PAYMENTS_UNIT"]."</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_PAYMENT_MODE"]."</td><td class=\"Value\">$PaymentMode</td><td class=\"Label\">&nbsp;</td><td class=\"Value\">&nbsp;</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_BANK"]."</td><td class=\"Value\">$Bank</td><td class=\"Label\">".$GLOBALS["LANG_PAYMENT_CHECK_NB"]."</td><td class=\"Value\">$CheckNb</td>\n</tr>\n";
             echo "</table>\n";

             closeStyledFrame();

             insertInputField("hidFamilyID", "hidden", "", "", "", $PaymentRecord["FamilyID"]);

             if ((!empty($BillsOfPayment)) && ($PaymentRecord['PaymentType'] == 1))
             {
                 echo $BillsOfPayment;
             }

             if (!empty($BillID))
             {
                 // New payemnt for a bill
                 $BillIDValue = $BillID;
                 if (is_array($BillID))
                 {
                     $BillIDValue = implode(',', $BillIDValue);
                 }

                 insertInputField("hidBillID", "hidden", "", "", "", $BillIDValue);
             }

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // Display buttons to save modifications and link to create a new bank
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";

                     echo generateBR(2);
                     openParagraph('InfoMsg');
                     echo generateCryptedHyperlink($GLOBALS['LANG_SUPPORT_UPDATE_BANK_PAGE_CREATE_BANK'], '', 'AddBank.php',
                                                   $GLOBALS["LANG_SUPPORT_UPDATE_BANK_PAGE_CREATE_BANK_TIP"], '', '_blank');
                     closeParagraph();
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a payment
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to submit a new bank or update a bank, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-06-20 : remove htmlspecialchars() function

 * @since 2012-01-28
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $BankID                   String                ID of the bank to display [0..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update banks
 */
 function displayDetailsBankForm($DbConnection, $BankID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a bank
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($BankID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsBank", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "VerificationBank('".$GLOBALS["LANG_ERROR_JS_BANK_NAME"]."')");

             // <<< Bank ID >>>
             if ($BankID == 0)
             {
                 $Reference = "&nbsp;";
                 $BankRecord = array(
                                     "BankName" => ""
                                    );
             }
             else
             {
                 if (isExistingBank($DbConnection, $BankID))
                 {
                     // We get the details of the bank
                     $BankRecord = getTableRecordInfos($DbConnection, "Banks", $BankID);
                     $Reference = $BankID;
                 }
                 else
                 {
                     // Error, the bank doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_BANK"], "Frame", "Frame", "DetailsNews");

             // <<< bank name INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $Name = stripslashes($BankRecord["BankName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Name = generateInputField("sName", "text", "50", "30", $GLOBALS["LANG_BANK_NAME_TIP"], $BankRecord["BankName"]);
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_BANK_NAME"]."*</td><td class=\"Value\">$Name</td>\n</tr>\n";
             echo "</table>\n";

             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";

                     // Display the link to create a new bank
                     if (!empty($BankID))
                     {
                         echo generateBR(2);
                         openParagraph('InfoMsg');
                         echo generateCryptedHyperlink($GLOBALS['LANG_SUPPORT_UPDATE_BANK_PAGE_CREATE_BANK'], '', 'AddBank.php',
                                                       $GLOBALS["LANG_SUPPORT_UPDATE_BANK_PAGE_CREATE_BANK_TIP"], '', '_blank');
                         closeParagraph();
                     }
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a bank
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to generate bills of the month for all families, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 2.1
 *     - 2013-12-18 : add a field to selected a family to generate its monthly bill and a button
 *                    to generate missed bills of some families (because of a technical problem)
 *     - 2020-03-02 : display canteen's prices and nursery's prices
 *
 * @since 2012-02-21
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month used to generate bills [1..12]
 * @param $Year                 Integer               Year used to generate bills
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update monthly bills
 * @param $bSendMail            Boolean               To send by mail each generated bill
 */
 function displayGenerateMonthlyBillsForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array(), $bSendMail = NULL)
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to generate bills of the month
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormGenerateMonthlyBills", "post", "$ProcessFormPage", "", "");

             // Display the months list to generate bills
             openParagraph('toolbar');

             // Get the min date of the bills
             $MinDate = getBillMinDate($DbConnection);
             if (empty($MinDate))
             {
                 // Last month if no bill in the database
                 $MinDate = date('Y-m', strtotime("last month"));
             }

             // First entry in the list
             $ArrayYearsMonths = array();
             $ArrayYearsMonthsLabels = array();

             // Generate the next years and months
             // We can't generate the bills for the current month or after except if the curernt month
             // is set in $CONF_BILLS_ALLOW_BILL_FOR_CURRENT_MONTHS
             if (in_array((integer)date('m'), $GLOBALS['CONF_BILLS_ALLOW_BILL_FOR_CURRENT_MONTHS']))
             {
                 $MaxDate = date('Y-m-t');
             }
             else
             {
                 $MaxDate = date('Y-m-t', strtotime("last month"));
             }

             $ArrayYearsMonthsTmp = array_keys(getPeriodIntervalsStats($MinDate, $MaxDate, "m"));
             foreach($ArrayYearsMonthsTmp as $p => $Period)
             {
                 $ArrayTmp = explode('-', $Period);
                 $CurrentYear = $ArrayTmp[0];
                 $CurrentMonth = (integer)$ArrayTmp[1];

                 $ArrayYearsMonths[] = $Period;
                 $ArrayYearsMonthsLabels[] = "$CurrentYear - ".$GLOBALS['CONF_PLANNING_MONTHS'][$CurrentMonth - 1];

                 unset($ArrayTmp);
             }

             echo generateSelectField("lYearMonth", $ArrayYearsMonths, $ArrayYearsMonthsLabels, "$Year-$Month", "onChangeSelectedYearMonth(this.value)");

             // Display the list of activated families for the given month/year
             $SchoolYear = getSchoolYear(date('Y-m-t', strtotime("$Year-$Month-01")));
             $ArrayFamilies = dbSearchFamily($DbConnection, array(
                                                                  'SchoolYear' => array($SchoolYear)
                                                                 ), "FamilyLastname", 1, 0);

             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
             {
                 echo "&nbsp;".generateSelectField("lFamilyID", array_merge(array(0), $ArrayFamilies['FamilyID']),
                                                   array_merge(array(''), $ArrayFamilies['FamilyLastname']), 0, "");
             }

             closeParagraph();

             // Display prices used to compute the bills
             openParagraph();
             echo "<ul>";
             echo "<li>".$GLOBALS['LANG_CANTEEN']." : ".$GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYear][0].$GLOBALS['CONF_PAYMENTS_UNIT']." / "
                                                       .$GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYear][1].$GLOBALS['CONF_PAYMENTS_UNIT'];
             echo "<li>".$GLOBALS['LANG_NURSERY']." ".$GLOBALS['LANG_AM']." : ".$GLOBALS['CONF_NURSERY_PRICES'][$SchoolYear][0].$GLOBALS['CONF_PAYMENTS_UNIT'];

             if ((isset($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$SchoolYear]))
                 && (!empty($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$SchoolYear]))
                 && (isset($GLOBALS['CONF_NURSERY_PRICES'][$SchoolYear]['OtherTimeslots']))
                 && (!empty($GLOBALS['CONF_NURSERY_PRICES'][$SchoolYear]['OtherTimeslots'])))
             {
                 // Display prices of other timeslots of the nursery if they exist
                 foreach($GLOBALS['CONF_NURSERY_PRICES'][$SchoolYear]['OtherTimeslots'] as $ots => $CurrentOTSPrice)
                 {
                     echo "<li>".$GLOBALS['LANG_NURSERY']." ".$GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$SchoolYear][$ots]['Label']." : $CurrentOTSPrice".$GLOBALS['CONF_PAYMENTS_UNIT'];
                 }
             }

             echo "<li>".$GLOBALS['LANG_NURSERY']." ".$GLOBALS['LANG_PM']." : ".$GLOBALS['CONF_NURSERY_PRICES'][$SchoolYear][1].$GLOBALS['CONF_PAYMENTS_UNIT'];
             echo "</ul>";
             closeParagraph();

             displayBR();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bGenerate", "submit", "", "", $GLOBALS["LANG_BILL_GENERATE_BUTTON_TIP"], $GLOBALS["LANG_BILL_GENERATE_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bMissingBills", "submit", "", "", $GLOBALS["LANG_BILL_GENERATE_MISSING_BUTTON_TIP"], $GLOBALS["LANG_BILL_GENERATE_MISSING_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";

             if (is_null($bSendMail))
             {
                 $bSendMail = FALSE;
                 if (($GLOBALS['CONF_BILLS_FAMILIES_SEND_NOTIFICATION_BY_DEFAULT'])
                     && (!empty($GLOBALS['CONF_BILLS_FAMILIES_NOTIFICATION'])))
                 {
                     $bSendMail = TRUE;
                 }
             }

             insertInputField("chkSendMail", "checkbox", "", "", $GLOBALS["LANG_BILL_CHECK_SEND_MAIL_TIP"], "sendmail",
                              FALSE, $bSendMail);
             echo " ".$GLOBALS["LANG_BILL_CHECK_SEND_MAIL"];
             echo "</td>\n</tr>\n</table>\n";

             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to re-send by mails bills of the month to all families, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-12-16
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month used to generate bills [1..12]
 * @param $Year                 Integer               Year used to generate bills
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to send by mail monthly bills
 */
 function displayResendMonthlyBillsForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to send by mail bills of the month
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormResendByMailMonthlyBills", "post", "$ProcessFormPage", "", "");

             echo "<hr class=\"Sep2Forms\"/>";

             openParagraph('InfoMsg');
             echo $GLOBALS['LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_RESEND_BILLS']."<strong>".$GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year</strong>.";
             displayBR(3);
             insertInputField("bResendByMail", "submit", "", "", $GLOBALS["LANG_BILL_RESEND_BUTTON_TIP"],
                              $GLOBALS["LANG_BILL_RESEND_BUTTON_CAPTION"]);
             closeParagraph();

             insertInputField("lYearMonth", "hidden", "", "", "", "$Year-$Month");
             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_SEND_BILLS"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to re-regenerate the document containing all bills of the month,
 * in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-06-03
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month used to generate bills [1..12]
 * @param $Year                 Integer               Year used to generate bills
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to send by mail monthly bills
 */
 function displayRegenerateAllMonthlyBillsSynthesisForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to regenerate document containing all bills of the month
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormRegenerateMonthlyBillsDoc", "post", "$ProcessFormPage", "", "");

             echo "<hr class=\"Sep2Forms\"/>";

             openParagraph('InfoMsg');
             echo $GLOBALS['LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_REGENERATE_DOC']."<strong>".$GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year</strong>.";
             displayBR(3);
             insertInputField("bRegenerateSynthesisDoc", "submit", "", "", $GLOBALS["LANG_BILL_REGENERATE_DOC_BUTTON_TIP"],
                              $GLOBALS["LANG_BILL_REGENERATE_DOC_BUTTON_CAPTION"]);
             closeParagraph();

             insertInputField("lYearMonth", "hidden", "", "", "", "$Year-$Month");
             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_SEND_BILLS"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to generate bills of the year for all families, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-26
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Year                 Integer               Year used to generate bills
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update monthly bills
 * @param $bSendMail            Boolean               To send by mail each generated bill
 */
 function displayGenerateAnnualBillsForm($DbConnection, $ProcessFormPage, $Year, $AccessRules = array(), $bSendMail = NULL)
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to generate bills of the year
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormGenerateAnnualBills", "post", "$ProcessFormPage", "", "");

             // Display the months list to generate bills
             openParagraph('toolbar');

             // Get the min date of the bills
             $MinDate = getBillMinDate($DbConnection);
             if (empty($MinDate))
             {
                 // Current year
                 $MinDate = date('Y-m-d', strtotime("now"));
             }

             // First entry in the list
             $ArrayYears = array();
             $ArrayYearsLabels = array();

             // Generate the next years and months
             $MaxDate = date('Y-m-d', strtotime("now"));  // We can't generate the bills for after now!
             $ArrayYearsTmp = array_keys(getPeriodIntervalsStats($MinDate, $MaxDate, "Y"));
             foreach($ArrayYearsTmp as $p => $Period)
             {
                 $ArrayYears[] = $Period;
                 $ArrayYearsLabels[] = "$Period";

                 unset($ArrayTmp);
             }

             echo generateSelectField("lYear", $ArrayYears, $ArrayYearsLabels, "$Year", "onChangeSelectedYear(this.value)");

             closeParagraph();

             displayBR(2);

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bGenerate", "submit", "", "", $GLOBALS["LANG_BILL_GENERATE_BUTTON_TIP"], $GLOBALS["LANG_BILL_GENERATE_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";

             if (is_null($bSendMail))
             {
                 $bSendMail = FALSE;
                 if (($GLOBALS['CONF_BILLS_FAMILIES_SEND_ANNUAL_NOTIFICATION_BY_DEFAULT'])
                     && (!empty($GLOBALS['CONF_BILLS_FAMILIES_NOTIFICATION'])))
                 {
                     $bSendMail = TRUE;
                 }
             }

             insertInputField("chkSendMail", "checkbox", "", "", $GLOBALS["LANG_BILL_CHECK_SEND_MAIL_TIP"], "sendmail",
                              FALSE, $bSendMail);
             echo " ".$GLOBALS["LANG_BILL_CHECK_SEND_MAIL"];
             echo "</td>\n</tr>\n</table>\n";

             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to select bills of a family, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-21
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $FamilyID             Integer               Month used to generate bills [1..12]
 * @param $Year                 Integer               Year used to generate bills
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update monthly bills
 * @param $bSendMail            Boolean               To send by mail each generated bill
 */
 function displaySelectBillsOfFamilyForm($DbConnection, $ProcessFormPage, $FamilyID, $ArrayBillID = array(), $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to add a payment for bills of a family
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormAddPaymentBills", "post", "$ProcessFormPage", "", "");

             // Generate the list of families with balance < 0
             openParagraph('toolbar');

             $ArrayFamilyID = array(0);
             $ArrayFamilyNames = array('');

             $ArrayFamilies = dbSearchFamily($DbConnection, array("Activated" => TRUE, "PbPayments" => TRUE), "FamilyLastname", 1, 0);
             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
             {
                 $ArrayFamilyID = array_merge(array(0), $ArrayFamilies['FamilyID']);
                 $ArrayFamilyNames = array_merge(array(''), $ArrayFamilies['FamilyLastname']);
             }

             echo generateSelectField("lFamilyID", $ArrayFamilyID, $ArrayFamilyNames, $FamilyID, "onChangeSelectedFamily(this.value)");

             closeParagraph();

             // To select bills
             openParagraph();
             if (!empty($FamilyID))
             {
                 // We display the bills not paid
                 $ArrayBills = getBills($DbConnection, NULL, NULL, 'BillForDate', NO_DATES,
                                        array("FamilyID" => array($FamilyID), "BillPaid" => array(0)));

                 if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
                 {
                     $ArrayBillsCaptions = array();
                     foreach($ArrayBills['BillID'] as $b => $BillID)
                     {
                         $Month = date("m", strtotime($ArrayBills["BillForDate"][$b]));
                         $Year = date("Y", strtotime($ArrayBills["BillForDate"][$b]));

                         $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";

                         // Compute the amount of the bill
                         $BillAmount = $ArrayBills['BillMonthlyContribution'][$b] + $ArrayBills['BillCanteenAmount'][$b]
                                       + $ArrayBills['BillWithoutMealAmount'][$b] + $ArrayBills['BillNurseryAmount'][$b]
                                       - $ArrayBills['BillDeposit'][$b];

                         $BillCaption .= " : $BillAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'];

                         $ArrayBillsCaptions[] = $BillCaption;
                     }

                     // By default, we select the first bill if no selected bills
                     if (empty($ArrayBillID))
                     {
                         $ArrayBillID[] = $ArrayBills['BillID'][0];
                     }

                     echo $GLOBALS['LANG_SUPPORT_CREATE_PAYMENT_PAGE_SELECT_BILLS'].' ';
                     insertMultipleSelectField("lmBillID", $ArrayBills['BillID'], $ArrayBillsCaptions, 5, $ArrayBillID, '');
                 }
                 else
                 {
                     // No bill not paid
                     echo $GLOBALS['LANG_SUPPORT_CREATE_PAYMENT_PAGE_NO_BILL'];
                 }
             }

             closeParagraph();

             displayBR(2);

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the details of a bill (not editable), in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-16
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $BillID                   String                ID of the bill to display [1..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update children
 */
 function displayDetailsBillForm($DbConnection, $BillID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the bill
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((!empty($BillID)) && ($BillID > 0))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             printDetailsBillForm($DbConnection, $BillID, $ProcessFormPage);
         }
         else
         {
             // The supporter isn't allowed to view the bill
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_VIEW_BILL"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the payments synthesis of the selected month, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.5
 *     - 2012-10-01 : patch the bug of wrong number of activated children for the selected month and
 *                    wrong number of canteens if the canteen price changed after september
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2014-06-02 : replace an "integer" cast by a round() function and use the abs() function
 *     - 2015-01-16 : try to find the right price of canteen if not found in $CONF_CANTEEN_PRICES
 *     - 2017-11-07 : taken into account BillWithoutMealAmount and the right price
 *
 * @since 2012-03-22
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the payments synthesis
 */
 function displayPaymentsSynthesisForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array(), $DetailsPage = '')
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the synthesis
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormViewPaymentsSynthesis", "post", "$ProcessFormPage", "", "");

             // Display the months list to change the synthesis to display
             openParagraph('toolbar');
             echo generateStyledPictureHyperlink($GLOBALS["CONF_PRINT_BULLET"], "javascript:PrintWebPage()", $GLOBALS["LANG_PRINT"], "PictureLink", "");
             closeParagraph();

             // Display the weeks list : we get the older date of the canteen registrations
             openParagraph('toolbar');

             // Get the min date of the bills
             $MinDate = getBillMinDate($DbConnection);
             if (empty($MinDate))
             {
                 // Last month if no bill in the database
                 $MinDate = date('Y-m', strtotime("last month"));
             }

             // First entry in the list
             $ArrayYearsMonths = array();
             $ArrayYearsMonthsLabels = array();

             // Generate the next years and months
             $MaxDate = date('Y-m-t', strtotime("last month"));  // We can't generate the bills for the current month or after!

             // To patch a bug of PHP : sometimes, the last month = current month (ex : for 2012-05-31)
             $MaxDateYearMonth = date('Y-m', strtotime($MaxDate));
             if (date('Y-m') === $MaxDateYearMonth)
             {
                 // One month ago from yesterday
                 $MaxDate = date('Y-m-t', strtotime("last month", strtotime("1 day ago")));
             }

             $ArrayYearsMonthsTmp = array_keys(getPeriodIntervalsStats($MinDate, $MaxDate, "m"));
             foreach($ArrayYearsMonthsTmp as $p => $Period)
             {
                 $ArrayTmp = explode('-', $Period);
                 $CurrentYear = $ArrayTmp[0];
                 $CurrentMonth = (integer)$ArrayTmp[1];

                 $ArrayYearsMonths[] = $Period;
                 $ArrayYearsMonthsLabels[] = "$CurrentYear - ".$GLOBALS['CONF_PLANNING_MONTHS'][$CurrentMonth - 1];

                 unset($ArrayTmp);
             }

             echo generateSelectField("lYearMonth", $ArrayYearsMonths, $ArrayYearsMonthsLabels, "$Year-$Month", "onChangeSelectedYearMonth(this.value)");

             closeParagraph();

             displayBR(2);

             // Display the table containing concerned families with payments for this month
             // So we get bills of the selected month
             $StartDate = "$Year-$Month-01";
             $EndDate = date("Y-m-t", strtotime($StartDate));
             $SchoolYear = getSchoolYear($StartDate);
             $SchoolYearPrice = $SchoolYear;

             $ArrayBills = getBills($DbConnection, $StartDate, $EndDate, 'FamilyLastname', PLANNING_BETWEEN_DATES);
             if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
             {
                 // Captions of the table
                 $ArrayCaptions = array($GLOBALS['LANG_FAMILY'], $GLOBALS['LANG_NB_CHILDREN'], $GLOBALS['LANG_MONTHLY_CONTRIBUTION'],
                                        $GLOBALS['LANG_CANTEEN'], $GLOBALS['LANG_NURSERY'], $GLOBALS['LANG_BILL'],
                                        $GLOBALS['LANG_BILL_PREVIOUS_BALANCE'], $GLOBALS['LANG_FAMILY_BALANCE']);

                 // Data
                 $TabFamiliesData = array();
                 $bSchoolYearPriceChanged = FALSE;
                 foreach($ArrayBills['BillID'] as $b => $BillID)
                 {
                     // Family lastname
                     if (empty($DetailsPage))
                     {
                         $TabFamiliesData[0][] = $ArrayBills["FamilyLastname"][$b];
                     }
                     else
                     {
                         $TabFamiliesData[0][] = generateAowIDHyperlink($ArrayBills["FamilyLastname"][$b], $ArrayBills["FamilyID"][$b],
                                                                        $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                        "", "_blank");
                     }

                     // Get the number of children of the family
                     $iNbChildren = getNbdbSearchChild($DbConnection, array(
                                                                            'FamilyID' => $ArrayBills["FamilyID"][$b],
                                                                            'Activated' => TRUE,
                                                                            'SchoolYear' => array($SchoolYear)
                                                                           ));
                     $TabFamiliesData[1][] = $iNbChildren;

                     // Monthly contribution
                     $TabFamiliesData[2][] = $ArrayBills["BillMonthlyContribution"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                     // Nb canteen registrations and amount
                     // Get the number of canteen registrations
                     $iNbCanteenRegistrations = 0;
                     $iNbWithoutMeals = 0;
                     if (isset($GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice]))
                     {
                         $fPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                         $MinPrice = $fPrice;
                         $MaxPrice = $fPrice;

                         $fWithoutMealPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                         $MinWithoutMealPrice = $fWithoutMealPrice;
                         $MaxWithoutMealPrice = $fWithoutMealPrice;

                         $iNbCanteenRegistrations = $ArrayBills['BillCanteenAmount'][$b] / $fPrice;
                         $iNbWithoutMeals = $ArrayBills['BillWithoutMealAmount'][$b] / $fWithoutMealPrice;

                         // We do this verification only once time
                         // We use the round(x, 3) because with float, some results have the form x.yE-15
                         if ((!$bSchoolYearPriceChanged) && (round(abs($iNbCanteenRegistrations - round($iNbCanteenRegistrations)), 3) > 0))
                         {
                             // We must use the canteen price of the previous school year
                             $SchoolYearPrice--;
                             if (isset($GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice]))
                             {
                                 // We re-compute data for the bill
                                 $fPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                                 $MinPrice = $fPrice;
                                 $iNbCanteenRegistrations = $ArrayBills['BillCanteenAmount'][$b] / $fPrice;

                                 $fWithoutMealPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                                 $iNbWithoutMeals = $ArrayBills['BillWithoutMealAmount'][$b] / $fWithoutMealPrice;
                             }

                             $bSchoolYearPriceChanged = TRUE;

                             if (round(abs($iNbCanteenRegistrations - round($iNbCanteenRegistrations)), 3) > 0)
                             {
                                 // Try to find the right price
                                 $fFoundPrice = findExactDivisor($ArrayBills['BillCanteenAmount'][$b], $MinPrice, $MaxPrice, 0.01);
                                 if ($fFoundPrice > 0)
                                 {
                                     // Right price found
                                     $bSchoolYearPriceChanged = FALSE;
                                     $SchoolYearPrice++;
                                     $fPrice = $fFoundPrice;

                                     $iNbCanteenRegistrations = $ArrayBills['BillCanteenAmount'][$b] / $fPrice;
                                 }

                                 $fWithoutMealFoundPrice = findExactDivisor($ArrayBills['BillWithoutMealAmount'][$b], $MinWithoutMealPrice,
                                                                            $MaxWithoutMealPrice, 0.01);
                                 if ($fWithoutMealFoundPrice > 0)
                                 {
                                     // Right price found
                                     $fWithoutMealPrice = $fWithoutMealFoundPrice;
                                     $iNbWithoutMeals = $ArrayBills['BillWithoutMealAmount'][$b] / $fWithoutMealPrice;
                                 }
                             }
                         }
                     }

                     $TabFamiliesData[3][] = generateStyledText($ArrayBills["BillCanteenAmount"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT']
                                                                ." ($iNbCanteenRegistrations)", "CanteenWithMeal")
                                             ." / ".$ArrayBills["BillWithoutMealAmount"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT']
                                             ." ($iNbWithoutMeals)";

                     // Nb nursery registrations and amount
                     $TabFamiliesData[4][] = $ArrayBills["BillNurseryAmount"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                     // Total of the bill + "paid" flag
                     $Flag = '';
                     if ($ArrayBills["BillPaid"][$b] == 1)
                     {
                         // The bill is Paid
                         $Flag = " ".generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'], $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                     }

                     $TabFamiliesData[5][] = $ArrayBills['BillAmount'][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'].$Flag;

                     // Balance of the family for this bill
                     $BalanceAmount = -1.00 * $ArrayBills['BillPreviousBalance'][$b];
                     $BalanceStyle = '';
                     if ($BalanceAmount < 0)
                     {
                         $BalanceStyle = 'NegativeBalance';
                     }
                     elseif ($BalanceAmount > 0)
                     {
                         $BalanceStyle = 'PositiveBalance';
                     }

                     $TabFamiliesData[6][] = generateStyledText(sprintf("%01.2f", $BalanceAmount)." ".$GLOBALS['CONF_PAYMENTS_UNIT'],
                                                                $BalanceStyle);

                     // Current balance of the family
                     $CurrentBalanceAmount = getTableFieldValue($DbConnection, 'Families', $ArrayBills["FamilyID"][$b],
                                                                'FamilyBalance');

                     $BalanceStyle = '';
                     if ($CurrentBalanceAmount < 0)
                     {
                         $BalanceStyle = 'NegativeBalance';
                     }
                     elseif ($CurrentBalanceAmount > 0)
                     {
                         $BalanceStyle = 'PositiveBalance';
                     }

                     // Get nb of not paid bills of the family
                     $ArrayNotPaidBills = getBills($DbConnection, NULL, NULL, 'BillID', NO_DATES,
                                                   array("FamilyID" => array($ArrayBills["FamilyID"][$b]),
                                                         "BillPaid" => array(0)));

                     $iNbNotPaidBills = 0;
                     $sNbNotPaidBillsText = '';
                     if ((isset($ArrayNotPaidBills['BillID'])) && (!empty($ArrayNotPaidBills['BillID'])))
                     {
                         $iNbNotPaidBills = count($ArrayNotPaidBills['BillID']);
                         $sNbNotPaidBillsText = " ($iNbNotPaidBills)";
                     }

                     $TabFamiliesData[7][] = generateStyledText("$CurrentBalanceAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'], $BalanceStyle)
                                             .$sNbNotPaidBillsText;
                 }

                 displayStyledTable($ArrayCaptions, array_fill(0, count($ArrayCaptions), ''), '', $TabFamiliesData,
                                    'PaymentsSynthesisTable', '', '', '', array(), NULL, array(), 'PaymentsList');
             }

             insertInputField("hidYearWeek", "hidden", "", "", "", "$Year-$Month");  // Current selected year-month
             closeForm();

             // Open a form to print the payments synthesis
             openForm("FormPrintAction", "post", "$ProcessFormPage?lYearMonth=$Year-$Month", "", "");
             insertInputField("hidOnPrint", "hidden", "", "", "", "0");
             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to submit a new discount/increase or update a discount/increase, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-05
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $DiscountFamilyID         String                ID of the discount/increase to display [0..n]
 * @param $FamilyID                 String                ID of the family concerned by the discount/increase [1..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update discounts/increases
 */
 function displayDetailsDiscountFamilyForm($DbConnection, $DiscountFamilyID, $FamilyID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a discount/increase for a family
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($DiscountFamilyID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsDiscount", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationDiscountFamily('".$GLOBALS["LANG_ERROR_JS_DISCOUNT_AMOUNT"]."')");

             // <<< Discount family ID >>>
             if ($DiscountFamilyID == 0)
             {
                 $Reference = "&nbsp;";
                 $DiscountRecord = array(
                                         "DiscountFamilyDate" => date('Y-m-d H:i:s'),
                                         "DiscountFamilyType" => 0,
                                         "DiscountFamilyReasonType" => 0,
                                         "DiscountFamilyReason" => "",
                                         "DiscountFamilyAmount" => "",
                                         "FamilyID" => $FamilyID
                                        );
             }
             else
             {
                 if (isExistingDiscountFamily($DbConnection, $DiscountFamilyID))
                 {
                     // We get the details of the discount/increase
                     $DiscountRecord = getTableRecordInfos($DbConnection, "DiscountsFamilies", $DiscountFamilyID);
                     $Reference = $DiscountFamilyID;
                 }
                 else
                 {
                     // Error, the discount/increase doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $DiscountRecord["FamilyID"]);

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_DISCOUNT"]." ".$GLOBALS['LANG_FAMILY']." ".$FamilyRecord['FamilyLastname'],
                             "Frame", "Frame", "DetailsNews");

             unset($FamilyRecord);

             // <<< Discount date value >>>
             $DiscountFamilyDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                        strtotime($DiscountRecord["DiscountFamilyDate"]));

             // <<< Discount type SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_UPDATE:
                     $DiscountFamilyType = $GLOBALS["CONF_DISCOUNTS_FAMILIES_TYPES"][$DiscountRecord["DiscountFamilyType"]];
                     break;

                 case FCT_ACT_CREATE:
                     $DiscountFamilyType = generateSelectField("lDiscountType", array_keys($GLOBALS['CONF_DISCOUNTS_FAMILIES_TYPES']),
                                                               $GLOBALS['CONF_DISCOUNTS_FAMILIES_TYPES'],
                                                               $DiscountRecord["DiscountFamilyType"], "");
                     break;
             }

             // <<< Discount reason type SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_UPDATE:
                     $DiscountReasonType = $GLOBALS["CONF_DISCOUNTS_FAMILIES_REASON_TYPES"][$DiscountRecord["DiscountFamilyReasonType"]];
                     break;

                 case FCT_ACT_CREATE:
                     $DiscountReasonType = generateSelectField("lDiscountReasonType",
                                                               array_keys($GLOBALS['CONF_DISCOUNTS_FAMILIES_REASON_TYPES']),
                                                               $GLOBALS['CONF_DISCOUNTS_FAMILIES_REASON_TYPES'],
                                                               $DiscountRecord["DiscountFamilyReasonType"], "");
                     break;
             }

             // <<< Reason INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $DiscountFamilyReason = stripslashes($DiscountRecord["DiscountFamilyReason"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $DiscountFamilyReason = generateInputField("sReason", "text", "255", "40", $GLOBALS["LANG_DISCOUNT_REASON_TIP"],
                                                                $DiscountRecord["DiscountFamilyReason"]);
                     break;
             }

             // <<< Amount INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $Amount = stripslashes($DiscountRecord["DiscountFamilyAmount"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Amount = generateInputField("fAmount", "text", "10", "10", $GLOBALS["LANG_DISCOUNT_AMOUNT_TIP"],
                                                  $DiscountRecord["DiscountFamilyAmount"]);
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_DISCOUNT_DATE"]."</td><td class=\"Value\">$DiscountFamilyDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DISCOUNT_TYPE"]."</td><td class=\"Value\">$DiscountFamilyType</td><td class=\"Label\">".$GLOBALS["LANG_DISCOUNT_AMOUNT"]."*</td><td class=\"Value\">$Amount ".$GLOBALS["CONF_PAYMENTS_UNIT"]."</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DISCOUNT_REASON_TYPE"]."</td><td class=\"Value\">$DiscountReasonType</td><td class=\"Label\">".$GLOBALS["LANG_DISCOUNT_REASON"]."</td><td class=\"Value\">$DiscountFamilyReason</td>\n</tr>\n";
             echo "</table>\n";

             closeStyledFrame();

             insertInputField("hidFamilyID", "hidden", "", "", "", $DiscountRecord["FamilyID"]);

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // We display the buttons
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a discount/increase
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }
?>