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
 * Common module : library of database functions used for the SnackRegistrations table
 *
 * @author Christophe Javouhey
 * @version 2.9
 * @since 2015-06-15
 */


/**
 * Check if a snack registration exists in the SnackRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-15
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $SnackRegistrationID      Integer      ID of the snack registration searched [1..n]
 *
 * @return Boolean                  TRUE if the snack registration exists, FALSE otherwise
 */
 function isExistingSnackRegistration($DbConnection, $SnackRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT SnackRegistrationID FROM SnackRegistrations
                                       WHERE SnackRegistrationID = $SnackRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The snack registration exists
             return TRUE;
         }
     }

     // The snack registration doesn't exist
     return FALSE;
 }


/**
 * Add a snack registration for the monday of a week and for a family and a class in the SnackRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-15
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $SnackRegistrationDate             Date         Date for which the snack registration is (yyyy-mm-dd)
 * @param $FamilyID                          Integer      ID of the family concerned by the snack registration [1..n]
 * @param $SnackRegistrationClass            Integer      Class concerned by the snack registration [0..n]
 *
 * @return Integer                           The primary key of the snack registration [1..n], 0 otherwise
 */
 function dbAddSnackRegistration($DbConnection, $SnackRegistrationDate, $FamilyID, $SnackRegistrationClass = 0)
 {
     if (($FamilyID > 0) && ($SnackRegistrationClass >= 0))
     {
         // Check if the snack registration is a new snack registration for date and a family
         $DbResult = $DbConnection->query("SELECT SnackRegistrationID FROM SnackRegistrations
                                          WHERE SnackRegistrationDate = \"$SnackRegistrationDate\" AND FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the SnackRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $SnackRegistrationDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $SnackRegistrationDate = ", SnackRegistrationDate = \"$SnackRegistrationDate\"";
                 }

                 // It's a new snack registration
                 $id = getNewPrimaryKey($DbConnection, "SnackRegistrations", "SnackRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO SnackRegistrations SET SnackRegistrationID = $id, FamilyID = $FamilyID,
                                                      SnackRegistrationClass = $SnackRegistrationClass $SnackRegistrationDate");
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
 * Update an existing snack registration for the monday of a week and for a family and a class in the SnackRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-15
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $SnackRegistrationID               Integer      ID of the snack registration to update [1..n]
 * @param $SnackRegistrationDate             Date         Date for which the snack registration is (yyyy-mm-dd)
 * @param $FamilyID                          Integer      ID of the family concerned by the snack registration [1..n]
 * @param $SnackRegistrationClass            Integer      Class concerned by the snack registration [0..n]
 *
 * @return Integer                           The primary key of the snack registration [1..n], 0 otherwise
 */
 function dbUpdateSnackRegistration($DbConnection, $SnackRegistrationID, $SnackRegistrationDate, $FamilyID, $SnackRegistrationClass = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($SnackRegistrationID < 1) || (!isInteger($SnackRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the SnackRegistrationDate is valide
     if (!is_null($SnackRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $SnackRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The SnackRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "SnackRegistrationDate = \"$SnackRegistrationDate\"";
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

     if (!is_Null($SnackRegistrationClass))
     {
         if (($SnackRegistrationClass < 0) || (!isInteger($SnackRegistrationClass)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The SnackRegistrationClass field will be updated
             $ArrayParamsUpdate[] = "SnackRegistrationClass = $SnackRegistrationClass";
         }
     }

     // Here, the parameters are correct, we check if the snack registration exists
     if (isExistingSnackRegistration($DbConnection, $SnackRegistrationID))
     {
         // We check if the snack registration is unique for a family and a day
         $DbResult = $DbConnection->query("SELECT SnackRegistrationID FROM SnackRegistrations
                                          WHERE SnackRegistrationDate = \"$SnackRegistrationDate\" AND FamilyID = $FamilyID
                                          AND SnackRegistrationID <> $SnackRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The snack registration exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE SnackRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE SnackRegistrationID = $SnackRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Snack registration updated
                         return $SnackRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $SnackRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the snack registrations between 2 dates for a given family or all.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-15
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order snack registrations
 * @param $FamilyID             Integer              ID of the family for which we want snack registrations [1..n]
 * @param $Mode                 Enum                 Mode to find snack registrations
 * @param $ArrayParams          Mixed Array          Other criterion to filter snack registrations
 *
 * @return mixed Array          The snack registrations and between the 2 dates for a given family or all,
 *                              an empty array otherwise
 */
 function getSnackRegistrations($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'SnackRegistrationDate', $FamilyID = NULL, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
 {
     if (empty($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Conditions = "";
     if (!empty($FamilyID))
     {
         if (is_array($FamilyID))
         {
              $Conditions .= " AND f.FamilyID IN ".constructSQLINString($FamilyID);
         }
         elseif (($FamilyID >= 1) && (isInteger($FamilyID)))
         {
             $Conditions .= " AND f.FamilyID = $FamilyID";
         }
     }

     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['SnackRegistrationClass'])) && (count($ArrayParams['SnackRegistrationClass']) > 0))
         {
             $Conditions .= " AND sr.SnackRegistrationClass IN ".constructSQLINString($ArrayParams['SnackRegistrationClass']);
         }
     }

     $Select = "SELECT sr.SnackRegistrationID, sr.SnackRegistrationDate, sr.SnackRegistrationClass, f.FamilyID, f.FamilyLastname";

     $From = "FROM SnackRegistrations sr, Families f";

     $Where = "WHERE sr.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"".$StartDate."\" >= DATE_FORMAT(sr.SnackRegistrationDate,'%Y-%m-%d') AND \""
                       .$EndDate."\" <= DATE_FORMAT(sr.SnackRegistrationDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"".$StartDate."\" >= DATE_FORMAT(sr.SnackRegistrationDate,'%Y-%m-%d')) OR (\""
                       .$EndDate."\" <= DATE_FORMAT(sr.SnackRegistrationDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND (DATE_FORMAT(sr.SnackRegistrationDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY sr.SnackRegistrationID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "SnackRegistrationID" => array(),
                                   "SnackRegistrationDate" => array(),
                                   "SnackRegistrationClass" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["SnackRegistrationID"][] = $Record["SnackRegistrationID"];
                 $ArrayRecords["SnackRegistrationDate"][] = $Record["SnackRegistrationDate"];
                 $ArrayRecords["SnackRegistrationClass"][] = $Record["SnackRegistrationClass"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
             }

             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Delete snack registrations between 2 dates.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-08-29
 *
 * @param $DbConnection             DB object            Object of the opened database connection
 * @param $SnackRegistrationID      Integer              ID of the snack registration to delete. Can be NULL or [1..n]
 * @param $StartDate                Date                 Start date of the period to delete (yyyy-mm-dd)
 * @param $EndDate                  Date                 End date of the period to delete (yyyy-mm-dd)
 *
 * @return Boolean                  TRUE if the snack registration is deleted, FALSE otherwise
 */
 function dbDeleteSnackRegistration($DbConnection, $SnackRegistrationID, $StartDate = NULL, $EndDate = NULL)
 {
     $Condition = "";
     if ($SnackRegistrationID > 0)
     {
         $Condition = " WHERE SnackRegistrationID = $SnackRegistrationID";
     }
     else
     {
         if (!empty($StartDate))
         {
             if (!empty($EndDate))
             {
                 $Condition = " WHERE SnackRegistrationDate BETWEEN \"$StartDate\" AND \"$EndDate\"";
             }
             else
             {
                 $Condition = " WHERE SnackRegistrationDate >= \"$StartDate\"";
             }
         }
     }

     if (!empty($Condition))
     {
         $DbResult = $DbConnection->query("DELETE FROM SnackRegistrations $Condition");
         if (!DB::isError($DbResult))
         {
             // Snack registration deleted
             return TRUE;
         }
     }
     else
     {
         // Error
         return FALSE;
     }
 }


/**
 * Give the older date of the SnackRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-15
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the SnackRegistrations table,
 *                              empty string otherwise
 */
 function getSnackRegistrationMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(SnackRegistrationDate) As minDate FROM SnackRegistrations");
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


/**
 * Give the earlier date of the SnackRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-16
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the SnackRegistrations table,
 *                              empty string otherwise
 */
 function getSnackRegistrationMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(SnackRegistrationDate) As maxDate FROM SnackRegistrations");
     if (!DB::isError($DbResult))
     {
         $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
         if (!empty($Record["maxDate"]))
         {
             return $Record["maxDate"];
         }
     }

     // ERROR
     return '';
 }
?>