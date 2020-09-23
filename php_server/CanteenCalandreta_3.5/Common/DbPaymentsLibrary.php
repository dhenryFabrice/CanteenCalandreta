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
 * Common module : library of database functions used for the Payments table
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2012-01-26
 */


/**
 * Check if a payment exists in the Payments table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-26
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $PaymentID            Integer      ID of the payment searched [1..n]
 *
 * @return Boolean              TRUE if the payment exists, FALSE otherwise
 */
 function isExistingPayment($DbConnection, $PaymentID)
 {
     $DbResult = $DbConnection->query("SELECT PaymentID FROM Payments WHERE PaymentID = $PaymentID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The payment exists
             return TRUE;
         }
     }

     // The payment doesn't exist
     return FALSE;
 }


/**
 * Check if a payment is unique for a bank and a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-01-25
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FamilyID             Integer      ID of the family linked to the payment [1..n]
 * @param $BankID               Integer      ID of the bank if payment is a check [1..n], NULL otherwise
 * @param $PaymentCheckNb       String       Check number
 * @param $PaymentID            Integer      ID of the payment if already exists [1..n]
 *
 * @return Boolean              TRUE if the payment is unique, FALSE otherwise
 */
 function isUniquePayment($DbConnection, $FamilyID, $BankID, $PaymentCheckNb, $PaymentID = NULL)
 {
     if ($FamilyID > 0)
     {
         if (empty($BankID))
         {
             return TRUE;
         }
         else if ($BankID > 0)
         {
             if (!empty($PaymentCheckNb))
             {
                 $Condition = '';
                 if ((!empty($PaymentID)) && ($PaymentID > 0))
                 {
                     $Condition = " AND PaymentID <> $PaymentID";
                 }

                 // Check if the payment is unique for the family
                 $DbResult = $DbConnection->query("SELECT PaymentID FROM Payments WHERE FamilyID = $FamilyID AND BankID = $BankID
                                                   AND PaymentCheckNb = \"$PaymentCheckNb\" $Condition");
                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() == 0)
                     {
                         return TRUE;
                     }
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Add a payment in the Payments table
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *     - 2016-10-12 : allow to force the part amount of a payment allocated to a bill
 *
 * @since 2012-01-26
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $PaymentDate                   Datetime     Creation date of the payment (yyyy-mm-dd hh:mm:ss)
 * @param $PaymentReceiptDate            Date         Date of the receipt of the payment (yyyy-mm-dd)
 * @param $FamilyID                      Integer      ID of the family linked to the payment [1..n]
 * @param $PaymentAmount                 Float        Amount of the payment (> 0.00)
 * @param $PaymentType                   Integer      Type of the payment [0..n]
 * @param $PaymentMode                   Integer      Mode of the payment [0..n]
 * @param $PaymentCheckNb                String       Number of the check
 * @param $BankID                        Integer      ID of the bank if payment is a check [1..n], NULL otherwise
 * @param $BillID                        Integer      ID of the bill for which the payment is [1..n], NULL otherwise.
 *                                                    Can be an array if payment linked to several bills
 * @param $BillPartAmount                Float        Part amount of the payment allocated to the bill, NULL otherwise.
 *                                                    Can be an array if payment linked to several bills
 *
 * @return Integer                       The primary key of the payment [1..n], 0 otherwise
 */
 function dbAddPayment($DbConnection, $PaymentDate, $PaymentReceiptDate, $FamilyID, $PaymentAmount, $PaymentType = 0, $PaymentMode = 0, $PaymentCheckNb = NULL, $BankID = NULL, $BillID = NULL, $BillPartAmount = NULL)
 {
     if ((!empty($PaymentDate)) && (!empty($PaymentReceiptDate)) && ($FamilyID > 0) && ($PaymentType >= 0) && ($PaymentMode >= 0) && ($PaymentAmount > 0))
     {
         // Check if the payment is a new payment
         $DbResult = $DbConnection->query("SELECT PaymentID FROM Payments WHERE PaymentReceiptDate = \"$PaymentReceiptDate\"
                                           AND FamilyID = $FamilyID AND PaymentAmount = $PaymentAmount");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the PaymentDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $PaymentDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $PaymentDate = ", PaymentDate = \"$PaymentDate\"";
                 }

                 // Check if the PaymentReceiptDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $PaymentReceiptDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $PaymentReceiptDate = ", PaymentReceiptDate = \"$PaymentReceiptDate\"";
                 }

                 if (empty($PaymentCheckNb))
                 {
                     $PaymentCheckNb = ", PaymentCheckNb = NULL";
                 }
                 else
                 {
                     $PaymentCheckNb = ", PaymentCheckNb = \"$PaymentCheckNb\"";
                 }

                 if ((!empty($BillID)) && (!is_array($BillID)) && ($BillID <= 0))
                 {
                     return 0;
                 }

                 if (empty($BankID))
                 {
                     $BankID = ", BankID = NULL";
                 }
                 elseif ($BankID <= 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $BankID = ", BankID = $BankID";
                 }

                 // It's a new payment
                 $id = getNewPrimaryKey($DbConnection, "Payments", "PaymentID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Payments SET PaymentID = $id, FamilyID = $FamilyID,
                                                       PaymentType = $PaymentType, PaymentMode = $PaymentMode,
                                                       PaymentAmount = $PaymentAmount $PaymentDate $PaymentReceiptDate $PaymentCheckNb $BankID");

                     if (!DB::isError($DbResult))
                     {
                         if (!empty($BillID))
                         {
                             // To link the payment to one or several bills
                             if (is_array($BillID))
                             {
                                 // Several bills
                                 foreach($BillID as $b => $CurrentBillID)
                                 {
                                     if (isset($BillPartAmount[$CurrentBillID]))
                                     {
                                         dbAddPaymentBill($DbConnection, $id, $CurrentBillID, $BillPartAmount[$CurrentBillID]);
                                     }
                                 }
                             }
                             else
                             {
                                 // One bill
                                 dbAddPaymentBill($DbConnection, $id, $BillID, $BillPartAmount);
                             }
                         }

                         return $id;
                     }
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing payment in the Payments table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *
 * @since 2012-01-26
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $PaymentID                     Integer      ID of the payment to update [1..n]
 * @param $PaymentDate                   Datetime     Creation date of the payment (yyyy-mm-dd hh:mm:ss)
 * @param $PaymentReceiptDate            Date         Date of the receipt of the payment (yyyy-mm-dd)
 * @param $FamilyID                      Integer      ID of the family linked to the payment [1..n]
 * @param $PaymentType                   Integer      Type of the payment [0..n]
 * @param $PaymentMode                   Integer      Mode of the payment [0..n]
 * @param $PaymentCheckNb                String       Number of the check
 * @param $BankID                        Integer      ID of the bank if payment is a check [1..n], NULL otherwise
 *
 * @return Integer                       The primary key of the payment [1..n], 0 otherwise
 */
 function dbUpdatePayment($DbConnection, $PaymentID, $PaymentDate, $PaymentReceiptDate, $FamilyID, $PaymentAmount, $PaymentType = NULL, $PaymentMode = NULL, $PaymentCheckNb = NULL, $BankID = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($PaymentID < 1) || (!isInteger($PaymentID)))
     {
         // ERROR
         return 0;
     }

     // Check if the PaymentDate is valide
     if (!is_null($PaymentDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $PaymentDate) == 0)
         {
             return 0;
         }
         else
         {
             // The PaymentDate field will be updated
             $ArrayParamsUpdate[] = "PaymentDate = \"$PaymentDate\"";
         }
     }

     // Check if the PaymentReceiptDate is valide
     if (!is_null($PaymentReceiptDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $PaymentReceiptDate) == 0)
         {
             return 0;
         }
         else
         {
             // The PaymentReceiptDate field will be updated
             $ArrayParamsUpdate[] = "PaymentReceiptDate = \"$PaymentReceiptDate\"";
         }
     }

     if (!is_null($FamilyID))
     {
         if (($FamilyID < 1) || (!isInteger($FamilyID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "FamilyID = $FamilyID";
         }
     }

     if (!is_null($PaymentAmount))
     {
         if ($PaymentAmount <= 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The PaymentAmount field will be updated
             $ArrayParamsUpdate[] = "PaymentAmount = $PaymentAmount";
         }
     }

     if (!is_Null($PaymentType))
     {
         if (($PaymentType < 0) || (!isInteger($PaymentType)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The PaymentType field will be updated
             $ArrayParamsUpdate[] = "PaymentType = $PaymentType";
         }
     }

     if (!is_Null($PaymentMode))
     {
         if (($PaymentMode < 0) || (!isInteger($PaymentMode)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The PaymentMode field will be updated
             $ArrayParamsUpdate[] = "PaymentMode = $PaymentMode";
         }
     }

     if (!is_null($PaymentCheckNb))
     {
         if (empty($PaymentCheckNb))
         {
             // The PaymentCheckNb field will be updated
             $ArrayParamsUpdate[] = "PaymentCheckNb = NULL";
         }
         else
         {
             // The PaymentCheckNb field will be updated
             $ArrayParamsUpdate[] = "PaymentCheckNb = \"$PaymentCheckNb\"";
         }
     }

     if (!is_null($BankID))
     {
         if (($BankID < 0) || (!isInteger($BankID)))
         {
             // ERROR
             return 0;
         }
         elseif (empty($BankID))
         {
             $ArrayParamsUpdate[] = "BankID = NULL";
         }
         else
         {
             $ArrayParamsUpdate[] = "BankID = $BankID";
         }
     }

     // Here, the parameters are correct, we check if the payment exists
     if (isExistingPayment($DbConnection, $PaymentID))
     {
         // We check if the payment name is unique for a family
         $DbResult = $DbConnection->query("SELECT PaymentID FROM Payments WHERE PaymentReceiptDate = \"$PaymentReceiptDate\"
                                          AND FamilyID = $FamilyID AND PaymentAmount = $PaymentAmount AND PaymentID <> $PaymentID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The payment exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Payments SET ".implode(", ", $ArrayParamsUpdate)." WHERE PaymentID = $PaymentID");
                     if (!DB::isError($DbResult))
                     {
                         // Payment updated
                         return $PaymentID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $PaymentID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the payments of a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 2.3
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *     - 2013-11-22 : allow to take into account more fields in the SELECT and teh array result
 *                    in relation with
 *     - 2013-12-16 : taken into account the "PaymentUsedAmount" field
 *     - 2015-06-03 : allow to filter payments on the PaymentDate field
 *     - 2018-01-22 : allow to filter payements not linked to bills
 *
 * @since 2012-01-26
 *
 * @param $DbConnection              DB object      Object of the opened database connection
 * @param $FamilyID                  Integer        ID of the family for which we want the payments [1..n]
 * @param $ArrayParams               Mixed array    Contains the criterion used to filter the payments of the given family
 * @param $OrderBy                   String         To order the payments
 *
 * @return Mixed array               All fields values of the payments of the family if it exists,
 *                                   an empty array otherwise
 */
 function getFamilyPayments($DbConnection, $FamilyID, $ArrayParams = array(), $OrderBy = 'PaymentDate')
 {
     if ($FamilyID > 0)
     {
         if (empty($OrderBy))
         {
             $OrderBy = 'PaymentDate';
         }

         $Conditions = '';
         $Having = '';

         $ArrayFrom = array();
         $SelectMoreFields = '';
         $ArrayMoreFields = array();
         $ArrayFromLeftJoin = array();
         if (!empty($ArrayParams))
         {
             if ((isset($ArrayParams['PaymentType'])) && (!empty($ArrayParams['PaymentType'])))
             {
                 $Conditions .= " AND p.PaymentType IN ".constructSQLINString($ArrayParams["PaymentType"]);
             }

             if ((isset($ArrayParams['BillID'])) && (!empty($ArrayParams['BillID'])) && (!isset($ArrayParams['WithoutBillID'])))
             {
                 if (!isset($ArrayFrom['PaymentsBills pb']))
                 {
                     $ArrayFrom['PaymentsBills pb'] = " AND p.PaymentID = pb.PaymentID";
                     $SelectMoreFields .= ", pb.PaymentBillPartAmount";
                     $ArrayMoreFields[] = "PaymentBillPartAmount";
                 }

                 $Conditions .= " AND pb.BillID IN ".constructSQLINString($ArrayParams["BillID"]);
             }

             if ((isset($ArrayParams['WithoutBillID'])) && ($ArrayParams['WithoutBillID']) && (!isset($ArrayParams['BillID'])))
             {
                 // We search payments without linked bill
                 if (!isset($ArrayFromLeftJoin['PaymentsBills pb']))
                 {
                     $ArrayFromLeftJoin['PaymentsBills pb'] = "ON (p.PaymentID = pb.PaymentID)";
                     $SelectMoreFields .= ", pb.PaymentBillID";

                     if (empty($Having))
                     {
                         $Having = "HAVING ";
                     }
                     else
                     {
                         $Having .= " AND ";
                     }

                     $Having .= "pb.PaymentBillID IS NULL";
                 }
             }

             if ((isset($ArrayParams['PaymentDate'])) && (count($ArrayParams['PaymentDate']) == 2))
             {
                 // $ArrayParams['PaymentDate'][0] contains the operator (>, >=, =...) and
                 // $ArrayParams['PaymentDate'][1] contains the date in english format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
                 $Conditions .= " AND p.PaymentDate ".$ArrayParams['PaymentDate'][0]." \"".$ArrayParams['PaymentDate'][1]."\"";
             }
         }

         $From = '';
         foreach($ArrayFrom as $f => $Cond)
         {
             $From .= ", $f";
             $Conditions .= $Cond;
         }

         $FromLeftJoin = '';
         foreach($ArrayFromLeftJoin as $f => $Cond)
         {
             $FromLeftJoin .= " LEFT JOIN $f $Cond";
         }

         // We get the payments of the family
         $DbResult = $DbConnection->query("SELECT p.PaymentID, p.PaymentDate, p.PaymentReceiptDate, p.PaymentType, p.PaymentMode,
                                          p.PaymentCheckNb, p.PaymentAmount, p.PaymentUsedAmount, b.BankID, b.BankName,
                                          b.BankAcronym $SelectMoreFields
                                          FROM Payments p LEFT JOIN Banks b ON (p.BankID = b.BankID) $FromLeftJoin $From
                                          WHERE FamilyID = $FamilyID $Conditions $Having ORDER BY $OrderBy");

         if (!DB::isError($DbResult))
         {
             // Creation of the result array
             $ArrayRecords = array(
                                  "PaymentID" => array(),
                                  "PaymentDate" => array(),
                                  "PaymentReceiptDate" => array(),
                                  "PaymentType" => array(),
                                  "PaymentMode" => array(),
                                  "PaymentCheckNb" => array(),
                                  "PaymentAmount" => array(),
                                  "PaymentUsedAmount" => array(),
                                  "BankID" => array(),
                                  "BankName" => array(),
                                  "BankAcronym" => array()
                                 );

             // Taken into account more fields in the SELECT
             if (!empty($ArrayMoreFields))
             {
                 foreach($ArrayMoreFields as $f => $Field)
                 {
                     $ArrayRecords[$Field] = array();
                 }
             }

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["PaymentID"][] = $Record["PaymentID"];
                 $ArrayRecords["PaymentDate"][] = $Record["PaymentDate"];
                 $ArrayRecords["PaymentReceiptDate"][] = $Record["PaymentReceiptDate"];
                 $ArrayRecords["PaymentType"][] = $Record["PaymentType"];
                 $ArrayRecords["PaymentMode"][] = $Record["PaymentMode"];
                 $ArrayRecords["PaymentCheckNb"][] = $Record["PaymentCheckNb"];
                 $ArrayRecords["PaymentAmount"][] = $Record["PaymentAmount"];
                 $ArrayRecords["PaymentUsedAmount"][] = $Record["PaymentUsedAmount"];
                 $ArrayRecords["BankID"][] = $Record["BankID"];
                 $ArrayRecords["BankName"][] = $Record["BankName"];
                 $ArrayRecords["BankAcronym"][] = $Record["BankAcronym"];

                 // Taken into account more fields in the SELECT
                 if (!empty($ArrayMoreFields))
                 {
                     foreach($ArrayMoreFields as $f => $Field)
                     {
                         $ArrayRecords[$Field][] = $Record[$Field];
                     }
                 }
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Delete a payment, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2016-07-12 : update the BillPaidAmount field
 *     - 2019-08-01 : taken into account FamilyAnnualContributionBalance field
 *
 * @since 2012-01-26
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $PaymentID                 Integer      ID of the payment to delete [1..n]
 *
 * @return Boolean                   TRUE if the payment is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeletePayment($DbConnection, $PaymentID)
 {
     // The parameters are correct?
     if ($PaymentID > 0)
     {
         // Get the amount of the payment to update the balance of the family
         $RecordPayment = getTableRecordInfos($DbConnection, 'Payments', $PaymentID);

         // Get bills linked to the payment
         $ArrayBills = getBillsOfPayment($DbConnection, $PaymentID, array(), 'BillID');

         // Delete the links between bills and the payment if exists
         $DbResult = $DbConnection->query("DELETE FROM PaymentsBills WHERE PaymentID = $PaymentID");
         if (!DB::isError($DbResult))
         {
             // Set the "Paid" flag of the bills to 0 and update paid amount of the bill
             // (remove part amount of the payment used to pay the bill)
             foreach($ArrayBills['BillID'] as $b => $BillID)
             {
                 $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = (BillPaidAmount - "
                                      .$ArrayBills['PaymentBillPartAmount'][$b].") WHERE BillID = $BillID");
             }
         }

         // Delete the payment in the table
         $DbResult = $DbConnection->query("DELETE FROM Payments WHERE PaymentID = $PaymentID");
         if (!DB::isError($DbResult))
         {
             // Update the balance : we remove the amount of the payment
             switch($RecordPayment['PaymentType'])
             {
                 case 0:
                     // Payment for annual contribution
                     updateFamilyAnnualContributionBalance($DbConnection, $RecordPayment['FamilyID'], -$RecordPayment['PaymentAmount']);
                     break;

                 case 1:
                 default:
                     updateFamilyBalance($DbConnection, $RecordPayment['FamilyID'], -$RecordPayment['PaymentAmount']);
                     break;
             }

             unset($RecordPayment);

             // Payment deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Add a link between a payment and a bill in the PaymentsBills table
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2013-11-20 : taken into account the new field "PaymentBillPartAmount"
 *     - 2016-10-12 : $PartAmount must be >= 0.00
 *
 * @since 2012-01-27
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $PaymentID                     Integer      ID of the payment [1..n]
 * @param $BillID                        Integer      ID of the bill [1..n]
 * @param $PartAmount                    Float        Amount of the payment affected to the bill ( > 0)
 *
 * @return Integer                       The primary key of the payment [1..n], 0 otherwise
 */
 function dbAddPaymentBill($DbConnection, $PaymentID, $BillID, $PartAmount = 0.00)
 {
     if (is_null($PartAmount))
     {
         $PartAmount = 0.00;
     }

     if (($PaymentID > 0) && ($BillID > 0) && ($PartAmount >= 0.00))
     {
         // Check if the link payment-bill is a new link
         $DbResult = $DbConnection->query("SELECT PaymentBillID FROM PaymentsBills WHERE PaymentID = $PaymentID AND BillID = $BillID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new link
                 $id = getNewPrimaryKey($DbConnection, "PaymentsBills", "PaymentBillID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO PaymentsBills SET PaymentBillID = $id, PaymentID = $PaymentID,
                                                       BillID = $BillID, PaymentBillPartAmount = $PartAmount");
                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update the paid amount of a bill, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-12-16
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $BillID               Integer      ID of the bill to update the paid amount [1..n]
 * @param $Value                Float        Value to add or remove to the current paid amount of the bill
 *
 * @return Float                The new paid amount of the bill, FALSE otherwise
 */
 function updatePaymentUsedAmount($DbConnection, $PaymentID, $Value)
 {
     $DbResult = $DbConnection->query("SELECT PaymentUsedAmount, PaymentAmount FROM Payments WHERE PaymentID = $PaymentID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Get the current amount and used amount of the payment
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);

             // Compute the new used amount
             $fNewUsedAmount = round((float)$Record['PaymentUsedAmount'], 2) + round((float)$Value, 2);

             // Check if the used amount is <= amount of the payment
             if ($fNewUsedAmount <= round((float)$Record['PaymentAmount'], 2))
             {
                 // Yes, so we set the new used amount
                 $DbResult = $DbConnection->query("UPDATE Payments SET PaymentUsedAmount = $fNewUsedAmount WHERE PaymentID = $PaymentID");
                 if (!DB::isError($DbResult))
                 {
                     return $fNewUsedAmount;
                 }
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Reset the used amount of a payment
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-28
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $PaymentID                     Integer      ID of the payment [1..n]
 *
 * @return Boolean                       TRUE if the reset is done, 0 otherwise
 */
 function dbResetPaymentUsedAmount($DbConnection, $PaymentID)
 {
     if ($PaymentID > 0)
     {
         $DbConnection->query("UPDATE Payments SET PaymentUsedAmount = 0 WHERE PaymentID = $PaymentID");
         return TRUE;
     }

     // Error
     return FALSE;
 }


/**
 * Remove the link between a payment and a bill in the PaymentsBills table but don't delete the payment
 * The balance of the concerned family isn't changed, but used amount of the payment and paid amount
 * of the bill are updated
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2013-12-17 : update the used amount of the payment and the paid amount of the bill
 *
 * @since 2013-01-22
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $PaymentID                     Integer      ID of the payment [1..n]
 * @param $BillID                        Integer      ID of the bill [1..n]
 *
 * @return Boolean                       TRUE if the link is deleted, 0 otherwise
 */
 function dbRemovePaymentBill($DbConnection, $PaymentID, $BillID)
 {
     if (($PaymentID > 0) && ($BillID > 0))
     {
         // Check if the link between the payment and bill exists
         $DbResult = $DbConnection->query("SELECT pb.PaymentBillID, p.PaymentUsedAmount, b.BillPaidAmount, pb.PaymentBillPartAmount
                                           FROM PaymentsBills pb INNER JOIN Bills b ON (pb.BillID = b.BillID AND pb.BillID = $BillID)
                                           INNER JOIN Payments p ON (pb.PaymentID = p.PaymentID AND pb.PaymentID = $PaymentID)");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 // Compute the amount to remouve from the used amount of the payment and the paid amount of the bill
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 $fNewUsedAmount = round((float)$Record['PaymentUsedAmount'], 2) - round((float)$Record['PaymentBillPartAmount'], 2);
                 $fNewPaidAmount = round((float)$Record['BillPaidAmount'], 2) - round((float)$Record['PaymentBillPartAmount'], 2);

                 // Delete
                 $DbResult = $DbConnection->query("DELETE FROM PaymentsBills WHERE PaymentID = $PaymentID AND BillID = $BillID");
                 if (!DB::isError($DbResult))
                 {
                     // Change the "Paid" flag of the bill and the value of the paid amount
                     $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewPaidAmount WHERE BillID = $BillID");

                     // Change the value of the used amount of the payment
                     $DbConnection->query("UPDATE Payments SET PaymentUsedAmount = $fNewUsedAmount WHERE PaymentID = $PaymentID");
                     return TRUE;
                 }
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Give the bills of one or several payments, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *     - 2013-11-21 : taken into account the new field "PaymentBillPartAmount"
 *     - 2013-12-16 : taken into account the new fields "PaymentUsedAmount" and "BillPaidAmount"
 *
 * @since 2012-03-08
 *
 * @param $DbConnection              DB object      Object of the opened database connection
 * @param $PaymentID                 Integer        ID of the concerned payment [1..n]. Can be an array
 * @param $ArrayParams               Mixed array    Contains the criterion used to filter the bills of the given payment
 * @param $OrderBy                   String         To order the bills
 *
 * @return Mixed array               All fields values of the bills if it exists,
 *                                   an empty array otherwise
 */
 function getBillsOfPayment($DbConnection, $PaymentID, $ArrayParams = array(), $OrderBy = 'BillID')
 {
     if ((is_null($PaymentID)) || ((!empty($PaymentID)) && ($PaymentID > 0)))
     {
         if (empty($OrderBy))
         {
             $OrderBy = 'BillID';
         }

         $Conditions = '';
         if (!is_null($PaymentID))
         {
             if (is_array($PaymentID))
             {
                 $Conditions .= " AND p.PaymentID IN ".constructSQLINString($PaymentID);
             }
             else
             {
                 $Conditions .= " AND p.PaymentID = $PaymentID";
             }
         }

         if (!empty($ArrayParams))
         {
             if ((isset($ArrayParams['PaymentType'])) && (!empty($ArrayParams['PaymentType'])))
             {
                 $Conditions .= " AND p.PaymentType IN ".constructSQLINString($ArrayParams["PaymentType"]);
             }

             if ((isset($ArrayParams['BillID'])) && (!empty($ArrayParams['BillID'])))
             {
                 $Conditions .= " AND b.BillID IN ".constructSQLINString($ArrayParams["BillID"]);
             }
         }

         // We get the bills of a payment
         $DbResult = $DbConnection->query("SELECT b.BillID, b.BillForDate, b.BillDeposit, b.BillPreviousBalance,
                                          b.BillMonthlyContribution, b.BillCanteenAmount, b.BillWithoutMealAmount, b.BillNurseryAmount,
                                          b.BillPaidAmount, b.BillPaid, p.PaymentID, p.PaymentDate, p.PaymentReceiptDate, p.PaymentType,
                                          p.PaymentAmount, p.PaymentUsedAmount, pb.PaymentBillPartAmount
                                          FROM Payments p, Bills b, PaymentsBills pb WHERE pb.PaymentID = p.PaymentID
                                          AND pb.BillID = b.BillID $Conditions ORDER BY $OrderBy");

         if (!DB::isError($DbResult))
         {
             // Creation of the result array
             $ArrayRecords = array(
                                  "BillID" => array(),
                                  "BillForDate" => array(),
                                  "BillDeposit" => array(),
                                  "BillPreviousBalance" => array(),
                                  "BillMonthlyContribution" => array(),
                                  "BillCanteenAmount" => array(),
                                  "BillWithoutMealAmount" => array(),
                                  "BillNurseryAmount" => array(),
                                  "BillPaidAmount" => array(),
                                  "BillPaid" => array(),
                                  "PaymentID" => array(),
                                  "PaymentDate" => array(),
                                  "PaymentReceiptDate" => array(),
                                  "PaymentType" => array(),
                                  "PaymentAmount" => array(),
                                  "PaymentUsedAmount" => array(),
                                  "PaymentBillPartAmount" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["BillID"][] = $Record["BillID"];
                 $ArrayRecords["BillForDate"][] = $Record["BillForDate"];
                 $ArrayRecords["BillDeposit"][] = $Record["BillDeposit"];
                 $ArrayRecords["BillPreviousBalance"][] = $Record["BillPreviousBalance"];
                 $ArrayRecords["BillMonthlyContribution"][] = $Record["BillMonthlyContribution"];
                 $ArrayRecords["BillCanteenAmount"][] = $Record["BillCanteenAmount"];
                 $ArrayRecords["BillWithoutMealAmount"][] = $Record["BillWithoutMealAmount"];
                 $ArrayRecords["BillNurseryAmount"][] = $Record["BillNurseryAmount"];
                 $ArrayRecords["BillPaidAmount"][] = $Record["BillPaidAmount"];
                 $ArrayRecords["BillPaid"][] = $Record["BillPaid"];
                 $ArrayRecords["PaymentID"][] = $Record["PaymentID"];
                 $ArrayRecords["PaymentDate"][] = $Record["PaymentDate"];
                 $ArrayRecords["PaymentReceiptDate"][] = $Record["PaymentReceiptDate"];
                 $ArrayRecords["PaymentType"][] = $Record["PaymentType"];
                 $ArrayRecords["PaymentAmount"][] = $Record["PaymentAmount"];
                 $ArrayRecords["PaymentUsedAmount"][] = $Record["PaymentUsedAmount"];
                 $ArrayRecords["PaymentBillPartAmount"][] = $Record["PaymentBillPartAmount"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Set the BillPaid" flag to 1 for concerned bills by a payment
 *
 * @author Christophe Javouhey
 * @version 4.0
 *     - 2013-01-31 : manage the case of the first bill of the family with a
 *                    previous balance <> 0
 *     - 2013-11-22 : taken into account the computation of the value to set in the "PaymentBillPartAmount"
 *                    field of the PaymentsBills table
 *     - 2013-12-17 : taken into account the "PaymentUsedAmount" and "BillPaidAmount" fields and the new
 *                    "DiffPaymentAmount" parameter
 *     - 2014-01-31 : patch a bug to compute part amount of a payment linked to a bill
 *     - 2014-07-02 : patch a bug to check if available amount == Bill amount to pay (round pb) and treat the case
 *                    if $DiffPaymentAmount < 0.00
 *     - 2015-01-21 : patch a bug with PaymentBillPartAmount == 0.00 (new link between a payment and a bill).
 *                    Add max($fPaymentUsedAmount, $fCurrentUsedAmount) to compute $fAvailablePaymentAmount
 *                    in order to use the right payment used amount. For fDiffAmount == 0.00, taken into account
 *                    the case $BillAmount == 0.00
 *     - 2016-10-12 : taken into account the manual part amount of a payment allocated to a bill
 *
 * @since 2012-02-08
 *
 * @param $DbConnection                         DB object    Object of the opened database connection
 * @param $PaymentID                            Integer      ID of the payment [1..n]
 * @param $DiffPaymentAmount                    Float        The amount the given payment ID has changed (+/-)
 * @param $NewPaymentManualBillPartAmount       Float        The manual part amount of the payment allocated to the bill
 *                                                           in the case of a new link payment/bill
 * @param $UpdatePaymentManualBillPartAmount    Float        The manual part amount of the payment allocated to the bill
 *                                                           in the case of an update of link payment/bill
 *
 * @return Boolean                              TRUE if flags set, FALSE otherwise
 */
 function dbSetBillsPaid($DbConnection, $PaymentID, $DiffPaymentAmount = 0.00, $NewPaymentManualBillPartAmount = NULL, $UpdatePaymentManualBillPartAmount = NULL)
 {
     if ($PaymentID > 0)
     {
         $DiffPaymentAmount = round($DiffPaymentAmount, 3);

         // Next, we get the bills linked to the payment
         $ArrayBillsOfCurrentPayment = getBillsOfPayment($DbConnection, $PaymentID, array(), 'BillID');
         if ((isset($ArrayBillsOfCurrentPayment['BillID'])) && (!empty($ArrayBillsOfCurrentPayment['BillID'])))
         {
             $PaymentAmount = $ArrayBillsOfCurrentPayment['PaymentAmount'][0];
             $fPaymentUsedAmount = $ArrayBillsOfCurrentPayment['PaymentUsedAmount'][0];
             $fInitialPaymentUsedAmount = $fPaymentUsedAmount;
             $fCurrentUsedAmount = 0.00;

             foreach($ArrayBillsOfCurrentPayment['BillID'] as $b => $BillID)
             {
                 // We check if the current bill is the first bill of the family
                 // if yes, we must take into account the previous balance of the bill
                 if (isFirstBillOfFamily($DbConnection, $BillID))
                 {
                     // Get the total amount of the bill with its previous balance amount
                     $BillAmount = getBillAmount($DbConnection, $BillID, WITH_PREVIOUS_BALANCE);
                 }
                 else
                 {
                     // Get the total amount of the bill (without its previous balance amount )
                     $BillAmount = getBillAmount($DbConnection, $BillID);
                 }

                 $bBillNewManualParAmount = FALSE;
                 if (!is_null($NewPaymentManualBillPartAmount))
                 {
                     if (is_array($NewPaymentManualBillPartAmount))
                     {
                         if (isset($NewPaymentManualBillPartAmount[$BillID]))
                         {
                             $bBillManualParAmount = TRUE;
                         }
                     }
                     else
                     {
                         $bBillManualParAmount = TRUE;
                     }
                 }

                 // It's a new link between the payment and the bill ?
                 if (($ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b] == 0.00) || ($bBillNewManualParAmount))
                 {
                     // Yes, we compute the part of paid amount
                     $fBillAmountToPay = $BillAmount - $ArrayBillsOfCurrentPayment['BillPaidAmount'][$b];

                     if (is_null($NewPaymentManualBillPartAmount))
                     {
                         //######## We auto-compute the part amount of the payment to allocate to the bill ########
                         // In relation with different treated cases $fPaymentUsedAmount and $fCurrentUsedAmount can be different
                         // We must use the right payment used mount (computed in this funciotn or stored in the DB)
                         $fAvailablePaymentAmount = max(0, $PaymentAmount - max($fPaymentUsedAmount, $fCurrentUsedAmount));
                     }
                     else
                     {
                         //######## Manual ########
                         if (is_array($NewPaymentManualBillPartAmount))
                         {
                             // Keys are BillID and values, part amount of the payment for each bill
                             $fAvailablePaymentAmount = 0.0;
                             if (isset($NewPaymentManualBillPartAmount[$BillID]))
                             {
                                 // Manual
                                 $fAvailablePaymentAmount = $NewPaymentManualBillPartAmount[$BillID];
                             }
                             else
                             {
                                 // Auto-computing because part amount of the payment not defined for this bill !
                                 $fAvailablePaymentAmount = max(0, $PaymentAmount - max($fPaymentUsedAmount, $fCurrentUsedAmount));
                             }
                         }
                         else
                         {
                             $fAvailablePaymentAmount = $NewPaymentManualBillPartAmount;
                         }
                     }

                     // We compute $fAvailablePaymentAmount - $fBillAmountToPay because of a pb with the test == between the 2 numbers
                     $fDiffAmount = round($fAvailablePaymentAmount - $fBillAmountToPay, 3);

                     if ($fDiffAmount < 0)
                     {
                         // No enough money to pay the bill
                         $fNewPartAmount = round($fAvailablePaymentAmount, 2);
                         $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b] + $fNewPartAmount, 2);

                         if (is_null($NewPaymentManualBillPartAmount))
                         {
                             // Auto-compute part amount of the payment for the bill
                             $fCurrentUsedAmount = $PaymentAmount;
                         }
                         else
                         {
                             // Manual
                             if (is_array($NewPaymentManualBillPartAmount))
                             {
                                 // Keys are BillID and values, part amount of the payment for each bill
                                 if (isset($NewPaymentManualBillPartAmount[$BillID]))
                                 {
                                     // Manual
                                     $fCurrentUsedAmount += $NewPaymentManualBillPartAmount[$BillID];
                                 }
                                 else
                                 {
                                     // Auto-computing because part amount of the payment not defined for this bill !
                                     $fCurrentUsedAmount = $PaymentAmount;
                                 }
                             }
                             else
                             {
                                 $fCurrentUsedAmount = $NewPaymentManualBillPartAmount;
                             }
                         }

                         // ...We update the part amount of payment for this bill
                         $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                           WHERE PaymentID = $PaymentID AND BillID = $BillID");

                         // ...The bill isn't paid
                         $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewBillPaidAmount
                                                           WHERE BillID = $BillID");

                         // ...The payment is totaly used, so we can't treat next bills !
                     }
                     elseif ($fDiffAmount == 0.00)
                     {
                         // Just enough money to pay the bill
                         $fNewPartAmount = round($fAvailablePaymentAmount, 2);
                         $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b] + $fNewPartAmount, 2);

                         // Don't update $fCurrentUsedAmount if the bill has got an amount == 0.00
                         if ($BillAmount != 0.00)
                         {
                             $fCurrentUsedAmount = $PaymentAmount;
                         }

                         // ...We update the part amount of payment for this bill
                         $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                           WHERE PaymentID = $PaymentID AND BillID = $BillID");

                         // ...The bill is paid
                         $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 1, BillPaidAmount = $fNewBillPaidAmount
                                                           WHERE BillID = $BillID");

                         // ...The payment is totaly used, so we can't treat next bills !
                     }
                     else
                     {
                         // Enough money to pay this bill and next
                         $fNewPartAmount = round($fBillAmountToPay, 2);
                         $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b] + $fNewPartAmount, 2);
                         $fCurrentUsedAmount += $fNewPartAmount;

                         // We update fPaymentUsedAmount to compute correctly the next fAvailablePaymentAmount
                         $fPaymentUsedAmount = $fCurrentUsedAmount;

                         // ...We update the part amount of payment for this bill
                         $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                           WHERE PaymentID = $PaymentID AND BillID = $BillID");

                         // ...The bill is paid
                         $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 1, BillPaidAmount = $fNewBillPaidAmount
                                                           WHERE BillID = $BillID");

                         // ...The payment isn't totaly used, so we can treat next bills...
                     }
                 }
                 else
                 {
                     // We check if the part amount allocated to the bill is correct in relation with the total payment amount
                     if ($fCurrentUsedAmount + $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b] <= $PaymentAmount)
                     {
                         // Yes, so we check if the bill is paid and if it's possible to change the part amount for this bill
                         $bBillUpdateManualParAmount = FALSE;
                         if (!is_null($UpdatePaymentManualBillPartAmount))
                         {
                             if (is_array($UpdatePaymentManualBillPartAmount))
                             {
                                 if (isset($UpdatePaymentManualBillPartAmount[$BillID]))
                                 {
                                     $bBillUpdateManualParAmount = TRUE;
                                 }
                             }
                             else
                             {
                                 $bBillUpdateManualParAmount = TRUE;
                             }
                         }

                         if (($ArrayBillsOfCurrentPayment['BillPaid'][$b] == 1) && (!$bBillUpdateManualParAmount))
                         {
                             // No change of the part amount for a paid bill in auto-computing mode
                             $fCurrentUsedAmount += $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b];
                         }
                         else
                         {
                             // Bill not paid, we can change the part amount (only in auto-computing mode) ?
                             if (($DiffPaymentAmount > 0.00) && (!$bBillUpdateManualParAmount))
                             {
                                 // Yes, we can increase the part amount for this bill
                                 $fBillAmountToPay = $BillAmount - $ArrayBillsOfCurrentPayment['BillPaidAmount'][$b];
                                 $fNewPartAmount = min($DiffPaymentAmount, $fBillAmountToPay);
                                 $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b] + $fNewPartAmount, 2);
                                 $fNewPartAmount += $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b];
                                 $fCurrentUsedAmount += $fNewPartAmount;

                                 // ...We update the part amount of payment for this bill
                                 $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                                   WHERE PaymentID = $PaymentID AND BillID = $BillID");

                                 if (round($BillAmount - $fNewBillPaidAmount, 3) < 0.00)
                                 {
                                     // Bill not paid
                                     $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewBillPaidAmount
                                                                       WHERE BillID = $BillID");
                                 }
                                 else
                                 {
                                     // Bill paid
                                     $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 1, BillPaidAmount = $fNewBillPaidAmount
                                                                       WHERE BillID = $BillID");
                                 }
                             }
                             elseif (($DiffPaymentAmount < 0.00) && (!$bBillUpdateManualParAmount))
                             {
                                 $fNewPartAmount = max(0, round($ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b] - $DiffPaymentAmount, 2));
                                 $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b]
                                                             - $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b]
                                                             + $fNewPartAmount, 2);
                                 $DiffPaymentAmount -= $fNewPartAmount;
                                 $fCurrentUsedAmount += $fNewPartAmount;

                                 // ...We update the part amount of payment for this bill
                                 $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                                   WHERE PaymentID = $PaymentID AND BillID = $BillID");

                                 if (round($BillAmount - $fNewBillPaidAmount, 3) < 0.00)
                                 {
                                     // Bill not paid
                                     $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewBillPaidAmount
                                                                       WHERE BillID = $BillID");
                                 }
                                 else
                                 {
                                     // Bill paid
                                     $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 1, BillPaidAmount = $fNewBillPaidAmount
                                                                       WHERE BillID = $BillID");
                                 }
                             }
                             else
                             {
                                 if (!$bBillUpdateManualParAmount)
                                 {
                                     // No change of the part amount (auto-computing mode)
                                     $fCurrentUsedAmount += $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b];
                                 }
                                 else
                                 {
                                     // We compare the part amount in database and the entered part amount
                                     $fDiffDbPartAmountEnteredValue = 0.00;
                                     if (is_array($UpdatePaymentManualBillPartAmount))
                                     {
                                         if (isset($UpdatePaymentManualBillPartAmount[$BillID]))
                                         {
                                             $fDiffDbPartAmountEnteredValue = round($UpdatePaymentManualBillPartAmount[$BillID]
                                                                                    - $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b], 3);
                                         }
                                     }
                                     else
                                     {
                                         $fDiffDbPartAmountEnteredValue = round($UpdatePaymentManualBillPartAmount
                                                                                - $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b], 3);
                                     }

                                     if ($fDiffDbPartAmountEnteredValue > 0.00)
                                     {
                                         // We increase the part amount for this bill
                                         $fBillAmountToPay = $BillAmount - $ArrayBillsOfCurrentPayment['BillPaidAmount'][$b];
                                         $fNewPartAmount = min($fDiffDbPartAmountEnteredValue, $fBillAmountToPay);
                                         $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b] + $fNewPartAmount, 2);
                                         $fNewPartAmount += $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b];
                                         $fCurrentUsedAmount += $fNewPartAmount;

                                         // ...We update the part amount of payment for this bill
                                         $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                                           WHERE PaymentID = $PaymentID AND BillID = $BillID");

                                         if (round($BillAmount - $fNewBillPaidAmount, 3) > 0.00)
                                         {
                                             // Bill not paid
                                             $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewBillPaidAmount
                                                                               WHERE BillID = $BillID");
                                         }
                                         else
                                         {
                                             // Bill paid
                                             $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 1, BillPaidAmount = $fNewBillPaidAmount
                                                                               WHERE BillID = $BillID");
                                         }
                                     }
                                     elseif ($fDiffDbPartAmountEnteredValue < 0.00)
                                     {
                                         $fNewPartAmount = max(0, round($ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b] + $fDiffDbPartAmountEnteredValue, 2));
                                         $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b]
                                                                     - $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b]
                                                                     + $fNewPartAmount, 2);
                                         $fCurrentUsedAmount += $fNewPartAmount;

                                         // ...We update the part amount of payment for this bill
                                         $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                                           WHERE PaymentID = $PaymentID AND BillID = $BillID");

                                         if (round($BillAmount - $fNewBillPaidAmount, 3) > 0.00)
                                         {
                                             // Bill not paid
                                             $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewBillPaidAmount
                                                                               WHERE BillID = $BillID");
                                         }
                                         else
                                         {
                                             // Bill paid
                                             $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 1, BillPaidAmount = $fNewBillPaidAmount
                                                                               WHERE BillID = $BillID");
                                         }
                                     }
                                     else
                                     {
                                         // No change of the part amount
                                         $fCurrentUsedAmount += $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b];
                                     }
                                 }
                             }
                         }
                     }
                     else
                     {
                         // No, there is a problem : we have to remove money of this part amount...
                         $fNewPartAmount = round($PaymentAmount - $fCurrentUsedAmount, 2);
                         $fAmountToRemove = $ArrayBillsOfCurrentPayment['PaymentBillPartAmount'][$b] - $fNewPartAmount;
                         $fNewBillPaidAmount = round($ArrayBillsOfCurrentPayment['BillPaidAmount'][$b] - $fAmountToRemove, 2);
                         $fCurrentUsedAmount = $PaymentAmount;

                         // ...We update the part amount of payment for this bill
                         $DbResult = $DbConnection->query("UPDATE PaymentsBills SET PaymentBillPartAmount = $fNewPartAmount
                                                           WHERE PaymentID = $PaymentID AND BillID = $BillID");

                         // ...The bill isn't paid
                         $DbResult = $DbConnection->query("UPDATE Bills SET BillPaid = 0, BillPaidAmount = $fNewBillPaidAmount
                                                           WHERE BillID = $BillID");

                         // ...The payment is totaly used, so we can't treat next bills !
                     }
                 }
             }

             // We update the used amount of the payment if different
             if ($fCurrentUsedAmount != $fInitialPaymentUsedAmount)
             {
                 $DbResult = $DbConnection->query("UPDATE Payments SET PaymentUsedAmount = $fCurrentUsedAmount
                                                   WHERE PaymentID = $PaymentID");
             }
         }

         return TRUE;
     }

     // ERROR
     return FALSE;
 }


/**
 * Get used amount and not used amount of a payment linked to one or several bills
 *
 * @author Christophe Javouhey
 * @version 2.0
 *    - 2013-11-22 : taken into account the new field "PaymentBillPartAmount"
 *                   of the PaymentsBills table
 *
 * @since 2013-01-29
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $PaymentID                     Integer      ID of the payment [1..n]
 *
 * @return Array of floats               2 floats in an array, the used amount and the not used amount,
 *                                       FALSE otherwise
 */
 function getPaymentProgress($DbConnection, $PaymentID)
 {
     if ($PaymentID > 0)
     {
         // Get the amount of the payment
         $PaymentAmount = getTableFieldValue($DbConnection, 'Payments', $PaymentID, 'PaymentAmount');
         $PaymentUsedAmount = 0;
         $PaymentUsedNotAmount = $PaymentAmount;

         // Get part amounts linked to each bill
         $DbResult = $DbConnection->query("SELECT PaymentID, BillID, PaymentBillPartAmount FROM PaymentsBills
                                           WHERE PaymentID = $PaymentID ORDER BY BillID");

         if (!DB::isError($DbResult))
         {
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $PaymentUsedAmount += $Record['PaymentBillPartAmount'];
             }

             $PaymentUsedNotAmount = $PaymentAmount - $PaymentUsedAmount;
         }

         return array(round($PaymentUsedAmount, 2), round($PaymentUsedNotAmount, 2));
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the not totally used payments for one or several families, thanks to their ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-01-31
 *
 * @param $DbConnection              DB object      Object of the opened database connection
 * @param $FamilyID                  Integer        ID of the concerned family [1..n]. Can be an array
 * @param $ArrayParams               Mixed array    Contains the criterion used to filter the payments
 * @param $OrderBy                   String         To order the not used payments
 *
 * @return Mixed array               Some fields values of the not totally used payments,
 *                                   an empty array otherwise
 */
 function getPaymentsNotUsed($DbConnection, $FamilyID = NULL, $ArrayParams = array(), $OrderBy = 'FamilyLastname')
 {
     $Conditions = '';
     if (!is_null($FamilyID))
     {
         if (is_array($FamilyID))
         {
             $Conditions .= " AND p.FamilyID IN ".constructSQLINString($FamilyID);
         }
         else
         {
             $Conditions .= " AND p.FamilyID = $FamilyID";
         }
     }

     // We get not totally used payments
     $DbResult = $DbConnection->query("SELECT f.FamilyID, f.FamilyLastname, p.PaymentID, p.PaymentAmount, p.PaymentUsedAmount
                                      FROM Payments p, Families f WHERE p.FamilyID = f.FamilyID AND p.PaymentType = 1
                                      AND p.PaymentAmount > p.PaymentUsedAmount $Conditions ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         // Creation of the result array
         $ArrayRecords = array();

         while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
         {
             $ArrayRecords[$Record["FamilyID"]]["FamilyLastname"][] = $Record["FamilyLastname"];
             $ArrayRecords[$Record["FamilyID"]]["PaymentID"][] = $Record["PaymentID"];
             $ArrayRecords[$Record["FamilyID"]]["PaymentAmount"][] = $Record["PaymentAmount"];
             $ArrayRecords[$Record["FamilyID"]]["PaymentUsedAmount"][] = $Record["PaymentUsedAmount"];
         }

         // Return result
         return $ArrayRecords;
     }

     // ERROR
     return array();
 }
?>