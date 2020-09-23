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
 * Support module : display the form to generate bills of a month to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.5
 *     - 2012-10-01 : patch the bug of wrong number of activated children for the selected month
 *     - 2013-02-01 : sent  a different notification if the family has several not paid bills (taken into account
 *                    $CONF_BILLS_NOT_PAID_BILLS_LIMIT and $CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION)
 *     - 2013-04-08 : add an link in the mail to download the PDF file of the bill and better managment of
 *                    the not paid bills (the not paid bill of the current month isn't taken inton account in the
 *                    "not paid bills" stat)
 *     - 2013-10-09 : taken into account modes of monthly contributions of $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS and
 *                    the new "FamilyMonthlyContributionMode" field
 *     - 2013-12-16 : allow to re-send by mail bills of a selected month. Allow to generate bills for some families
 *                    or a selected family and a given month (when the bill wasn't generated because of
 *                    a technical problem)
 *     - 2014-02-03 : taken into account nursery delays ($CONF_NURSERY_DELAYS_PRICES) in the price of the nursery,
 *                    display an achor to go directly to content
 *     - 2014-06-03 : allow to regenerate the PDF containing all bills of ths families for the selected month
 *     - 2015-06-02 : recompute the previous balance field for the last bill of a family
 *     - 2016-07-04 : allow to send bills to families with a delay (jobs)
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2017-11-07 : v3.1. Taken into account $CONF_CANTEEN_PRICES_CONCERNED_MEAL_TYPES and use BillWithoutMealAmount field
 *     - 2020-03-02 : v3.5. Taken into account $CONF_NURSERY_OTHER_TIMESLOTS to compute the bills and
 *                    BillNbCanteenRegistrations and BillNbNurseryRegistrations fields
 *
 * @since 2012-02-21
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
                                      'CONF_NURSERY_OTHER_TIMESLOTS',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 // To take into account the year and month to generate the bills
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
     }
 }

 // We check if the "send mail" checkbox is checked
 $bChkSendmail = NULL;
 if (isset($_POST["chkSendMail"]))
 {
     $bChkSendmail = TRUE;
 }
 elseif (isset($_GET["chkSendMail"]))
 {
     $bChkSendmail = TRUE;
 }
 elseif (!empty($_POST["bGenerate"]))
 {
     // Checkbox "send mail" not checked
     $bChkSendmail = FALSE;
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

 //################################ FORM PROCESSING ##########################
 $sErrorMsg = '';
 $sConfirmationMsg = '';
 $iNbGeneratedBills = 0;
 $iNbMailsSent = 0;
 $iNbJobsCreated = 0;

 if ((!empty($_POST["bGenerate"])) || (!empty($_POST["bMissingBills"])))
 {
     // We generate the bills for each activated family, for the selected month,
     // next, we generate the pdf for each bill,
     // then we send each pdf bill to the family by mail
     // finaly, we generate one pdf for the logged supporter with all bills

     // So, we get the activated families for the current school year
     $BillDate = date('Y-m-d H:i:s');
     $BillForDate = date('Y-m-t', strtotime("$Year-$Month-01"));
     $SchoolYear = getSchoolYear(date('Y-m-d', strtotime($BillForDate)));
     $StartDate = date('Y-m-d', strtotime("$Year-$Month-01"));
     $EndDate = $BillForDate;

     // We get the number of other nursery timeslots
     $iNbOtherTimeslots = 0;
     $ArrayOtherTimeslotsPatterns = array();
     if ((isset($CONF_NURSERY_OTHER_TIMESLOTS[$SchoolYear])) && (!empty($CONF_NURSERY_OTHER_TIMESLOTS[$SchoolYear])))
     {
         // This school year has some other timeslots (more than AM and PM timeslots)
         $iNbOtherTimeslots = count($CONF_NURSERY_OTHER_TIMESLOTS[$SchoolYear]);
         $iPos = 0;
         foreach($CONF_NURSERY_OTHER_TIMESLOTS[$SchoolYear] as $ots => $CurrentParamsOtherTimeslot)
         {
             $ArrayOtherTimeslotsPatterns[$ots] = pow(2, $iPos);
             $iPos++;
         }
     }

     // We get the max price of the selected school year, for nursery delay
     if (isset($CONF_NURSERY_DELAYS_PRICES[$SchoolYear]))
     {
         $ArrayPricesKeys = array_keys($CONF_NURSERY_DELAYS_PRICES[$SchoolYear]);
         $_NURSERY_DELAY_MAX_PRICE_ = 0.00;
         if (!empty($ArrayPricesKeys))
         {
             $_NURSERY_DELAY_MAX_PRICE_ = $CONF_NURSERY_DELAYS_PRICES[$SchoolYear][$ArrayPricesKeys[count($ArrayPricesKeys) - 1]];
         }

         unset($ArrayPricesKeys);
     }

     // Parameters to search activates families for which we must generate monthly bills
     $ArrayParams = array(
                          'Activated' => TRUE,
                          'ActivatedChildren' => TRUE,
                          'SchoolYear' => array($SchoolYear)
                         );

     $MoreParams = array();

     $SelectedFamilyID = trim(strip_tags($_POST['lFamilyID']));

     if (!empty($_POST["bGenerate"]))
     {
         // Check if a family is selected, limit the search to this family
         if (!empty($SelectedFamilyID))
         {
             // Only one family selected for which we want to generate the bill for the selected month
             $MoreParams['FamilyID'] = array($SelectedFamilyID);
         }
     }
     elseif (!empty($_POST["bMissingBills"]))
     {
         // We check if we only generate missing bills for all concerned families or only one selected family
         if (empty($SelectedFamilyID))
         {
             // It's for all concerned families : we get activated families
             $ArrayFamilies = dbSearchFamily($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
         }
         else
         {
             // It's for all only the selected family : we get activated selected family
             $ArrayFamilies = dbSearchFamily($DbCon, array_merge($ArrayParams, array('FamilyID' => $SelectedFamilyID)),
                                             "FamilyLastname", 1, 0);
         }

         if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
         {
             // We get bills of the selected month
             $ArrayBills = getBills($DbCon, $StartDate, $EndDate, "BillForDate, FamilyID", PLANNING_BETWEEN_DATES,
                                    array('BillForYearMonth' => date('Y-m', strtotime($BillForDate))));

             if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
             {
                 $ArrayConcernedFamilyID = array();
                 foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                 {
                     if (!in_array($FamilyID, $ArrayBills['FamilyID']))
                     {
                         // This family hasn't bill for this month : she is concerned by regenerate the missing bill
                         $ArrayConcernedFamilyID[] = $FamilyID;
                     }
                 }

                 if (empty($ArrayConcernedFamilyID))
                 {
                     // No family with missing bill for this month
                     $MoreParams['FamilyID'] = array(-1);  // We set this value not to found families with dbSearchFamily()
                 }
                 else
                 {
                     $MoreParams['FamilyID'] = $ArrayConcernedFamilyID;
                 }

                 unset($ArrayConcernedFamilyID);
             }
         }

         unset($ArrayFamilies);
     }

     $ArrayFamilies = dbSearchFamily($DbCon, array_merge($ArrayParams, $MoreParams), "FamilyLastname", 1, 0);

     if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
     {
         // ******** STEP 1 : we generate the bills of the family in the database ********
         foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
         {
             // We get the previous balance of the family
             $BillPreviousBalance = -1 * $ArrayFamilies['FamilyBalance'][$f];

             // We get children of the family and there activated suspensions
             $ArrayChildren = getFamilyChildren($DbCon, $FamilyID);
             $iNbSuspensions = 0;
             if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
             {
                 foreach($ArrayChildren['ChildID'] as $c => $ChildID)
                 {
                     $ArraySuspensions = getSuspensionsChild($DbCon, $ChildID, FALSE, 'SuspensionStartDate DESC',
                                                             array(
                                                                   'OpenedInPast' => TRUE,
                                                                   'StartDate' => $StartDate,
                                                                   'EndDate' => $EndDate
                                                                  ));

                     // At least one suspension found for this month, we don't count the child for the monthly contribution
                     if ((isset($ArraySuspensions['SuspensionID'])) && (!empty($ArraySuspensions['SuspensionID'])))
                     {
                         $iNbSuspensions++;
                     }
                 }
             }

             $iNbChildrenToTakeIntoAccount = max(0, $ArrayFamilies['NbChildren'][$f] - $iNbSuspensions);

             // We check if there is a deposit for the family : not used up to now!
             $BillDeposit = 0;

             // We get all expenses in the month of the current family
             // First, we get the monthly contribution in relation with the number of children
             // for the current school year...
             // ... except if there is no contribution for this month
             if (in_array($Month, $CONF_NO_CONTRIBUTION_FOR_MONTHS))
             {
                 // No contribution for this month
                 $BillMonthlyContribution = 0;
             }
             else
             {
                 // Taken into account the mode of monthly contribution
                 if (isset($CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SchoolYear][$ArrayFamilies['FamilyMonthlyContributionMode'][$f]][$iNbChildrenToTakeIntoAccount]))
                 {
                     $BillMonthlyContribution = $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SchoolYear][$ArrayFamilies['FamilyMonthlyContributionMode'][$f]][$iNbChildrenToTakeIntoAccount];
                 }
                 elseif ($iNbChildrenToTakeIntoAccount == 0)
                 {
                     $BillMonthlyContribution = 0;
                 }
                 else
                 {
                     // The number of children > max children defined in the configuration. We use the max contribution
                     $ArrayKeys = $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SchoolYear];
                     $BillMonthlyContribution = $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SchoolYear][$ArrayFamilies['FamilyMonthlyContributionMode'][$f]][count($ArrayKeys)];
                     unset($ArrayKeys);
                 }
             }

             // Next, we get canteen registrations
             $BillNbCanteenRegistrations = 0;
             $BillCanteenAmount = 0;
             $BillWithoutMealAmount = 0;
             $ArrayCanteenRegistrations = getCanteenRegistrations($DbCon, $StartDate, $EndDate, "CanteenRegistrationForDate",
                                                                  NULL, FALSE, PLANNING_BETWEEN_DATES,
                                                                  array('FamilyID' => array($FamilyID)));

             if ((isset($ArrayCanteenRegistrations['CanteenRegistrationID'])) && (!empty($ArrayCanteenRegistrations['CanteenRegistrationID'])))
             {
                 foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $cr => $CanteenRegistrationID)
                 {
                     // Check if the canteen registration is valided
                     if ($ArrayCanteenRegistrations['CanteenRegistrationValided'][$cr] == 0)
                     {
                         // Not valided : only price of the lunch
                         $BillWithoutMealAmount += $CONF_CANTEEN_PRICES[$SchoolYear][0];
                     }
                     else
                     {
                         // Valided : the type of meal is concerned by the price of the lunch ?
                         if (in_array($ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$cr],
                                      $CONF_CANTEEN_PRICES_CONCERNED_MEAL_TYPES))
                         {
                             // Price of the lunch + price of the nursery for the lunch
                             $BillCanteenAmount += $CONF_CANTEEN_PRICES[$SchoolYear][0] + $CONF_CANTEEN_PRICES[$SchoolYear][1];
                         }
                         else
                         {
                             // Only price of the nursery for the lunch
                             $BillWithoutMealAmount += $CONF_CANTEEN_PRICES[$SchoolYear][1];
                         }
                     }

                     $BillNbCanteenRegistrations++;
                 }
             }

             // Finaly, we get nursery registrations
             $BillNbNurseryRegistrations = 0;
             $BillNurseryAmount = 0;
             $BillNurseryNbDelays = 0;
             $ArrayNurseryDelays = array();
             $ArrayNurseryRegistrations = getNurseryRegistrations($DbCon, $StartDate, $EndDate, "NurseryRegistrationForDate",
                                                                  NULL, PLANNING_BETWEEN_DATES,
                                                                  array('FamilyID' => array($FamilyID)));

             if ((isset($ArrayNurseryRegistrations['NurseryRegistrationID'])) && (!empty($ArrayNurseryRegistrations['NurseryRegistrationID'])))
             {
                 foreach($ArrayNurseryRegistrations['NurseryRegistrationID'] as $nr => $NurseryRegistrationID)
                 {
                     if ($ArrayNurseryRegistrations['NurseryRegistrationForAM'][$nr] > 0)
                     {
                         // Price for AM
                         $BillNurseryAmount += $CONF_NURSERY_PRICES[$SchoolYear][0];
                         $BillNbNurseryRegistrations++;
                     }

                     if ($ArrayNurseryRegistrations['NurseryRegistrationForPM'][$nr] > 0)
                     {
                         // Price for PM
                         $BillNurseryAmount += $CONF_NURSERY_PRICES[$SchoolYear][1];
                         $BillNbNurseryRegistrations++;
                     }

                     // Check if the nursery registration has a delay
                     if ($ArrayNurseryRegistrations['NurseryRegistrationIsLate'][$nr] > 0)
                     {
                         // Yes, there is a delay : we check if there is already a delay for this date
                         if (!in_array($ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr], $ArrayNurseryDelays))
                         {
                             $ArrayNurseryDelays[] = $ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr];
                             $BillNurseryNbDelays++;
                         }
                     }

                     // Check if there are some other timeslots
                     if ($iNbOtherTimeslots > 0)
                     {
                         foreach($ArrayOtherTimeslotsPatterns as $ots => $CurrentPattern)
                         {
                             if ($ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][$nr] & $CurrentPattern)
                             {
                                 // Other timeslot checked
                                 $BillNbNurseryRegistrations++;

                                 // Get price of the timeslot
                                 if ((isset($CONF_NURSERY_PRICES[$SchoolYear]['OtherTimeslots']))
                                     && (isset($CONF_NURSERY_PRICES[$SchoolYear]['OtherTimeslots'][$ots])))
                                 {
                                     $BillNurseryAmount += $CONF_NURSERY_PRICES[$SchoolYear]['OtherTimeslots'][$ots];
                                 }
                             }
                         }
                     }
                 }
             }

             // Compute the amount of nursery delays
             if ($BillNurseryNbDelays > 0)
             {
                 if (isset($CONF_NURSERY_DELAYS_PRICES[$SchoolYear]))
                 {
                     $NurseryDelaysAmount = 0;

                     for($nd = 1; $nd <= $BillNurseryNbDelays; $nd++)
                     {
                         if (isset($CONF_NURSERY_DELAYS_PRICES[$SchoolYear][$nd]))
                         {
                             $NurseryDelaysAmount += $CONF_NURSERY_DELAYS_PRICES[$SchoolYear][$nd];
                         }
                         else
                         {
                             $NurseryDelaysAmount += $_NURSERY_DELAY_MAX_PRICE_;
                         }
                     }

                     // Update the nursery amount
                     $BillNurseryAmount += $NurseryDelaysAmount;
                 }
             }

             // We check if the bill already exists for the family and for this year/month
             $BillID = getMonthlyBillIDForFamily($DbCon, $FamilyID, $BillForDate);
             if ($BillID > 0)
             {
                 // We get the previous data of the bill
                 $RecordOldBill = getTableRecordInfos($DbCon, "Bills", $BillID);

                 $OldBillTotal = $RecordOldBill['BillMonthlyContribution'] + $RecordOldBill['BillCanteenAmount']
                                 + $RecordOldBill['BillWithoutMealAmount'] + $RecordOldBill['BillNurseryAmount']
                                 - $RecordOldBill['BillDeposit'];

                 $NewBillTotal = $BillMonthlyContribution + $BillCanteenAmount + $BillWithoutMealAmount + $BillNurseryAmount
                                 - $BillDeposit;

                 // We check if the bill is the last of the family
                 if (isLastBillOfFamily($DbCon, $BillID))
                 {
                     // This bill is the last for this family : we recompute the previous balance amount of the bill
                     $BillPreviousBalance = NULL;

                     // We get payments recorded after the generation of the bill
                     $ArrayNewBillsPayments = getFamilyPayments($DbCon, $FamilyID,
                                                                array("PaymentType" => array(1),
                                                                      "PaymentDate" => array(">", $RecordOldBill['BillDate'])),
                                                                "PaymentDate");

                     $fTotalNewPayments = 0;
                     if ((isset($ArrayNewBillsPayments['PaymentID'])) && (count($ArrayNewBillsPayments['PaymentID']) > 0))
                     {
                         foreach($ArrayNewBillsPayments['PaymentID'] as $np => $NewPaymentID)
                         {
                             $fTotalNewPayments += $ArrayNewBillsPayments['PaymentAmount'][$np];
                         }

                         // We remove the total of payments recorded after the generation of the last bill to
                         // the previous balance
                         $BillPreviousBalance = $RecordOldBill['BillPreviousBalance'] - $fTotalNewPayments;
                     }
                 }
                 else
                 {
                     // We don't recompute the previous balance amount of the bill
                     $BillPreviousBalance = NULL;
                 }

                 // We update the bill of the month : we don't change the previous balance of the family
                 $BillID = dbUpdateBill($DbCon, $BillID, $BillDate, $BillForDate, $FamilyID, $BillPreviousBalance, $BillDeposit,
                                        $BillMonthlyContribution, $BillCanteenAmount, $BillWithoutMealAmount, $BillNurseryAmount,
                                        NULL, $BillNurseryNbDelays, $BillNbCanteenRegistrations, $BillNbNurseryRegistrations);

                 if ($OldBillTotal != $NewBillTotal)
                 {
                     // We must update the balance of the family
                     $NewBalance = $NewBillTotal - $OldBillTotal;
                     $UpdatedBalance = updateFamilyBalance($DbCon, $FamilyID, -1 * $NewBalance);
                 }

                 $iNbGeneratedBills++;
             }
             else
             {
                 // We save the bill in the database
                 $BillID = dbAddBill($DbCon, $BillDate, $BillForDate, $FamilyID, $BillPreviousBalance, $BillDeposit,
                                     $BillMonthlyContribution, $BillCanteenAmount, $BillWithoutMealAmount, $BillNurseryAmount,
                                     0.00, $BillNurseryNbDelays, $BillNbCanteenRegistrations, $BillNbNurseryRegistrations);

                 if ($BillID > 0)
                 {
                     // We update the balance of the family
                     $NewBalance = $BillMonthlyContribution + $BillCanteenAmount + $BillWithoutMealAmount + $BillNurseryAmount
                                   - $BillDeposit;

                     $UpdatedBalance = updateFamilyBalance($DbCon, $FamilyID, -1 * $NewBalance);

                     $iNbGeneratedBills++;
                 }
             }
         }

         // ******** STEP 2 : we generate each generated bill in PDF and send it to the family by e-mail ********
         // We get bills of the year/month
         $ArrayBillsParams = array('BillForYearMonth' => date('Y-m', strtotime($BillForDate)));
         $ArrayBills = getBills($DbCon, $StartDate, $EndDate, "BillForDate, FamilyLastname", PLANNING_BETWEEN_DATES,
                                array_merge($ArrayBillsParams, $MoreParams));

         if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
         {
             $FileSuffix = formatFilename($CONF_PLANNING_MONTHS[$Month - 1].$Year);
             foreach($ArrayBills['BillID'] as $b => $BillID)
             {
                 // Generate the bill in HTML/CSS, then we convert the HTML/CSS file to PDF
                 $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix-$BillID.html";

                 @unlink($HTMLFilename);
                 printDetailsBillForm($DbCon, $BillID, "GenerateMonthlyBills.php", $HTMLFilename);
                 if (file_exists($HTMLFilename))
                 {
                     $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix-$BillID.pdf";

                     // Generate the PDF
                     @unlink($PDFFilename);
                     if (html2pdf($HTMLFilename, $PDFFilename, 'portrait', MONTHLY_BILL_DOCTYPE))
                     {
                         // Delete the HTML file
                         unlink($HTMLFilename);

                         // Send the PDF by e-mail if template defined and checkbox "send mail" checked
                         if (($bChkSendmail) && (!empty($CONF_BILLS_FAMILIES_NOTIFICATION)))
                         {
                             // Subject of the mail
                             $BillForDate = date($CONF_DATE_DISPLAY_FORMAT, strtotime($ArrayBills['BillForDate'][$b]));
                             $BillUrl = $CONF_URL_SUPPORT."Canteen/DownloadBill.php?Cr=".md5($BillID)."&amp;Id=$BillID";
                             $BillLink    = $LANG_DOWNLOAD;
                             $BillLinkTip = $LANG_DOWNLOAD;

                             $EmailSubject = "$LANG_BILL_EMAIL_SUBJECT $BillForDate";

                             if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_BILL]))
                             {
                                 $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_BILL].$EmailSubject;
                             }

                             // We define the content of the mail
                             $TemplateToUse = $CONF_BILLS_FAMILIES_NOTIFICATION;
                             if (empty($CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION))
                             {
                                 // The "not paid bills" template isn't activated, so we use the default template to send
                                 // the monthy bill to the family
                                 $ReplaceInTemplate = array(
                                                            array(
                                                                  "{LANG_FAMILY_LASTNAME}", "{FamilyLastname}", "{LANG_BILL_FOR_DATE}",
                                                                  "{BillForDate}", "{BillUrl}", "{BillLink}", "{BillLinkTip}"
                                                                 ),
                                                            array(
                                                                  $LANG_FAMILY_LASTNAME, $ArrayBills['FamilyLastname'][$b],
                                                                  $LANG_BILL_FOR_DATE, $BillForDate, $BillUrl, $BillLink, $BillLinkTip
                                                                 )
                                                           );
                             }
                             else
                             {
                                 // The "not paid bills" template isn't activated, so we check if the family has several
                                 // not paid bills >= the defined limit ($CONF_BILLS_NOT_PAID_BILLS_LIMIT)
                                 // if no, we use the defaut template to send the monthy bill
                                 // if yes, we use the "not paid bills" template
                                 $iNbNotPaidBills = 0;

                                 // We get the not paid bills of the family
                                 $ArrayBillsFamily = getBills($DbCon, NULL, NULL, 'BillForDate', NO_DATES,
                                                              array(
                                                                    "FamilyID" => array($ArrayBills['FamilyID'][$b]),
                                                                    "BillPaid" => array(0)
                                                                   )
                                                             );

                                 if ((isset($ArrayBillsFamily['BillID'])) && (!empty($ArrayBillsFamily['BillID'])))
                                 {
                                     // The family has not paid bills : we remove the not paid bill of the current month
                                     $iNbNotPaidBills = count($ArrayBillsFamily['BillID']) - 1;
                                     if ($iNbNotPaidBills >= $CONF_BILLS_NOT_PAID_BILLS_LIMIT)
                                     {
                                         // We have to use the "not paid bills" template
                                         $TemplateToUse = $CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION;

                                         // We add to the subject a mark to show the notification concerned not paid bills
                                         $EmailSubject .= " (!)";
                                     }
                                 }

                                 $ReplaceInTemplate = array(
                                                            array(
                                                                  "{LANG_FAMILY_LASTNAME}", "{FamilyLastname}", "{LANG_BILL_FOR_DATE}",
                                                                  "{BillForDate}", "{NbNotPaidBills}", "{BillUrl}", "{BillLink}", "{BillLinkTip}"
                                                                 ),
                                                            array(
                                                                  $LANG_FAMILY_LASTNAME, $ArrayBills['FamilyLastname'][$b],
                                                                  $LANG_BILL_FOR_DATE, $BillForDate, $iNbNotPaidBills,
                                                                  $BillUrl, $BillLink, $BillLinkTip
                                                                 )
                                                           );
                             }

                             // Set the PDF file in attachment
                             $ArrayPDF = array($PDFFilename);

                             // We define the mailing-list
                             $MailingList["to"] = array($ArrayBills['FamilyMainEmail'][$b]);
                             if (!empty($ArrayBills['FamilySecondEmail'][$b]))
                             {
                                 $MailingList["to"][] = $ArrayBills['FamilySecondEmail'][$b];
                             }

                             // DEBUG MODE
                             if ($GLOBALS["CONF_MODE_DEBUG"])
                             {
                                 if (!in_array($GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"], $MailingList["to"]))
                                 {
                                     // Without this test, there is a server mail error...
                                     $MailingList["to"] = array_merge(array($GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"]),
                                                                      $MailingList["to"]);
                                 }
                             }

                             // We send the e-mail : now or after ?
                             if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL]))
                                 && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL]) == 2))
                             {
                                 // The message is delayed (job)
                                 $bIsEmailSent = FALSE;

                                 // Compute the planned date/time
                                 if ($iNbJobsCreated == 0)
                                 {
                                     // First job
                                     $PlannedDateStamp = strtotime("+1 min", strtotime("now"));
                                 }
                                 elseif (($iNbJobsCreated % $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL][JobSize]) == 0)
                                 {
                                     // New planned date for jobs
                                     // Compute date/time for the next job
                                     $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL][DelayBetween2Jobs] * 60;
                                 }

                                 $ArrayJobParams = array(
                                                         array(
                                                               "JobParameterName" => "mailinglist",
                                                               "JobParameterValue" => base64_encode(serialize($MailingList))
                                                              ),
                                                         array(
                                                               "JobParameterName" => "subject",
                                                               "JobParameterValue" => $EmailSubject
                                                              ),
                                                         array(
                                                               "JobParameterName" => "template-name",
                                                               "JobParameterValue" => $TemplateToUse
                                                              ),
                                                         array(
                                                               "JobParameterName" => "replace-in-template",
                                                               "JobParameterValue" => base64_encode(serialize($ReplaceInTemplate))
                                                              ),
                                                         array(
                                                               "JobParameterName" => "attachment",
                                                               "JobParameterValue" => base64_encode(serialize($ArrayPDF))
                                                              )
                                                        );

                                 // Create the job to send a delayed e-mail
                                 $JobID = dbAddJob($DbCon, $_SESSION['SupportMemberID'], JOB_EMAIL,
                                                   date('Y-m-d H:i:s', $PlannedDateStamp), NULL, 0, NULL, $ArrayJobParams);

                                 if ($JobID > 0)
                                 {
                                     $iNbJobsCreated++;
                                     $bIsEmailSent = TRUE;
                                 }
                             }
                             else
                             {
                                 // We can send now the e-mail with bill
                                 $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate,
                                                           $ArrayPDF);
                             }

                             if ($bIsEmailSent)
                             {
                                 $iNbMailsSent++;
                             }
                         }
                     }
                 }
             }

             // ******** STEP 3 : we generate one PDF with all bills of the month ********
             if (empty($MoreParams))
             {
                 $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_GLOBAL_FILENAME."$FileSuffix.html";
                 @unlink($HTMLFilename);
                 printDetailsSeveralBillsForm($DbCon, $ArrayBills['BillID'], "GenerateMonthlyBills.php", $HTMLFilename);

                 if (file_exists($HTMLFilename))
                 {
                     $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_GLOBAL_FILENAME."$FileSuffix.pdf";

                     // Generate the PDF
                     @unlink($PDFFilename);
                     if (html2pdf($HTMLFilename, $PDFFilename, 'landscape', ALL_MONTHLY_BILLS_DOCTYPE))
                     {
                         // Delete the HTML file
                         unlink($HTMLFilename);

                         // Create link to download the PDF containing all bills of the month
                         if (file_exists($PDFFilename))
                         {
                             // Force the download of the PDF file
                             $PDFsize = filesize($PDFFilename);
                             $PDFTmpFilename = basename($PDFFilename);
                             header("Content-Type: application/octet-stream");
                             header("Content-Length: $PDFsize");
                             header("Content-disposition: attachment; filename=$PDFTmpFilename");
                             header("Pragma: no-cache;");
                             header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
                             header("Expires: 0");
                             readfile($PDFFilename);
                         }
                     }
                 }
             }

             $iNbFamilies = count($ArrayFamilies['FamilyID']);
             $sConfirmationMsg = ucfirst($LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_NB_GENERATED_BILLS)." : $iNbGeneratedBills / $iNbFamilies, "
                                 .$LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_NB_SENT_EMAILS." : $iNbMailsSent / $iNbFamilies.";

             unset($ArrayFamilies);
         }
     }
 }

 if (!empty($_POST["bResendByMail"]))
 {
     // Just re-send by mail to families bills previously generated
     $BillForDate = date('Y-m-t', strtotime("$Year-$Month-01"));
     $SchoolYear = getSchoolYear($BillForDate);
     $StartDate = date('Y-m-d', strtotime("$Year-$Month-01"));
     $EndDate = $BillForDate;

     // We get bills of the year/month
     $ArrayBills = getBills($DbCon, $StartDate, $EndDate, "BillForDate, FamilyLastname", PLANNING_BETWEEN_DATES,
                            array('BillForYearMonth' => date('Y-m', strtotime($BillForDate))));

     if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
     {
         $FileSuffix = formatFilename($CONF_PLANNING_MONTHS[$Month - 1].$Year);
         foreach($ArrayBills['BillID'] as $b => $BillID)
         {
             // Generate the bill in HTML/CSS, then we convert the HTML/CSS file to PDF
             $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix-$BillID.html";

             @unlink($HTMLFilename);
             printDetailsBillForm($DbCon, $BillID, "GenerateMonthlyBills.php", $HTMLFilename);
             if (file_exists($HTMLFilename))
             {
                 $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_FILENAME."$FileSuffix-$BillID.pdf";

                 // Generate the PDF
                 @unlink($PDFFilename);
                 if (html2pdf($HTMLFilename, $PDFFilename, 'portrait', MONTHLY_BILL_DOCTYPE))
                 {
                     // Delete the HTML file
                     unlink($HTMLFilename);

                     // Send the PDF by e-mail if template defined
                     if (!empty($CONF_BILLS_FAMILIES_NOTIFICATION))
                     {
                         // Subject of the mail
                         $BillForDate = date($CONF_DATE_DISPLAY_FORMAT, strtotime($ArrayBills['BillForDate'][$b]));
                         $BillUrl = $CONF_URL_SUPPORT."Canteen/DownloadBill.php?Cr=".md5($BillID)."&amp;Id=$BillID";
                         $BillLink    = $LANG_DOWNLOAD;
                         $BillLinkTip = $LANG_DOWNLOAD;

                         $EmailSubject = "$LANG_BILL_EMAIL_SUBJECT $BillForDate";

                         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_BILL]))
                         {
                             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_BILL].$EmailSubject;
                         }

                         // We define the content of the mail
                         $TemplateToUse = $CONF_BILLS_FAMILIES_NOTIFICATION;
                         if (empty($CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION))
                         {
                             // The "not paid bills" template isn't activated, so we use the default template to send
                             // the monthy bill to the family
                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{LANG_FAMILY_LASTNAME}", "{FamilyLastname}", "{LANG_BILL_FOR_DATE}",
                                                              "{BillForDate}", "{BillUrl}", "{BillLink}", "{BillLinkTip}"
                                                             ),
                                                        array(
                                                              $LANG_FAMILY_LASTNAME, $ArrayBills['FamilyLastname'][$b],
                                                              $LANG_BILL_FOR_DATE, $BillForDate, $BillUrl, $BillLink, $BillLinkTip
                                                             )
                                                       );
                         }
                         else
                         {
                             // The "not paid bills" template isn't activated, so we check if the family has several
                             // not paid bills >= the defined limit ($CONF_BILLS_NOT_PAID_BILLS_LIMIT)
                             // if no, we use the defaut template to send the monthy bill
                             // if yes, we use the "not paid bills" template
                             $iNbNotPaidBills = 0;

                             // We get the not paid bills of the family
                             $ArrayBillsFamily = getBills($DbCon, NULL, NULL, 'BillForDate', NO_DATES,
                                                          array(
                                                                "FamilyID" => array($ArrayBills['FamilyID'][$b]),
                                                                "BillPaid" => array(0)
                                                               )
                                                         );

                             if ((isset($ArrayBillsFamily['BillID'])) && (!empty($ArrayBillsFamily['BillID'])))
                             {
                                 // The family has not paid bills : we remove the not paid bill of the current month
                                 $iNbNotPaidBills = count($ArrayBillsFamily['BillID']) - 1;
                                 if ($iNbNotPaidBills >= $CONF_BILLS_NOT_PAID_BILLS_LIMIT)
                                 {
                                     // We have to use the "not paid bills" template
                                     $TemplateToUse = $CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION;

                                     // We add to the subject a mark to show the notification concerned not paid bills
                                     $EmailSubject .= " (!)";
                                 }
                             }

                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{LANG_FAMILY_LASTNAME}", "{FamilyLastname}", "{LANG_BILL_FOR_DATE}",
                                                              "{BillForDate}", "{NbNotPaidBills}", "{BillUrl}", "{BillLink}", "{BillLinkTip}"
                                                             ),
                                                        array(
                                                              $LANG_FAMILY_LASTNAME, $ArrayBills['FamilyLastname'][$b],
                                                              $LANG_BILL_FOR_DATE, $BillForDate, $iNbNotPaidBills,
                                                              $BillUrl, $BillLink, $BillLinkTip
                                                             )
                                                       );
                         }

                         // Set the PDF file in attachment
                         $ArrayPDF = array($PDFFilename);

                         // We define the mailing-list
                         $MailingList["to"] = array($ArrayBills['FamilyMainEmail'][$b]);
                         if (!empty($ArrayBills['FamilySecondEmail'][$b]))
                         {
                             $MailingList["to"][] = $ArrayBills['FamilySecondEmail'][$b];
                         }

                         // DEBUG MODE
                         if ($GLOBALS["CONF_MODE_DEBUG"])
                         {
                             if (!in_array($GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"], $MailingList["to"]))
                             {
                                 // Without this test, there is a server mail error...
                                 $MailingList["to"] = array_merge(array($GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"]),
                                                                  $MailingList["to"]);
                             }
                         }

                         // We send the e-mail : now or after ?
                         if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL]))
                             && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL]) == 2))
                         {
                             // The message is delayed (job)
                             $bIsEmailSent = FALSE;

                             // Compute the planned date/time
                             if ($iNbJobsCreated == 0)
                             {
                                 // First job
                                 $PlannedDateStamp = strtotime("+1 min", strtotime("now"));
                             }
                             elseif (($iNbJobsCreated % $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL][JobSize]) == 0)
                             {
                                 // New planned date for jobs
                                 // Compute date/time for the next job
                                 $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_BILL][DelayBetween2Jobs] * 60;
                             }

                             $ArrayJobParams = array(
                                                     array(
                                                           "JobParameterName" => "mailinglist",
                                                           "JobParameterValue" => base64_encode(serialize($MailingList))
                                                          ),
                                                     array(
                                                           "JobParameterName" => "subject",
                                                           "JobParameterValue" => $EmailSubject
                                                          ),
                                                     array(
                                                           "JobParameterName" => "template-name",
                                                           "JobParameterValue" => $TemplateToUse
                                                          ),
                                                     array(
                                                           "JobParameterName" => "replace-in-template",
                                                           "JobParameterValue" => base64_encode(serialize($ReplaceInTemplate))
                                                          ),
                                                     array(
                                                           "JobParameterName" => "attachment",
                                                           "JobParameterValue" => base64_encode(serialize($ArrayPDF))
                                                          ),
                                                    );

                             // Create the job to send a delayed e-mail
                             $JobID = dbAddJob($DbCon, $_SESSION['SupportMemberID'], JOB_EMAIL,
                                               date('Y-m-d H:i:s', $PlannedDateStamp), NULL, 0, NULL, $ArrayJobParams);

                             if ($JobID > 0)
                             {
                                 $iNbJobsCreated++;
                                 $bIsEmailSent = TRUE;
                             }
                         }
                         else
                         {
                             // We can send now the e-mail with bill
                             $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate,
                                                       $ArrayPDF);
                         }

                         if ($bIsEmailSent)
                         {
                             $iNbMailsSent++;
                         }
                     }
                 }
             }
         }

         $sConfirmationMsg = $LANG_CONFIRM_BILLS_RESENT;
     }
     else
     {
         // No bill for this month
         $sErrorMsg = $LANG_ERROR_NO_BILL_FOR_THIS_MONTH;
     }
 }
 elseif (!empty($_POST['bRegenerateSynthesisDoc']))
 {
     // Regenerate the PDF containing all bills of the families for the selected month
     $BillForDate = date('Y-m-t', strtotime("$Year-$Month-01"));
     $SchoolYear = getSchoolYear($BillForDate);
     $StartDate = date('Y-m-d', strtotime("$Year-$Month-01"));
     $EndDate = $BillForDate;

     // We get bills of the year/month
     $ArrayBills = getBills($DbCon, $StartDate, $EndDate, "BillForDate, FamilyLastname", PLANNING_BETWEEN_DATES,
                            array('BillForYearMonth' => date('Y-m', strtotime($BillForDate))));

     if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
     {
         $FileSuffix = formatFilename($CONF_PLANNING_MONTHS[$Month - 1].$Year);
         $HTMLFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_GLOBAL_FILENAME."$FileSuffix.html";
         @unlink($HTMLFilename);
         printDetailsSeveralBillsForm($DbCon, $ArrayBills['BillID'], "GenerateMonthlyBills.php", $HTMLFilename);

         if (file_exists($HTMLFilename))
         {
             $PDFFilename = $CONF_EXPORT_DIRECTORY_HDD.$CONF_BILLS_PRINT_GLOBAL_FILENAME."$FileSuffix.pdf";

             // Generate the PDF
             @unlink($PDFFilename);
             if (html2pdf($HTMLFilename, $PDFFilename, 'landscape', ALL_MONTHLY_BILLS_DOCTYPE))
             {
                 // Delete the HTML file
                 unlink($HTMLFilename);

                 // Create link to download the PDF containing all bills of the month
                 if (file_exists($PDFFilename))
                 {
                     // Force the download of the PDF file
                     $PDFsize = filesize($PDFFilename);
                     $PDFTmpFilename = basename($PDFFilename);
                     header("Content-Type: application/octet-stream");
                     header("Content-Length: $PDFsize");
                     header("Content-disposition: attachment; filename=$PDFTmpFilename");
                     header("Pragma: no-cache;");
                     header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
                     header("Expires: 0");
                     readfile($PDFFilename);
                 }
             }
         }
     }
     else
     {
         // No bill for this month
         $sErrorMsg = $LANG_ERROR_NO_BILL_FOR_THIS_MONTH;
     }
 }
 //################################ END FORM PROCESSING ##########################

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
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#lYearMonth', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_GenerateMonthlyBill);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_TITLE, 2);

 // Check if there is an error message to display
 if (!empty($sErrorMsg))
 {
     openParagraph('ErrorMsg');
     echo $sErrorMsg;
     closeParagraph();
 }
 elseif (!empty($sConfirmationMsg))
 {
     openParagraph('ConfirmationMsg');
     displayStyledText($sConfirmationMsg, 'ShortConfirmMsg');
     closeParagraph();
 }

 // We generate the bills of the selected year and month
 openParagraph();
 displayStyledText($LANG_SUPPORT_GENERATE_MONTHLY_BILLS_PAGE_INTRODUCTION." <strong>".$CONF_PLANNING_MONTHS[$Month - 1]." $Year</strong>.", "");
 closeParagraph();

 // We display the form to generate bills
 displayGenerateMonthlyBillsForm($DbCon, "GenerateMonthlyBills.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_BILL],
                                 $bChkSendmail);

 // We display the form to resend by mail bills
 displayResendMonthlyBillsForm($DbCon, "GenerateMonthlyBills.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_BILL]);

 // We display the form to regenerate the document containing all bills of the month
 displayRegenerateAllMonthlyBillsSynthesisForm($DbCon, "GenerateMonthlyBills.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_BILL]);

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