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
 * Common module : library of database functions used for the ExitPermissions table
 *
 * @author Christophe Javouhey       
 * @version 2.7
 * @since 2015-07-09
 */


/**
 * Check if an exit permission exists in the ExitPermissions table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $ExitPermissionID         Integer      ID of the exit permission searched [1..n]
 *
 * @return Boolean                  TRUE if the exit permission exists, FALSE otherwise
 */
 function isExistingExitPermission($DbConnection, $ExitPermissionID)
 {
     $DbResult = $DbConnection->query("SELECT ExitPermissionID FROM ExitPermissions WHERE ExitPermissionID = $ExitPermissionID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The exit permission exists
             return TRUE;
         }
     }

     // The exit permission doesn't exist
     return FALSE;
 }


/**
 * Add an exit permission for a date and for a child in the ExitPermissions table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $ExitPermissionDate                Date         Date for which the exit permission is (yyyy-mm-dd)
 * @param $ChildID                           Integer      ID of the child concerned by the exit permission [1..n]
 * @param $ExitPermissionName                String       Name of the authorized person to get the child
 * @param $ExitPermissionAuthorizedPerson    Integer      If the name is an authorized person or not [0..1]
 *
 * @return Integer                           The primary key of the exit permission [1..n], 0 otherwise
 */
 function dbAddExitPermission($DbConnection, $ExitPermissionDate, $ChildID, $ExitPermissionName, $ExitPermissionAuthorizedPerson = 1)
 {
     if (($ChildID > 0) && (!empty($ExitPermissionName)) && ($ExitPermissionAuthorizedPerson >= 0))
     {
         // Check if the exit permission is a new exit permission for date and a child
         $DbResult = $DbConnection->query("SELECT ExitPermissionID FROM ExitPermissions
                                           WHERE ExitPermissionDate = \"$ExitPermissionDate\" AND ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the ExitPermissionDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $ExitPermissionDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $ExitPermissionDate = ", ExitPermissionDate = \"$ExitPermissionDate\"";
                 }

                 // It's a new exit permission
                 $id = getNewPrimaryKey($DbConnection, "ExitPermissions", "ExitPermissionID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO ExitPermissions SET ExitPermissionID = $id, ChildID = $ChildID,
                                                       ExitPermissionName = \"$ExitPermissionName\",
                                                       ExitPermissionAuthorizedPerson = $ExitPermissionAuthorizedPerson
                                                       $ExitPermissionDate");
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
 * Update an existing exit permission for a date and for a child in the ExitPermissions table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $ExitPermissionID                  Integer      ID of the exit permission to update [1..n]
 * @param $ExitPermissionDate                Date         Date for which the exit permission is (yyyy-mm-dd)
 * @param $ChildID                           Integer      ID of the child concerned by the exit permission [1..n]
 * @param $ExitPermissionName                String       Name of the authorized person to get the child
 * @param $ExitPermissionAuthorizedPerson    Integer      If the name is an authorized person or not [0..1]
 *
 * @return Integer                           The primary key of the exit permissoin [1..n], 0 otherwise
 */
 function dbUpdateExitPermission($DbConnection, $ExitPermissionID, $ExitPermissionDate, $ChildID, $ExitPermissionName, $ExitPermissionAuthorizedPerson = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($ExitPermissionID < 1) || (!isInteger($ExitPermissionID)))
     {
         // ERROR
         return 0;
     }

     // Check if the ExitPermissionDate is valide
     if (!is_null($ExitPermissionDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $ExitPermissionDate) == 0)
         {
             return 0;
         }
         else
         {
             // The ExitPermissionDate field will be updated
             $ArrayParamsUpdate[] = "ExitPermissionDate = \"$ExitPermissionDate\"";
         }
     }

     if (!is_null($ChildID))
     {
         if (($ChildID < 1) || (!isInteger($ChildID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "ChildID = $ChildID";
         }
     }

     if (!is_null($ExitPermissionName))
     {
         if (empty($ExitPermissionName))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ExitPermissionName field will be updated
             $ArrayParamsUpdate[] = "ExitPermissionName = \"$ExitPermissionName\"";
         }
     }

     if (!is_Null($ExitPermissionAuthorizedPerson))
     {
         if (($ExitPermissionAuthorizedPerson < 0) || (!isInteger($ExitPermissionAuthorizedPerson)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ExitPermissionAuthorizedPerson field will be updated
             $ArrayParamsUpdate[] = "ExitPermissionAuthorizedPerson = $ExitPermissionAuthorizedPerson";
         }
     }

     // Here, the parameters are correct, we check if the exit permission exists
     if (isExistingExitPermission($DbConnection, $ExitPermissionID))
     {
         // We check if the exit permission is unique for a child and a day
         $DbResult = $DbConnection->query("SELECT ExitPermissionID FROM ExitPermissions
                                           WHERE ExitPermissionDate = \"$ExitPermissionDate\" AND ChildID = $ChildID
                                           AND ExitPermissionID <> $ExitPermissionID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The exit permission exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE ExitPermissions SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE ExitPermissionID = $ExitPermissionID");
                     if (!DB::isError($DbResult))
                     {
                         // Exit permission updated
                         return $ExitPermissionID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $ExitPermissionID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the exit permission between 2 dates for a given child or all.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order exit permissions
 * @param $FamilyID             Integer              ID of the family for which we want exit permissons of children [1..n]
 * @param $Mode                 Enum                 Mode to find snack registrations
 * @param $ArrayParams          Mixed Array          Other criterion to filter snack registrations
 *
 * @return mixed Array          The exit permissions between the 2 dates for a given family or all,
 *                              an empty array otherwise
 */
 function getExitPermissions($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'ExitPermissionDate', $FamilyID = NULL, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
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
         if ((isset($ArrayParams['ExitPermissionAuthorizedPerson'])) && (count($ArrayParams['ExitPermissionAuthorizedPerson']) > 0))
         {
             $Conditions .= " AND ep.ExitPermissionAuthorizedPerson IN ".constructSQLINString($ArrayParams['ExitPermissionAuthorizedPerson']);
         }
     }

     $Select = "SELECT ep.ExitPermissionID, ep.ExitPermissionDate, ep.ExitPermissionName, ep.ExitPermissionAuthorizedPerson,
                f.FamilyID, f.FamilyLastname, c.ChildID, c.ChildFirstname";

     $From = "FROM ExitPermissions ep, Families f, Children c";

     $Where = "WHERE ep.ChildID = c.ChildID AND c.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"".$StartDate."\" >= DATE_FORMAT(ep.ExitPermissionDate,'%Y-%m-%d') AND \""
                       .$EndDate."\" <= DATE_FORMAT(ep.ExitPermissionDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"".$StartDate."\" >= DATE_FORMAT(ep.ExitPermissionDate,'%Y-%m-%d')) OR (\""
                       .$EndDate."\" <= DATE_FORMAT(ep.ExitPermissionDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND (DATE_FORMAT(ep.ExitPermissionDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY ep.ExitPermissionID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         $ArrayRecords = array(
                               "ExitPermissionID" => array(),
                               "ExitPermissionDate" => array(),
                               "ExitPermissionName" => array(),
                               "ExitPermissionAuthorizedPerson" => array(),
                               "FamilyID" => array(),
                               "FamilyLastname" => array(),
                               "ChildID" => array(),
                               "ChildFirstname" => array()
                              );

         if ($DbResult->numRows() > 0)
         {
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["ExitPermissionID"][] = $Record["ExitPermissionID"];
                 $ArrayRecords["ExitPermissionDate"][] = $Record["ExitPermissionDate"];
                 $ArrayRecords["ExitPermissionName"][] = $Record["ExitPermissionName"];
                 $ArrayRecords["ExitPermissionAuthorizedPerson"][] = $Record["ExitPermissionAuthorizedPerson"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
             }
         }

         return $ArrayRecords;
     }

     // ERROR
     return array();
 }


/**
 * Delete an exit permission, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $ExitPermissionID          Integer      ID of the exit permission to delete [1..n]
 *
 * @return Boolean                   TRUE if the exit permission is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteExitPermission($DbConnection, $ExitPermissionID)
 {
     // The parameters are correct?
     if ($ExitPermissionID > 0)
     {
         // Delete the exit permission in the table
         $DbResult = $DbConnection->query("DELETE FROM ExitPermissions WHERE ExitPermissionID = $ExitPermissionID");
         if (!DB::isError($DbResult))
         {
             // Exit permission deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the older date of the ExitPermissions table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the ExitPermissions table,
 *                              empty string otherwise
 */
 function getExitPermissionMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(ExitPermissionDate) As minDate FROM ExitPermissions");
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
 * Give the earlier date of the ExitPermissions table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-09
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the ExitPermissions table,
 *                              empty string otherwise
 */
 function getExitPermissionMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(ExitPermissionDate) As maxDate FROM ExitPermissions");
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