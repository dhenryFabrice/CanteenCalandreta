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
 * Common module : library of database functions used for the LaundryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 2.9
 * @since 2015-06-19
 */


/**
 * Check if a laundry registration exists in the LaundryRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-19
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $LaundryRegistrationID    Integer      ID of the laundry registration searched [1..n]
 *
 * @return Boolean                  TRUE if the laundry registration exists, FALSE otherwise
 */
 function isExistingLaundryRegistration($DbConnection, $LaundryRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT LaundryRegistrationID FROM LaundryRegistrations
                                       WHERE LaundryRegistrationID = $LaundryRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The laundry registration exists
             return TRUE;
         }
     }

     // The laundry registration doesn't exist
     return FALSE;
 }


/**
 * Add a laundry registration for the friday of a week and for a family in the LaundryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-19
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $LaundryRegistrationDate           Date         Date for which the laundry registration is (yyyy-mm-dd)
 * @param $FamilyID                          Integer      ID of the family concerned by the laundry registration [1..n]
 *
 * @return Integer                           The primary key of the laundry registration [1..n], 0 otherwise
 */
 function dbAddLaundryRegistration($DbConnection, $LaundryRegistrationDate, $FamilyID)
 {
     if ($FamilyID > 0)
     {
         // Check if the laundry registration is a new laundry registration for a family and a day
         $DbResult = $DbConnection->query("SELECT LaundryRegistrationID FROM LaundryRegistrations
                                          WHERE LaundryRegistrationDate = \"$LaundryRegistrationDate\" AND FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the LaundryRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $LaundryRegistrationDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $LaundryRegistrationDate = ", LaundryRegistrationDate = \"$LaundryRegistrationDate\"";
                 }

                 // It's a new laundry registration
                 $id = getNewPrimaryKey($DbConnection, "LaundryRegistrations", "LaundryRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO LaundryRegistrations SET LaundryRegistrationID = $id,
                                                      FamilyID = $FamilyID $LaundryRegistrationDate");
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
 * Update an existing laundry registration for the friday of a week and for a family in the LaundryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-19
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $LaundryRegistrationID             Integer      ID of the laundry registration to update [1..n]
 * @param $LaundryRegistrationDate           Date         Date for which the laundry registration is (yyyy-mm-dd)
 * @param $FamilyID                          Integer      ID of the family concerned by the laundry registration [1..n]
 *
 * @return Integer                           The primary key of the laundry registration [1..n], 0 otherwise
 */
 function dbUpdateLaundryRegistration($DbConnection, $LaundryRegistrationID, $LaundryRegistrationDate, $FamilyID)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($LaundryRegistrationID < 1) || (!isInteger($LaundryRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the LaundryRegistrationDate is valide
     if (!is_null($LaundryRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $LaundryRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The LaundryRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "LaundryRegistrationDate = \"$LaundryRegistrationDate\"";
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

     // Here, the parameters are correct, we check if the laundry registration exists
     if (isExistingLaundryRegistration($DbConnection, $LaundryRegistrationID))
     {
         // We check if the laundry registration is unique for a family and a day
         $DbResult = $DbConnection->query("SELECT LaundryRegistrationID FROM LaundryRegistrations
                                          WHERE LaundryRegistrationDate = \"$LaundryRegistrationDate\" AND FamilyID = $FamilyID
                                          AND LaundryRegistrationID <> $LaundryRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The laundry registration exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE LaundryRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE LaundryRegistrationID = $LaundryRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Laundry registration updated
                         return $LaundryRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $LaundryRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the laundry registrations between 2 dates for a given family or all.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-19
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order laundry registrations
 * @param $FamilyID             Integer              ID of the family for which we want laundry registrations [1..n]
 * @param $Mode                 Enum                 Mode to find laundry registrations
 * @param $ArrayParams          Mixed Array          Other criterion to filter laundry registrations
 *
 * @return mixed Array          The laundry registrations and between the 2 dates for a given family or all,
 *                              an empty array otherwise
 */
 function getLaundryRegistrations($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'LaundryRegistrationDate', $FamilyID = NULL, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
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

     $Select = "SELECT sl.LaundryRegistrationID, sl.LaundryRegistrationDate, f.FamilyID, f.FamilyLastname";

     $From = "FROM LaundryRegistrations sl, Families f";

     $Where = "WHERE sl.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"".$StartDate."\" >= DATE_FORMAT(sl.LaundryRegistrationDate,'%Y-%m-%d') AND \""
                       .$EndDate."\" <= DATE_FORMAT(sl.LaundryRegistrationDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"".$StartDate."\" >= DATE_FORMAT(sl.LaundryRegistrationDate,'%Y-%m-%d')) OR (\""
                       .$EndDate."\" <= DATE_FORMAT(sl.LaundryRegistrationDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND (DATE_FORMAT(sl.LaundryRegistrationDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY sl.LaundryRegistrationID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "LaundryRegistrationID" => array(),
                                   "LaundryRegistrationDate" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["LaundryRegistrationID"][] = $Record["LaundryRegistrationID"];
                 $ArrayRecords["LaundryRegistrationDate"][] = $Record["LaundryRegistrationDate"];
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
 * Delete laundry registrations between 2 dates.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-08-29
 *
 * @param $DbConnection             DB object            Object of the opened database connection
 * @param $LaundryRegistrationID    Integer              ID of the laundry registration to delete. Can be NULL or [1..n]
 * @param $StartDate                Date                 Start date of the period to delete (yyyy-mm-dd)
 * @param $EndDate                  Date                 End date of the period to delete (yyyy-mm-dd)
 *
 * @return Boolean                  TRUE if the laundry registration is deleted, FALSE otherwise
 */
 function dbDeleteLaundryRegistration($DbConnection, $LaundryRegistrationID, $StartDate = NULL, $EndDate = NULL)
 {
     $Condition = "";
     if ($LaundryRegistrationID > 0)
     {
         $Condition = " WHERE LaundryRegistrationID = $LaundryRegistrationID";
     }
     else
     {
         if (!empty($StartDate))
         {
             if (!empty($EndDate))
             {
                 $Condition = " WHERE LaundryRegistrationDate BETWEEN \"$StartDate\" AND \"$EndDate\"";
             }
             else
             {
                 $Condition = " WHERE LaundryRegistrationDate >= \"$StartDate\"";
             }
         }
     }

     if (!empty($Condition))
     {
         $DbResult = $DbConnection->query("DELETE FROM LaundryRegistrations $Condition");
         if (!DB::isError($DbResult))
         {
             // Laundry registration deleted
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
 * Give the older date of the LaundryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-19
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the LaundryRegistrations table,
 *                              empty string otherwise
 */
 function getLaundryRegistrationMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(LaundryRegistrationDate) As minDate FROM LaundryRegistrations");
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
 * Give the earlier date of the LaundryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-19
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the LaundryRegistrations table,
 *                              empty string otherwise
 */
 function getLaundryRegistrationMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(LaundryRegistrationDate) As maxDate FROM LaundryRegistrations");
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