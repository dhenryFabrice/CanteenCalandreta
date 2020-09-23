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
 * Common module : library of database functions used for the Bills table
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2012-02-21
 */


/**
 * Check if a bill exists in the Bills table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-21
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $BillID               Integer      ID of the bill searched [1..n]
 *
 * @return Boolean              TRUE if the bill exists, FALSE otherwise
 */
 function isExistingBill($DbConnection, $BillID)
 {
     $DbResult = $DbConnection->query("SELECT BillID FROM Bills WHERE BillID = $BillID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The bill exists
             return TRUE;
         }
     }

     // The bill doesn't exist
     return FALSE;
 }


/**
 * Check if a bill if the first bill of the family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-01-31
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $BillID               Integer      ID of the bill searched [1..n]
 *
 * @return Boolean              TRUE if the bill is the first bill of the family,
 *                              FALSE otherwise
 */
 function isFirstBillOfFamily($DbConnection, $BillID)
 {
     if ($BillID > 0)
     {
         $DbResult = $DbConnection->query("SELECT bf.BillID FROM Bills b, Bills bf WHERE b.BillID = $BillID AND b.FamilyID = bf.FamilyID
                                          ORDER BY bf.BillID LIMIT 0, 1");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 if ($BillID == $Record['BillID'])
                 {
                     // The given bill is the first of the family
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Check if a bill if the last bill of the family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-03
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $BillID               Integer      ID of the bill searched [1..n]
 *
 * @return Boolean              TRUE if the bill is the last bill of the family,
 *                              FALSE otherwise
 */
 function isLastBillOfFamily($DbConnection, $BillID)
 {
     if ($BillID > 0)
     {
         $DbResult = $DbConnection->query("SELECT bf.BillID FROM Bills b, Bills bf WHERE b.BillID = $BillID AND b.FamilyID = bf.FamilyID
                                          ORDER BY bf.BillID DESC LIMIT 0, 1");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 if ($BillID == $Record['BillID'])
                 {
                     // The given bill is the last of the family
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Gte the ID of the bill for a family and a year/month if it exists
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-23
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $FamilyID             Integer      ID of the concerned family [1..n]
 * @param $BillForDate          Date         Date for the searched monthly bill (yyyy-mm-dd)
 *
 * @return Integer              ID of the bill if it exists, 0 otherwise
 */
 function getMonthlyBillIDForFamily($DbConnection, $FamilyID, $BillForDate)
 {
     if (($FamilyID > 0) && (preg_match("[\d\d\d\d-\d\d-\d\d]", $BillForDate) != 0))
     {
         $DbResult = $DbConnection->query("SELECT BillID FROM Bills WHERE DATE_FORMAT(BillForDate,'%Y-%m') = \""
                                          .date("Y-m", strtotime($BillForDate))."\" AND FamilyID = $FamilyID");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 // The bill exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['BillID'];
             }
         }
     }

     // The bill doesn't exist
     return 0;
 }


/**
 * Add a bill for a family and a year/month in the Bills table
 *
 * @author Christophe Javouhey
 * @version 1.4
 *     - 2013-12-16 : v1.1. Taken into account the BillPaidAmount field
 *     - 2014-02-03 : v1.2. Taken into account the BillNurseryNbDelays field
 *     - 2015-06-03 : v1.3. BillDate field is now a datetime
 *     - 2020-03-09 : v1.4. Taken into account BillNbCanteenRegistrations and BillNbNurseryRegistrations
 *                          fields
 *
 * @since 2012-02-23
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $BillDate                          Datetime     Creation date of the bill (yyyy-mm-dd hh:mm:ss)
 * @param $BillForDate                       Date         Date for which the bill is (yyyy-mm-dd)
 * @param $FamilyID                          Integer      ID of the family concerned by the bill [1..n]
 * @param $BillPreviousBalance               Float        Previous balance of the family (>= 0 or <= 0)
 * @param $BillDeposit                       Float        If the family has set a deposit (>= 0)
 * @param $BillMonthlyContribution           Float        Contribution of the family for the month (>= 0)
 * @param $BillCanteenAmount                 Float        Amount for the canteen registrations (>= 0)
 * @param $BillWithoutMealAmount             Float        Amount for the not valided canteen registrations (>= 0)
 * @param $BillNurseryAmount                 Float        Amount for the nursery registrations (>= 0)
 * @param $BillPaidAmount                    Float        Amount of the bill paid (>= 0 and <= bill amount)
 * @param $BillNurseryNbDelays               Integer      Number of nursery delays [0..n]
 * @param $BillNbCanteenRegistrations        Integer      Number of canteen registrations (NULL or [0..n])
 * @param $BillNbNurseryRegistrations        Integer      Number of nursery registrations (NULL or [0..n])
 *
 * @return Integer                           The primary key of the bill [1..n], 0 otherwise
 */
 function dbAddBill($DbConnection, $BillDate, $BillForDate, $FamilyID, $BillPreviousBalance = 0, $BillDeposit = 0, $BillMonthlyContribution = 0, $BillCanteenAmount = 0, $BillWithoutMealAmount = 0, $BillNurseryAmount = 0, $BillPaidAmount = 0.00, $BillNurseryNbDelays = 0, $BillNbCanteenRegistrations = NULL, $BillNbNurseryRegistrations = NULL)
 {
     if (($FamilyID > 0) && ($BillDeposit >= 0) && ($BillMonthlyContribution >= 0) && ($BillCanteenAmount >= 0)
         && ($BillWithoutMealAmount >= 0) && ($BillNurseryAmount >= 0) && ($BillPaidAmount >= 0) && ($BillNurseryNbDelays >= 0))
     {
         // Check if the bill is a new bill for the family and the year/month
         $DbResult = $DbConnection->query("SELECT BillID FROM Bills WHERE DATE_FORMAT(BillForDate,'%Y-%m') = \""
                                          .date("Y-m", strtotime($BillForDate))."\" AND FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the BillDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $BillDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $BillDate = ", BillDate = \"$BillDate\"";
                 }

                 // Check if the BillForDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $BillForDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $BillForDate = ", BillForDate = \"$BillForDate\"";
                 }

                 // Check if the number of canteen registrations is valide
                 if (is_null($BillNbCanteenRegistrations))
                 {
                     $BillNbCanteenRegistrations = ", BillNbCanteenRegistrations = NULL";
                 }
                 else
                 {
                     if ($BillNbCanteenRegistrations < 0)
                     {
                         // Error
                         return 0;
                     }
                     else
                     {
                         $BillNbCanteenRegistrations = ", BillNbCanteenRegistrations = $BillNbCanteenRegistrations";
                     }
                 }

                 // Check if the number of nursery registrations is valide
                 if (is_null($BillNbNurseryRegistrations))
                 {
                     $BillNbNurseryRegistrations = ", BillNbNurseryRegistrations = NULL";
                 }
                 else
                 {
                     if ($BillNbNurseryRegistrations < 0)
                     {
                         // Error
                         return 0;
                     }
                     else
                     {
                         $BillNbNurseryRegistrations = ", BillNbNurseryRegistrations = $BillNbNurseryRegistrations";
                     }
                 }

                 // It's a new bill
                 $id = getNewPrimaryKey($DbConnection, "Bills", "BillID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Bills SET BillID = $id, FamilyID = $FamilyID,
                                                      BillPreviousBalance = $BillPreviousBalance, BillDeposit = $BillDeposit,
                                                      BillMonthlyContribution = $BillMonthlyContribution, BillCanteenAmount = $BillCanteenAmount,
                                                      BillWithoutMealAmount = $BillWithoutMealAmount, BillNurseryAmount = $BillNurseryAmount,
                                                      BillNurseryNbDelays = $BillNurseryNbDelays, BillPaidAmount = $BillPaidAmount
                                                      $BillDate $BillForDate $BillNbCanteenRegistrations $BillNbNurseryRegistrations");

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
 * Update an existing bill for a family and a year/month in the Bills table
 *
 * @author Christophe Javouhey
 * @version 1.4
 *     - 2013-12-16 : v1.1. Taken into account the BillPaidAmount field
 *     - 2014-02-03 : v1.2. Taken into account the BillNurseryNbDelays field
 *     - 2015-06-03 : v1.3. TillDate field is now a datetime
 *     - 2020-03-09 : v1.4. Taken into account BillNbCanteenRegistrations and BillNbNurseryRegistrations
 *                          fields
 *
 * @since 2012-02-23
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $BillID                            Integer      ID of the bill to update [1..n]
 * @param $BillDate                          Datetime     Creation date of the bill (yyyy-mm-dd hh:mm:ss)
 * @param $BillForDate                       Date         Date for which the bill is (yyyy-mm-dd)
 * @param $FamilyID                          Integer      ID of the family concerned by the bill [1..n]
 * @param $BillPreviousBalance               Float        Previous balance of the family (>= 0 or <= 0)
 * @param $BillDeposit                       Float        If the family has set a deposit (>= 0)
 * @param $BillMonthlyContribution           Float        Contribution of the family for the month (>= 0)
 * @param $BillCanteenAmount                 Float        Amount for the canteen registrations (>= 0)
 * @param $BillWithoutMealAmount             Float        Amount for the not valided canteen registrations (>= 0)
 * @param $BillNurseryAmount                 Float        Amount for the nursery registrations (>= 0)
 * @param $BillPaidAmount                    Float        Amount of the bill paid (>= 0 and <= bill amount)
 * @param $BillNurseryNbDelays               Integer      Number of nursery delays [0..n]
 * @param $BillNbCanteenRegistrations        Integer      Number of canteen registrations (NULL or [0..n])
 * @param $BillNbNurseryRegistrations        Integer      Number of nursery registrations (NULL or [0..n])
 *
 * @return Integer                           The primary key of the canteen registration [1..n], 0 otherwise
 */
 function dbUpdateBill($DbConnection, $BillID, $BillDate, $BillForDate, $FamilyID, $BillPreviousBalance = NULL, $BillDeposit = NULL, $BillMonthlyContribution = NULL, $BillCanteenAmount = NULL, $BillWithoutMealAmount = NULL, $BillNurseryAmount = NULL, $BillPaidAmount = NULL, $BillNurseryNbDelays = NULL, $BillNbCanteenRegistrations = NULL, $BillNbNurseryRegistrations = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($BillID < 1) || (!isInteger($BillID)))
     {
         // ERROR
         return 0;
     }

     // Check if the BillDate is valide
     if (!is_null($BillDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $BillDate) == 0)
         {
             return 0;
         }
         else
         {
             // The BillDate field will be updated
             $ArrayParamsUpdate[] = "BillDate = \"$BillDate\"";
         }
     }

     // Check if the BillForDate is valide
     if (!is_null($BillForDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $BillForDate) == 0)
         {
             return 0;
         }
         else
         {
             // The BillForDate field will be updated
             $ArrayParamsUpdate[] = "BillForDate = \"$BillForDate\"";
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

     if (!is_Null($BillPreviousBalance))
     {
         // The BillPreviousBalance field will be updated
         $ArrayParamsUpdate[] = "BillPreviousBalance = $BillPreviousBalance";
     }

     if (!is_Null($BillDeposit))
     {
         if ($BillDeposit < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillDeposit field will be updated
             $ArrayParamsUpdate[] = "BillDeposit = $BillDeposit";
         }
     }

     if (!is_Null($BillMonthlyContribution))
     {
         if ($BillMonthlyContribution < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillMonthlyContribution field will be updated
             $ArrayParamsUpdate[] = "BillMonthlyContribution = $BillMonthlyContribution";
         }
     }

     if (!is_Null($BillCanteenAmount))
     {
         if ($BillCanteenAmount < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillCanteenAmount field will be updated
             $ArrayParamsUpdate[] = "BillCanteenAmount = $BillCanteenAmount";
         }
     }

     if (!is_Null($BillWithoutMealAmount))
     {
         if ($BillWithoutMealAmount < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillWithoutMealAmount field will be updated
             $ArrayParamsUpdate[] = "BillWithoutMealAmount = $BillWithoutMealAmount";
         }
     }

     if (!is_Null($BillNurseryAmount))
     {
         if ($BillNurseryAmount < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillNurseryAmount field will be updated
             $ArrayParamsUpdate[] = "BillNurseryAmount = $BillNurseryAmount";
         }
     }

     if (!is_Null($BillNurseryNbDelays))
     {
         if ($BillNurseryNbDelays < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillNurseryNbDelays field will be updated
             $ArrayParamsUpdate[] = "BillNurseryNbDelays = $BillNurseryNbDelays";
         }
     }

     if (!is_Null($BillPaidAmount))
     {
         if ($BillPaidAmount < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillPaidAmount field will be updated
             $ArrayParamsUpdate[] = "BillPaidAmount = $BillPaidAmount";
         }
     }

     if (!is_Null($BillNbCanteenRegistrations))
     {
         if ($BillNbCanteenRegistrations < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillNbCanteenRegistrations field will be updated
             $ArrayParamsUpdate[] = "BillNbCanteenRegistrations = $BillNbCanteenRegistrations";
         }
     }

     if (!is_Null($BillNbNurseryRegistrations))
     {
         if ($BillNbNurseryRegistrations < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The BillNbNurseryRegistrations field will be updated
             $ArrayParamsUpdate[] = "BillNbNurseryRegistrations = $BillNbNurseryRegistrations";
         }
     }

     // Here, the parameters are correct, we check if the bill exists
     if (isExistingBill($DbConnection, $BillID))
     {
         // We check if the bill is unique for a family and a year/month
         $DbResult = $DbConnection->query("SELECT BillID FROM Bills WHERE DATE_FORMAT(BillForDate,'%Y-%m') = \""
                                          .date("Y-m", strtotime($BillForDate))."\" AND FamilyID = $FamilyID AND BillID <> $BillID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The bill exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Bills SET ".implode(", ", $ArrayParamsUpdate)." WHERE BillID = $BillID");
                     if (!DB::isError($DbResult))
                     {
                         // Bill updated
                         return $BillID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $BillID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the bills between 2 dates.
 *
 * @author Christophe Javouhey
 * @version 1.6
 *     - 2013-01-20 : v1.1. Taken into account the "IncludeBillID" parameter (allow to get some given Bill ID
 *                    without taken into account other parameters)
 *     - 2013-12-16 : v1.2. Taken into account the BillPaidAmount field
 *     - 2014-02-03 : v1.3. Taken into account the BillNurseryNbDelays field
 *     - 2014-03-12 : v1.4. Add the $LimitRecords parameter
 *     - 2015-06-04 : v1.5. Allow to filter bills on the BillDate field
 *     - 2020-03-09 : v1.6. Taken into account BillNbCanteenRegistrations and BillNbNurseryRegistrations
 *                          fields
 *
 * @since 2012-02-23
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order bills
 * @param $Mode                 Enum                 Mode to find bills
 * @param $ArrayParams          Mixed Array          Other criterion to filter bills
 $ @param $LimitRecords         Integer              The max of records to keep [0..n]
 *
 * @return mixed Array                               The bills between the 2 dates,
 *                                                   an empty array otherwise
 */
 function getBills($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'BillForDate', $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array(), $LimitRecords = 0)
 {
     if (is_Null($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Conditions = "";
     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['BillID'])) && (count($ArrayParams['BillID']) > 0))
         {
             $Conditions .= " AND b.BillID IN ".constructSQLINString($ArrayParams['BillID']);
         }

         if ((isset($ArrayParams['FamilyID'])) && (count($ArrayParams['FamilyID']) > 0))
         {
             $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams['FamilyID']);
         }

         if ((isset($ArrayParams['BillDate'])) && (count($ArrayParams['BillDate']) == 2))
         {
             // $ArrayParams['BillDate'][0] contains the operator (>, >=, =...) and
             // $ArrayParams['BillDate'][1] contains the date in english format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
             $Conditions .= " AND b.BillDate ".$ArrayParams['BillDate'][0]." \"".$ArrayParams['BillDate'][1]."\"";
         }

         if ((isset($ArrayParams['BillForYearMonth'])) && (!empty($ArrayParams['BillForYearMonth'])))
         {
             $Conditions .= " AND DATE_FORMAT(b.BillForDate,'%Y-%m') = \"".$ArrayParams['BillForYearMonth']."\"";
         }

         if ((isset($ArrayParams['BillPaid'])) && (count($ArrayParams['BillPaid']) > 0))
         {
             $Conditions .= " AND b.BillPaid IN ".constructSQLINString($ArrayParams['BillPaid']);
         }

         if ((isset($ArrayParams['IncludeBillID'])) && (count($ArrayParams['IncludeBillID']) > 0))
         {
             $Conditions .= " OR (b.BillID IN ".constructSQLINString($ArrayParams['IncludeBillID']).")";
         }
     }

     $Select = "SELECT b.BillID, b.BillDate, b.BillForDate, b.BillPreviousBalance, b.BillDeposit, b.BillMonthlyContribution,
               b.BillNbCanteenRegistrations, b.BillCanteenAmount, b.BillWithoutMealAmount, b.BillNbNurseryRegistrations, b.BillNurseryAmount,
               b.BillNurseryNbDelays, b.BillPaidAmount, b.BillPaid,
               f.FamilyID, f.FamilyLastname, f.FamilyMainEmail, f.FamilySecondEmail";

     $From = "FROM Bills b, Families f";

     $Where = "WHERE b.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"$StartDate\" >= DATE_FORMAT(b.BillForDate,'%Y-%m-%d')
                       AND \"$EndDate\" <= DATE_FORMAT(b.BillForDate,'%Y-%m-%d')";
             break;

         case PLANNING_INCLUDED_IN_DATES:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND DATE_FORMAT(b.BillForDate,'%Y-%m-%d') >= \"$StartDate\"
                       AND DATE_FORMAT(b.BillForDate,'%Y-%m-%d') <= \"$EndDate\"";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"$StartDate\" BETWEEN DATE_FORMAT(b.BillForDate,'%Y-%m-%d') AND
                       DATE_FORMAT(b.BillForDate,'%Y-%m-%d')) OR (\"$EndDate\" BETWEEN DATE_FORMAT(b.BillForDate,'%Y-%m-%d')
                       AND DATE_FORMAT(b.BillForDate,'%Y-%m-%d')))";
             break;

         case NO_DATES:
             // Do nothing
             break;

         default:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND ((DATE_FORMAT(b.BillForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")
                       OR (DATE_FORMAT(b.BillForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\"))";
             break;
     }

     // There is a limit of max records to keep ?
     $Limit = '';
     if (!empty($LimitRecords))
     {
         $Limit = "LIMIT 0, $LimitRecords";
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY b.BillID ORDER BY $OrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "BillID" => array(),
                                   "BillDate" => array(),
                                   "BillForDate" => array(),
                                   "BillPreviousBalance" => array(),
                                   "BillDeposit" => array(),
                                   "BillMonthlyContribution" => array(),
                                   "BillNbCanteenRegistrations" => array(),
                                   "BillCanteenAmount" => array(),
                                   "BillWithoutMealAmount" => array(),
                                   "BillNbNurseryRegistrations" => array(),
                                   "BillNurseryAmount" => array(),
                                   "BillNurseryNbDelays" => array(),
                                   "BillPaidAmount" => array(),
                                   "BillPaid" => array(),
                                   "BillAmount" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array(),
                                   "FamilyMainEmail" => array(),
                                   "FamilySecondEmail" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["BillID"][] = $Record["BillID"];
                 $ArrayRecords["BillDate"][] = $Record["BillDate"];
                 $ArrayRecords["BillForDate"][] = $Record["BillForDate"];
                 $ArrayRecords["BillPreviousBalance"][] = $Record["BillPreviousBalance"];
                 $ArrayRecords["BillDeposit"][] = $Record["BillDeposit"];
                 $ArrayRecords["BillMonthlyContribution"][] = $Record["BillMonthlyContribution"];
                 $ArrayRecords["BillNbCanteenRegistrations"][] = $Record["BillNbCanteenRegistrations"];
                 $ArrayRecords["BillCanteenAmount"][] = $Record["BillCanteenAmount"];
                 $ArrayRecords["BillWithoutMealAmount"][] = $Record["BillWithoutMealAmount"];
                 $ArrayRecords["BillNbNurseryRegistrations"][] = $Record["BillNbNurseryRegistrations"];
                 $ArrayRecords["BillNurseryAmount"][] = $Record["BillNurseryAmount"];
                 $ArrayRecords["BillNurseryNbDelays"][] = $Record["BillNurseryNbDelays"];
                 $ArrayRecords["BillPaidAmount"][] = $Record["BillPaidAmount"];
                 $ArrayRecords["BillPaid"][] = $Record["BillPaid"];

                 $fAmount = $Record['BillMonthlyContribution'] + $Record['BillCanteenAmount'] + $Record['BillWithoutMealAmount']
                            + $Record['BillNurseryAmount'] - $Record['BillDeposit'];

                 $ArrayRecords["BillAmount"][] = $fAmount;
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["FamilyMainEmail"][] = $Record["FamilyMainEmail"];
                 $ArrayRecords["FamilySecondEmail"][] = $Record["FamilySecondEmail"];
             }

             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Compute the amount of a bill, thanks to it's ID
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2013-01-31 : taken into account the new parameter "AmountMode"
 *
 * @since 2012-03-09
 *
 * @param $DbConnection        DB object    Object of the opened database connection
 * @param $BillID              Integer      ID of the bill to get the amount [1..n]
 * @param $AmountMode          Enum         The mode to compute the total amount of the bill
 *
 * @return Float               The total amount of the bill, FALSE otherwise
 */
 function getBillAmount($DbConnection, $BillID, $AmountMode = WITHOUT_PREVIOUS_BALANCE)
 {
     if ($BillID > 0)
     {
         $RecordBill = getTableRecordInfos($DbConnection, 'Bills', $BillID);
         if (!empty($RecordBill))
         {
             $fAmount = $RecordBill['BillMonthlyContribution'] + $RecordBill['BillCanteenAmount']
                        + $RecordBill['BillWithoutMealAmount'] + $RecordBill['BillNurseryAmount']
                        - $RecordBill['BillDeposit'];

             switch($AmountMode)
             {
                 case WITH_PREVIOUS_BALANCE:
                     // Add the previous balance of the bill to the total amount
                     $fAmount += $RecordBill['BillPreviousBalance'];
                     break;
             }

             return $fAmount;
         }
     }

     // ERROR
     return FALSE;
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
 * @return Float                The value added/removed to the bill, FALSE otherwise
 */
 function updateBillPaidAmount($DbConnection, $BillID, $Value)
 {
     $DbResult = $DbConnection->query("SELECT BillPaidAmount FROM Bills WHERE BillID = $BillID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // Get the current paid amount of the bill
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);

             // Compute the total amount of the bill
             if (isFirstBillOfFamily($DbConnection, $BillID))
             {
                 // Get the total amount of the bill with its previous balance amount
                 $fAmount = getBillAmount($DbConnection, $BillID, WITH_PREVIOUS_BALANCE);
             }
             else
             {
                 // Get the total amount of the bill (without its previous balance amount )
                 $fAmount = getBillAmount($DbConnection, $BillID);
             }

             // Compute the new paid amount and check if the paid amount is <= total amount of the bill
             $fBillPaidAmount = round((float)$Record['BillPaidAmount'], 2);
             $fValueToAdd = round((float)$Value, 2);

             if ($fBillPaidAmount + $fValueToAdd > $fAmount)
             {
                 $fValueToAdd = $fAmount - $fBillPaidAmount;
             }
             elseif ($fBillPaidAmount + $fValueToAdd < 0.00)
             {
                 $fValueToAdd = -$fBillPaidAmount;
             }

             $fNewPaidAmount = $fBillPaidAmount + $fValueToAdd;

             // We set the new paid amount
             $DbResult = $DbConnection->query("UPDATE Bills SET BillPaidAmount = $fNewPaidAmount WHERE BillID = $BillID");
             if (!DB::isError($DbResult))
             {
                 return $fValueToAdd;
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Give the older date of the Bills table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-21
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the Bills table,
 *                              empty string otherwise
 */
 function getBillMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(BillForDate) As minDate FROM Bills");
     if (!DB::isError($DbResult))
     {
         $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
         if (!empty($Record["minDate"]))
         {
             return $Record["minDate"];
         }
     }

     // ERROR
     return '';
 }
?>