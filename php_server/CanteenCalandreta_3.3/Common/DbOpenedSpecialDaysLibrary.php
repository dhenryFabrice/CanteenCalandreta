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
 * Common module : library of database functions used for the OpenedSpecialDays table
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2013-10-25
 */


/**
 * Check if an opened special day exists in the OpenedSpecialDays table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection          DB object    Object of the opened database connection
 * @param $OpenedSpecialDayID    Integer      ID of the opened special day searched [1..n]
 *
 * @return Boolean               TRUE if the opened special day exists, FALSE otherwise
 */
 function isExistingOpenedSpecialDay($DbConnection, $OpenedSpecialDayID)
 {
     $DbResult = $DbConnection->query("SELECT OpenedSpecialDayID FROM OpenedSpecialDays WHERE OpenedSpecialDayID = $OpenedSpecialDayID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The pened special day exists
             return TRUE;
         }
     }

     // The pened special day doesn't exist
     return FALSE;
 }


/**
 * Add an opened special day in the OpenedSpecialDays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection                   DB object    Object of the opened database connection
 * @param $OpenedSpecialDayDate           Date         Date of the opened special day (yyyy-mm-dd)
 * @param $OpenedSpecialDayDescription    String       Description of the opened special day
 *
 * @return Integer                        The primary key of the opened special day [1..n], 0 otherwise
 */
 function dbAddOpenedSpecialDay($DbConnection, $OpenedSpecialDayDate, $OpenedSpecialDayDescription = '')
 {
     if (!empty($OpenedSpecialDayDate))
     {
         // Check if the OpenedSpecialDayDate is valide
         $SQLOpenedSpecialDayDate = "";
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $OpenedSpecialDayDate) == 0)
         {
             return 0;
         }
         else
         {
             $SQLOpenedSpecialDayDate = ", OpenedSpecialDayDate = \"$OpenedSpecialDayDate\"";
         }

         // Check if the opened special day is a new opened special day
         $ArrayDays = getOpenedSpecialDays($DbConnection, $OpenedSpecialDayDate, $OpenedSpecialDayDate);
         if ((empty($ArrayDays)) || ((isset($ArrayDays['OpenedSpecialDayID'])) && (empty($ArrayDays['OpenedSpecialDayID']))))
         {
             if (empty($OpenedSpecialDayDescription))
             {
                 $OpenedSpecialDayDescription = ", OpenedSpecialDayDescription = NULL";
             }
             else
             {
                 $OpenedSpecialDayDescription = ", OpenedSpecialDayDescription = \"$OpenedSpecialDayDescription\"";
             }

             // It's a new holiday
             $id = getNewPrimaryKey($DbConnection, "OpenedSpecialDays", "OpenedSpecialDayID");
             if ($id != 0)
             {
                 $DbResult = $DbConnection->query("INSERT INTO OpenedSpecialDays SET OpenedSpecialDayID = $id $OpenedSpecialDayDescription
                                                   $SQLOpenedSpecialDayDate");

                 if (!DB::isError($DbResult))
                 {
                     return $id;
                 }
             }
         }
         else
         {
             // The opened special day already exists
             return $ArrayDays['OpenedSpecialDayID'][0];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing opened special day in the OpenedSpecialDays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection                   DB object    Object of the opened database connection
 * @param $OpenedSpecialDayID             Integer      ID of the opend special day to update [1..n]
 * @param $OpenedSpecialDayDate           Date         Date of the opened special day (yyyy-mm-dd)
 * @param $OpenedSpecialDayDescription    String       Description of the opened special day
 *
 * @return Integer                        The primary key of the opened special day [1..n], 0 otherwise
 */
 function dbUpdateOpenedSpecialDay($DbConnection, $OpenedSpecialDayID, $OpenedSpecialDayDate = NULL, $OpenedSpecialDayDescription = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($OpenedSpecialDayID < 1) || (!isInteger($OpenedSpecialDayID)))
     {
         // ERROR
         return 0;
     }

     $RecordDay = getTableRecordInfos($DbConnection, 'OpenedSpecialDays', $OpenedSpecialDayID);

     // Check if the OpenedSpecialDayDate is valide
     if (!is_null($OpenedSpecialDayDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $OpenedSpecialDayDate) == 0)
         {
             return 0;
         }
         else
         {
             // The OpenedSpecialDayDate field will be updated
             $ArrayParamsUpdate[] = "OpenedSpecialDayDate = \"$OpenedSpecialDayDate\"";
         }
     }
     else
     {
         $OpenedSpecialDayDate = $RecordDay['OpenedSpecialDayDate'];
     }

     if (!is_Null($OpenedSpecialDayDescription))
     {
         // The OpenedSpecialDayDescription field will be updated
         $ArrayParamsUpdate[] = "OpenedSpecialDayDescription = \"$OpenedSpecialDayDescription\"";
     }

     // Here, the parameters are correct, we check if the opened special day exists
     if (isExistingOpenedSpecialDay($DbConnection, $OpenedSpecialDayID))
     {
         // We check if the holiday is unique
         $ArrayDays = getOpenedSpecialDays($DbConnection, $OpenedSpecialDayDate, $OpenedSpecialDayDate);
         if ((empty($ArrayDays)) || (((isset($ArrayDays['OpenedSpecialDayID'])) && (count($ArrayDays['OpenedSpecialDayID']) == 1)
             && ($ArrayDays['OpenedSpecialDayID'][0] == $OpenedSpecialDayID))))
         {
             // The opened special day exists and is unique : we can update if there is at least 1 parameter
             if (count($ArrayParamsUpdate) > 0)
             {
                 $DbResult = $DbConnection->query("UPDATE OpenedSpecialDays SET ".implode(", ", $ArrayParamsUpdate)
                                                  ." WHERE OpenedSpecialDayID = $OpenedSpecialDayID");
                 if (!DB::isError($DbResult))
                 {
                     // Opened special day updated
                     return $OpenedSpecialDayID;
                 }
             }
             else
             {
                 // The update isn't usefull
                 return $OpenedSpecialDayID;
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Delete an opened special day, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $OpenedSpecialDayID        Integer      ID of the opened special day to delete [1..n]
 *
 * @return Boolean                   TRUE if the opened special day is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteOpenedSpecialDay($DbConnection, $OpenedSpecialDayID)
 {
     // The parameters are correct?
     if ($OpenedSpecialDayID > 0)
     {
         // Delete the opened special day in the table
         $DbResult = $DbConnection->query("DELETE FROM OpenedSpecialDays WHERE OpenedSpecialDayID = $OpenedSpecialDayID");
         if (!DB::isError($DbResult))
         {
             // Opened special day deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the opened special days between 2 dates.
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-10-26 : taken into account the Description criteria
 *
 * @since 2013-10-25
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order opened special days
 * @param $Description          String               Description to filter opened special days
 *
 * @return mixed Array                               The opened special days between the 2 dates,
 *                                                   an empty array otherwise
 */
 function getOpenedSpecialDays($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'OpenedSpecialDayDate', $Description = '')
 {
     if (is_Null($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $sCondition = '';
     if (!empty($Description))
     {
         $sCondition = " AND $sCondition like \"$Description\"";
     }

     $DbResult = $DbConnection->query("SELECT o.OpenedSpecialDayID, o.OpenedSpecialDayDate, o.OpenedSpecialDayDescription
                                       FROM OpenedSpecialDays o WHERE o.OpenedSpecialDayDate BETWEEN \"$StartDate\" AND \"$EndDate\"
                                       $sCondition GROUP BY o.OpenedSpecialDayDate ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "OpenedSpecialDayID" => array(),
                                   "OpenedSpecialDayDate" => array(),
                                   "OpenedSpecialDayDescription" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["OpenedSpecialDayID"][] = $Record["OpenedSpecialDayID"];
                 $ArrayRecords["OpenedSpecialDayDate"][] = $Record["OpenedSpecialDayDate"];
                 $ArrayRecords["OpenedSpecialDayDescription"][] = $Record["OpenedSpecialDayDescription"];
             }

             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Give the older date of the OpenedSpeciaDays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-10-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the OpenedSpeciaDays table,
 *                              empty string otherwise
 */
 function getOpenedSpecialDayMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(OpenedSpecialDayDate) As minDate FROM OpenedSpecialDays");
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
 * Give the earlier date of the OpenedSpeciaDays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-10-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the OpenedSpeciaDays table,
 *                              empty string otherwise
 */
 function getOpenedSpeciaDayMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(OpenedSpecialDayDate) As maxDate FROM OpenedSpecialDays");
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