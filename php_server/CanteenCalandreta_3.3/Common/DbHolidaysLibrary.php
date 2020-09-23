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
 * Common module : library of database functions used for the Holidays table
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2012-03-28
 */


/**
 * Check if a holiday exists in the Holidys table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $HolidayID            Integer      ID of the holiday searched [1..n]
 *
 * @return Boolean              TRUE if the holiday exists, FALSE otherwise
 */
 function isExistingHoliday($DbConnection, $HolidayID)
 {
     $DbResult = $DbConnection->query("SELECT HolidayID FROM Holidays WHERE HolidayID = $HolidayID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The holiday exists
             return TRUE;
         }
     }

     // The holiday doesn't exist
     return FALSE;
 }


/**
 * Add a holiday in the Holidays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $HolidayStartDate         Date         Start date of the holiday (yyyy-mm-dd)
 * @param $HolidayEndDate           Date         End date of the holiday (yyyy-mm-dd)
 * @param $HolidayDescription       String       Description of the holiday
 *
 * @return Integer                  The primary key of the holiday [1..n], 0 otherwise
 */
 function dbAddHoliday($DbConnection, $HolidayStartDate, $HolidayEndDate, $HolidayDescription = '')
 {
     if ((!empty($HolidayStartDate)) && (!empty($HolidayEndDate)))
     {
         // Check if the HolidayStartDate is valide
         $SQLHolidayStartDate = "";
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $HolidayStartDate) == 0)
         {
             return 0;
         }
         else
         {
             $SQLHolidayStartDate = ", HolidayStartDate = \"$HolidayStartDate\"";
         }

         // Check if the HolidayEndDate is valide
         $SQLHolidayEndDate = "";
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $HolidayEndDate) == 0)
         {
             return 0;
         }
         else
         {
             $SQLHolidayEndDate = ", HolidayEndDate = \"$HolidayEndDate\"";
         }

         // Check if the holiday is a new holiday
         $ArrayHolidays = getHolidays($DbConnection, $HolidayStartDate, $HolidayEndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
         if ((empty($ArrayHolidays)) || ((isset($ArrayHolidays['HolidayID'])) && (empty($ArrayHolidays['HolidayID']))))
         {
             if (empty($HolidayDescription))
             {
                 $HolidayDescription = ", HolidayDescription = NULL";
             }
             else
             {
                 $HolidayDescription = ", HolidayDescription = \"$HolidayDescription\"";
             }

             // It's a new holiday
             $id = getNewPrimaryKey($DbConnection, "Holidays", "HolidayID");
             if ($id != 0)
             {
                 $DbResult = $DbConnection->query("INSERT INTO Holidays SET HolidayID = $id $HolidayDescription $SQLHolidayStartDate
                                                   $SQLHolidayEndDate");

                 if (!DB::isError($DbResult))
                 {
                     return $id;
                 }
             }
         }
         else
         {
             // The holiday already exists
             return $ArrayHolidays['HolidayID'][0];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing holiday in the Holidays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $HolidayID                Integer      ID of the holiday to update [1..n]
 * @param $HolidayStartDate         Date         Start date of the holiday (yyyy-mm-dd)
 * @param $HolidayEndDate           Date         End date of the holiday (yyyy-mm-dd)
 * @param $HolidayDescription       String       Description of the holiday
 *
 * @return Integer                  The primary key of the holiday [1..n], 0 otherwise
 */
 function dbUpdateHoliday($DbConnection, $HolidayID, $HolidayStartDate = NULL, $HolidayEndDate = NULL, $HolidayDescription = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($HolidayID < 1) || (!isInteger($HolidayID)))
     {
         // ERROR
         return 0;
     }

     $RecordHoliday = getTableRecordInfos($DbConnection, 'Holidays', $HolidayID);

     // Check if the HolidayStartDate is valide
     if (!is_null($HolidayStartDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $HolidayStartDate) == 0)
         {
             return 0;
         }
         else
         {
             // The HolidayStartDate field will be updated
             $ArrayParamsUpdate[] = "HolidayStartDate = \"$HolidayStartDate\"";
         }
     }
     else
     {
         $HolidayStartDate = $RecordHoliday['HolidayStartDate'];
     }

     // Check if the HolidayEndDate is valide
     if (!is_null($HolidayEndDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $HolidayEndDate) == 0)
         {
             return 0;
         }
         else
         {
             // The HolidayEndDate field will be updated
             $ArrayParamsUpdate[] = "HolidayEndDate = \"$HolidayEndDate\"";
         }
     }
     else
     {
         $HolidayEndDate = $RecordHoliday['HolidayEndDate'];
     }

     if (!is_Null($HolidayDescription))
     {
         // The HolidayDescription field will be updated
         $ArrayParamsUpdate[] = "HolidayDescription = \"$HolidayDescription\"";
     }

     // Here, the parameters are correct, we check if the holiday exists
     if (isExistingHoliday($DbConnection, $HolidayID))
     {
         // We check if the holiday is unique
         $ArrayHolidays = getHolidays($DbConnection, $HolidayStartDate, $HolidayEndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
         if ((isset($ArrayHolidays['HolidayID'])) && (count($ArrayHolidays['HolidayID']) == 1)
             && ($ArrayHolidays['HolidayID'][0] == $HolidayID))
         {
             // The holiday exists and is unique : we can update if there is at least 1 parameter
             if (count($ArrayParamsUpdate) > 0)
             {
                 $DbResult = $DbConnection->query("UPDATE Holidays SET ".implode(", ", $ArrayParamsUpdate)
                                                  ." WHERE HolidayID = $HolidayID");
                 if (!DB::isError($DbResult))
                 {
                     // Holiday updated
                     return $HolidayID;
                 }
             }
             else
             {
                 // The update isn't usefull
                 return $HolidayID;
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Delete a holiday, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $HolidayID                 Integer      ID of the holiday to delete [1..n]
 *
 * @return Boolean                   TRUE if the holiday is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteHoliday($DbConnection, $HolidayID)
 {
     // The parameters are correct?
     if ($HolidayID > 0)
     {
         // Delete the holiday in the table
         $DbResult = $DbConnection->query("DELETE FROM Holidays WHERE HolidayID = $HolidayID");
         if (!DB::isError($DbResult))
         {
             // Holiday deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the holidays between 2 dates.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-28
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order holidays
 * @param $Mode                 Enum                 Mode to find holidays
 *
 * @return mixed Array                               The holidays between the 2 dates,
 *                                                   an empty array otherwise
 */
 function getHolidays($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'HolidayStartDate', $Mode = PLANNING_BETWEEN_DATES)
 {
     if (is_Null($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Select = "SELECT h.HolidayID, h.HolidayStartDate, h.HolidayEndDate, h.HolidayDescription";
     $From = "FROM Holidays h";
     $Where = "";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where = "WHERE \"$StartDate\" >= DATE_FORMAT(h.HolidayStartDate, '%Y-%m-%d')
                       AND \"$EndDate\" <= DATE_FORMAT(h.HolidayEndDate, '%Y-%m-%d')";
             break;

         case PLANNING_INCLUDED_IN_DATES:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where = "WHERE DATE_FORMAT(h.HolidayStartDate, '%Y-%m-%d') >= \"$StartDate\"
                       AND DATE_FORMAT(h.HolidayEndDate, '%Y-%m-%d') <= \"$EndDate\"";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where = "WHERE ((\"$StartDate\" BETWEEN DATE_FORMAT(h.HolidayStartDate, '%Y-%m-%d')
                       AND DATE_FORMAT(h.HolidayEndDate, '%Y-%m-%d')) OR (\"$EndDate\" BETWEEN DATE_FORMAT(h.HolidayStartDate,'%Y-%m-%d')
                       AND DATE_FORMAT(h.HolidayEndDate, '%Y-%m-%d')))";
             break;

         case NO_DATES:
             // No Where
             break;

         default:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where = "WHERE ((DATE_FORMAT(h.HolidayStartDate, '%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")
                       OR (DATE_FORMAT(h.HolidayEndDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\"))";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY h.HolidayID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "HolidayID" => array(),
                                   "HolidayStartDate" => array(),
                                   "HolidayEndDate" => array(),
                                   "HolidayDescription" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["HolidayID"][] = $Record["HolidayID"];
                 $ArrayRecords["HolidayStartDate"][] = $Record["HolidayStartDate"];
                 $ArrayRecords["HolidayEndDate"][] = $Record["HolidayEndDate"];
                 $ArrayRecords["HolidayDescription"][] = $Record["HolidayDescription"];
             }

             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Give the older date of the Holidays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-28
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the Holidays table,
 *                              empty string otherwise
 */
 function getHolidayMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(HolidayStartDate) As minDate FROM Holidays");
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
 * Give the earlier date of the Holidays table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-28
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the Holidays table,
 *                              empty string otherwise
 */
 function getHolidayMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(HolidayEndDate) As maxDate FROM Holidays");
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


/**
 * Get holidays filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the holidays
 * @param $OrderBy                  String                 Criteria used to sort the holidays. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of holidays per page to return [1..n]
 *
 * @return Array of String                                 List of holidays filtered, an empty array otherwise
 */
 function dbSearchHoliday($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find holidays
     $Select = "SELECT h.HolidayID, h.HolidayStartDate, h.HolidayEndDate, h.HolidayDescription";
     $From = "FROM Holidays h";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< HolidayID field >>>
         if ((array_key_exists("HolidayID", $ArrayParams)) && (!empty($ArrayParams["HolidayID"])))
         {
             if (is_array($ArrayParams["HolidayID"]))
             {
                 $Where .= " AND h.HolidayID IN ".constructSQLINString($ArrayParams["HolidayID"]);
             }
             else
             {
                 $Where .= " AND h.HolidayID = ".$ArrayParams["HolidayID"];
             }
         }

         // <<< HolidayDescription field >>>
         if ((array_key_exists("HolidayDescription", $ArrayParams)) && (!empty($ArrayParams["HolidayDescription"])))
         {
             $Where .= " AND h.HolidayDescription LIKE \"".$ArrayParams["HolidayDescription"]."\"";
         }

         if ((array_key_exists("HolidayStartDate", $ArrayParams)) && (count($ArrayParams["HolidayStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND h.HolidayStartDate ".$ArrayParams["HolidayStartDate"][1]." \"".$ArrayParams["HolidayStartDate"][0]."\"";
         }

         if ((array_key_exists("HolidayEndDate", $ArrayParams)) && (count($ArrayParams["HolidayEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND h.HolidayEndDate ".$ArrayParams["HolidayEndDate"][1]." \"".$ArrayParams["HolidayEndDate"][0]."\"";
         }
     }

     // We take into account the page and the number of holidays per page
     if ($Page < 1)
     {
         $Page = 1;
     }

     if ($RecordsPerPage < 0)
     {
         $RecordsPerPage = 10;
     }

     $Limit = '';
     if ($RecordsPerPage > 0)
     {
         $StartIndex = ($Page - 1) * $RecordsPerPage;
         $Limit = "LIMIT $StartIndex, $RecordsPerPage";
     }

     // We take into account the order by
     if ($OrderBy == "")
     {
         $StrOrderBy = "";
     }
     else
     {
         $StrOrderBy = " ORDER BY $OrderBy";
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY h.HolidayID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "HolidayID" => array(),
                                   "HolidayStartDate" => array(),
                                   "HolidayEndDate" => array(),
                                   "HolidayDescription" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["HolidayID"][] = $Record["HolidayID"];
                 $ArrayRecords["HolidayStartDate"][] = $Record["HolidayStartDate"];
                 $ArrayRecords["HolidayEndDate"][] = $Record["HolidayEndDate"];
                 $ArrayRecords["HolidayDescription"][] = $Record["HolidayDescription"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of holidays filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the holidays
 *
 * @return Integer              Number of the holidays found, 0 otherwise
 */
 function getNbdbSearchHoliday($DbConnection, $ArrayParams)
 {
     // SQL request to find events
     $Select = "SELECT h.HolidayID";
     $From = "FROM Holidays h";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< HolidayID field >>>
         if ((array_key_exists("HolidayID", $ArrayParams)) && (!empty($ArrayParams["HolidayID"])))
         {
             if (is_array($ArrayParams["HolidayID"]))
             {
                 $Where .= " AND h.HolidayID IN ".constructSQLINString($ArrayParams["HolidayID"]);
             }
             else
             {
                 $Where .= " AND h.HolidayID = ".$ArrayParams["HolidayID"];
             }
         }

         // <<< HolidayDescription field >>>
         if ((array_key_exists("HolidayDescription", $ArrayParams)) && (!empty($ArrayParams["HolidayDescription"])))
         {
             $Where .= " AND h.HolidayDescription LIKE \"".$ArrayParams["HolidayDescription"]."\"";
         }

         if ((array_key_exists("HolidayStartDate", $ArrayParams)) && (count($ArrayParams["HolidayStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND h.HolidayStartDate ".$ArrayParams["HolidayStartDate"][1]." \"".$ArrayParams["HolidayStartDate"][0]."\"";
         }

         if ((array_key_exists("HolidayEndDate", $ArrayParams)) && (count($ArrayParams["HolidayEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND h.HolidayEndDate ".$ArrayParams["HolidayEndDate"][1]." \"".$ArrayParams["HolidayEndDate"][0]."\"";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY HolidayID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }
?>