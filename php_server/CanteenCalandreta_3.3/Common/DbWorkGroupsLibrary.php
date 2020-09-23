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
 * Common module : library of database functions used for the WorkGroups and WorkGroupRegistrations tables
 *
 * @author Christophe Javouhey
 * @version 2.9
 * @since 2015-10-12
 */


/**
 * Check if a workgroup exists in the WorkGroups table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $WorkGroupID          Integer      ID of the workgroup searched [1..n]
 *
 * @return Boolean              TRUE if the workgroup exists, FALSE otherwise
 */
 function isExistingWorkGroup($DbConnection, $WorkGroupID)
 {
     $DbResult = $DbConnection->query("SELECT WorkGroupID FROM WorkGroups WHERE WorkGroupID = $WorkGroupID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The workgroup exists
             return TRUE;
         }
     }

     // The workgroup doesn't exist
     return FALSE;
 }


/**
 * Give the ID of a workgroup thanks to its name
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $WorkGroupName        String       Name of the workgroup searched
 *
 * @return Integer              ID of the alias, 0 otherwise
 */
 function getWorkGroupID($DbConnection, $WorkGroupName)
 {
     $DbResult = $DbConnection->query("SELECT WorkGroupID FROM WorkGroups WHERE WorkGroupName = \"$WorkGroupName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["WorkGroupID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the name of a workgroup thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $WorkGroupID          Integer      ID of the workgroup searched
 *
 * @return String               Name of the workgroup, empty string otherwise
 */
 function getWorkGroupName($DbConnection, $WorkGroupID)
 {
     $DbResult = $DbConnection->query("SELECT WorkGroupName FROM WorkGroups WHERE WorkGroupID = $WorkGroupID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["WorkGroupName"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Add a workgroup in the WorkGroups table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-03-01 : patch a bug
 *
 * @since 2015-10-12
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $WorkGroupName                 String       Name of the workgroup
 * @param $WorkGroupDescription          String       Description of the workgroup
 * @param $WorkGroupEmail                String       E-mail of the workgroup
 *
 * @return Integer                       The primary key of the workgroup [1..n], 0 otherwise
 */
 function dbAddWorkGroup($DbConnection, $WorkGroupName, $WorkGroupDescription = NULL, $WorkGroupEmail = NULL)
 {
     if (!empty($WorkGroupName))
     {
         // Check if the workgroup is a new workgroup
         $DbResult = $DbConnection->query("SELECT WorkGroupID FROM WorkGroups WHERE WorkGroupName = \"$WorkGroupName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 if (empty($WorkGroupDescription))
                 {
                     $WorkGroupDescription = "";
                 }
                 else
                 {
                     $WorkGroupDescription = ", WorkGroupDescription = \"$WorkGroupDescription\"";
                 }

                 if (empty($WorkGroupEmail))
                 {
                     $WorkGroupEmail = "";
                 }
                 else
                 {
                     $WorkGroupEmail = ", WorkGroupEmail = \"$WorkGroupEmail\"";
                 }

                 // It's a new workgroup
                 $id = getNewPrimaryKey($DbConnection, "WorkGroups", "WorkGroupID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO WorkGroups SET WorkGroupID = $id, WorkGroupName = \"$WorkGroupName\"
                                                      $WorkGroupDescription $WorkGroupEmail");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The workgroup already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['WorkGroupID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing workgroup in the WorkGroups table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $WorkGroupID                   Integer      ID of the workgroup to update [1..n]
 * @param $WorkGroupName                 String       Name of the workgroup
 * @param $WorkGroupDescription          String       Description of the workgroup
 * @param $WorkGroupEmail                String       E-mail of the workgroup
 *
 * @return Integer                       The primary key of the workgroup [1..n], 0 otherwise
 */
 function dbUpdateWorkGroup($DbConnection, $WorkGroupID, $WorkGroupName = NULL, $WorkGroupDescription = NULL, $WorkGroupEmail = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($WorkGroupID < 1) || (!isInteger($WorkGroupID)))
     {
         // ERROR
         return 0;
     }

     // Check if the WorkGroupName is valide
     if (!is_null($WorkGroupName))
     {
         if (empty($WorkGroupName))
         {
             return 0;
         }
         else
         {
             // The WorkGroupName field will be updated
             $ArrayParamsUpdate[] = "WorkGroupName = \"$WorkGroupName\"";
         }
     }

     if (!is_Null($WorkGroupDescription))
     {
         // The WorkGroupDescription field will be updated
         $ArrayParamsUpdate[] = "WorkGroupDescription = \"$WorkGroupDescription\"";
     }

     if (!is_Null($WorkGroupEmail))
     {
         // The WorkGroupEmail field will be updated
         $ArrayParamsUpdate[] = "WorkGroupEmail = \"$WorkGroupEmail\"";
     }

     // Here, the parameters are correct, we check if the workgroup exists
     if (isExistingWorkGroup($DbConnection, $WorkGroupID))
     {
         // We check if the workgroup name is unique
         $DbResult = $DbConnection->query("SELECT WorkGroupID FROM WorkGroups WHERE WorkGroupName = \"$WorkGroupName\"
                                          AND WorkGroupID <> $WorkGroupID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The workgroup exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE WorkGroups SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE WorkGroupID = $WorkGroupID");
                     if (!DB::isError($DbResult))
                     {
                         // Workgroup updated
                         return $WorkGroupID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $WorkGroupID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Get workgroups filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the workgroups
 * @param $OrderBy                  String                 Criteria used to sort the workgroups. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of workgroups per page to return [1..n]
 *
 * @return Array of String                                 List of workgroups filtered, an empty array otherwise
 */
 function dbSearchWorkGroup($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find workgroups
     $Select = "SELECT wg.WorkGroupID, wg.WorkGroupName, wg.WorkGroupDescription, wg.WorkGroupEmail,
                COUNT(wgr.WorkGroupRegistrationID) AS NbRegistrations";
     $From = "FROM WorkGroups wg LEFT JOIN WorkGroupRegistrations wgr ON (wg.WorkGroupID = wgr.WorkGroupID)";
     $Where = "WHERE 1=1";
     $Having = "";

     $FromRegistrations = '';
     if (count($ArrayParams) >= 0)
     {
         // <<< WorkGroupID field >>>
         if ((array_key_exists("WorkGroupID", $ArrayParams)) && (!empty($ArrayParams["WorkGroupID"])))
         {
             $Where .= " AND wg.WorkGroupID = ".$ArrayParams["WorkGroupID"];
         }

         // <<< WorkGroupName field >>>
         if ((array_key_exists("WorkGroupName", $ArrayParams)) && (!empty($ArrayParams["WorkGroupName"])))
         {
             $Where .= " AND wg.WorkGroupName LIKE \"".$ArrayParams["WorkGroupName"]."\"";
         }

         // <<< WorkGroupEmail field >>>
         if ((array_key_exists("WorkGroupEmail", $ArrayParams)) && (!empty($ArrayParams["WorkGroupEmail"])))
         {
             $Where .= " AND wg.WorkGroupEmail LIKE \"".$ArrayParams["WorkGroupEmail"]."\"";
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             // Registered families to workgroups
             $FromRegistrations = " LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";

             $Where .= " AND wgr.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
         }

         // <<< FamilyLastname pseudo-field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             // Registered families to workgroups
             $FromRegistrations = " LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";

             $Where .= " AND (f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"
                              OR wgr.WorkGroupRegistrationLastname LIKE \"".$ArrayParams["FamilyLastname"]."\")";
         }

         // <<< FamilyEmail pseudo-field >>>
         if ((array_key_exists("FamilyEmail", $ArrayParams)) && (!empty($ArrayParams["FamilyEmail"])))
         {
             // Registered families to workgroups
             $FromRegistrations = " LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";

             $Where .= " AND (f.FamilyMainEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR f.FamilySecondEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR wgr.WorkGroupRegistrationEmail LIKE \"".$ArrayParams["FamilyEmail"]."\")";
         }
     }

     // We take into account the page and the number of workgroups per page
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
     $DbResult = $DbConnection->query("$Select $From $FromRegistrations $Where GROUP BY WorkGroupID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "WorkGroupID" => array(),
                                   "WorkGroupName" => array(),
                                   "WorkGroupDescription" => array(),
                                   "WorkGroupEmail" => array(),
                                   "NbRegistrations" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["WorkGroupID"][] = $Record["WorkGroupID"];
                 $ArrayRecords["WorkGroupName"][] = $Record["WorkGroupName"];
                 $ArrayRecords["WorkGroupDescription"][] = $Record["WorkGroupDescription"];
                 $ArrayRecords["WorkGroupEmail"][] = $Record["WorkGroupEmail"];

                 if (empty($Record["NbRegistrations"]))
                 {
                     $Record["NbRegistrations"] = 0;
                 }
                 $ArrayRecords["NbRegistrations"][] = $Record["NbRegistrations"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of workgroups filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-13
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the workgroups
 *
 * @return Integer              Number of the workgroups found, 0 otherwise
 */
 function getNbdbSearchWorkGroup($DbConnection, $ArrayParams)
 {
     // SQL request to find workgroups
     $Select = "SELECT wg.WorkGroupID, COUNT(wgr.WorkGroupRegistrationID) AS NbRegistrations";
     $From = "FROM WorkGroups wg LEFT JOIN WorkGroupRegistrations wgr ON (wg.WorkGroupID = wgr.WorkGroupID)";
     $Where = "WHERE 1=1";
     $Having = "";

     $FromRegistrations = '';
     if (count($ArrayParams) >= 0)
     {
         // <<< WorkGroupID field >>>
         if ((array_key_exists("WorkGroupID", $ArrayParams)) && (!empty($ArrayParams["WorkGroupID"])))
         {
             $Where .= " AND wg.WorkGroupID = ".$ArrayParams["WorkGroupID"];
         }

         // <<< WorkGroupName field >>>
         if ((array_key_exists("WorkGroupName", $ArrayParams)) && (!empty($ArrayParams["WorkGroupName"])))
         {
             $Where .= " AND wg.WorkGroupName LIKE \"".$ArrayParams["WorkGroupName"]."\"";
         }

         // <<< WorkGroupEmail field >>>
         if ((array_key_exists("WorkGroupEmail", $ArrayParams)) && (!empty($ArrayParams["WorkGroupEmail"])))
         {
             $Where .= " AND wg.WorkGroupEmail LIKE \"".$ArrayParams["WorkGroupEmail"]."\"";
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             // Registered families to workgroups
             $FromRegistrations = " LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";

             $Where .= " AND wgr.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
         }

         // <<< FamilyLastname pseudo-field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             // Registered families to workgroups
             $FromRegistrations = " LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";

             $Where .= " AND (f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"
                              OR wgr.WorkGroupRegistrationLastname LIKE \"".$ArrayParams["FamilyLastname"]."\")";
         }

         // <<< FamilyEmail pseudo-field >>>
         if ((array_key_exists("FamilyEmail", $ArrayParams)) && (!empty($ArrayParams["FamilyEmail"])))
         {
             // Registered families to workgroups
             $FromRegistrations = " LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";

             $Where .= " AND (f.FamilyMainEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR f.FamilySecondEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR wgr.WorkGroupRegistrationEmail LIKE \"".$ArrayParams["FamilyEmail"]."\")";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $FromRegistrations $Where GROUP BY WorkGroupID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Delete a workgroup, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-20
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $WorkGroupID               Integer      ID of the workgroup to delete [1..n]
 *
 * @return Boolean                   TRUE if the workgroup is deleted, FALSE otherwise
 */
 function dbDeleteWorkGroup($DbConnection, $WorkGroupID)
 {
     // The parameters are correct?
     if ($WorkGroupID > 0)
     {
         // First, delete registrations of the workgroup
         $DbConnection->query("DELETE FROM WorkGroupRegistrations WHERE WorkGroupID = $WorkGroupID");

         // Next, delete the workgroup
         $DbResult = $DbConnection->query("DELETE FROM WorkGroups WHERE WorkGroupID = $WorkGroupID");
         if (!DB::isError($DbResult))
         {
             // Workgroup deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Check if a workgroup registration exists in the WorkGroupRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection                 DB object    Object of the opened database connection
 * @param $WorkGroupRegistrationID      Integer      ID of the workgroup registration searched [1..n]
 *
 * @return Boolean                      TRUE if the workgroup registration exists, FALSE otherwise
 */
 function isExistingWorkGroupRegistration($DbConnection, $WorkGroupRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT WorkGroupRegistrationID FROM WorkGroupRegistrations
                                       WHERE WorkGroupRegistrationID = $WorkGroupRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The workgroup registration exists
             return TRUE;
         }
     }

     // The workgroup registration doesn't exist
     return FALSE;
 }


/**
 * Add a workgroup registration for a person/family in the WorkGroupRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-14
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $WorkGroupRegistrationDate         Date         Creation date of the workgroup registration (yyyy-mm-dd hh:mm:ss)
 * @param $WorkGroupID                       Integer      ID of the workgroup concerned by the registration [1..n]
 * @param $SupportMemberID                   Integer      ID of the supporter, author of the workgroup registration [1..n]
 * @param $WorkGroupRegistrationLastname     String       Lastname of the person concerned by the registration
 * @param $WorkGroupRegistrationFirstname    String       Firstname of the person concerned by the registration
 * @param $WorkGroupRegistrationEmail        String       Email of the person concerned by the registration
 * @param $WorkGroupRegistrationReferent     Integer      1 if the person is a referent of the workgroup, 0 otherwise [0..1]
 * @param $FamilyID                          Integer      ID of the family concerned by the registration [0..n]
 *
 * @return Integer                           The primary key of the workgroup registration [1..n], 0 otherwise
 */
 function dbAddWorkGroupRegistration($DbConnection, $WorkGroupRegistrationDate, $WorkGroupID, $SupportMemberID, $WorkGroupRegistrationLastname, $WorkGroupRegistrationFirstname, $WorkGroupRegistrationEmail, $WorkGroupRegistrationReferent = 0, $FamilyID = NULL)
 {
     if (($WorkGroupID > 0) && ($SupportMemberID > 0) && (!empty($WorkGroupRegistrationLastname))
         && (!empty($WorkGroupRegistrationFirstname)) && (!empty($WorkGroupRegistrationEmail)) && ($WorkGroupRegistrationReferent >= 0))
     {
         // Check if the workgroup registration is a new workgroup registration for the person and workgroup
         $DbResult = $DbConnection->query("SELECT WorkGroupRegistrationID FROM WorkGroupRegistrations WHERE WorkGroupID = $WorkGroupID
                                          AND WorkGroupRegistrationEmail = \"$WorkGroupRegistrationEmail\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the WorkGroupRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $WorkGroupRegistrationDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $WorkGroupRegistrationDate = ", WorkGroupRegistrationDate = \"$WorkGroupRegistrationDate\"";
                 }

                 if (empty($FamilyID))
                 {
                     $FamilyID = ", FamilyID = NULL";
                 }
                 else
                 {
                     $FamilyID = ", FamilyID = $FamilyID";
                 }

                 // It's a new workgroup registration
                 $id = getNewPrimaryKey($DbConnection, "WorkGroupRegistrations", "WorkGroupRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO WorkGroupRegistrations SET WorkGroupRegistrationID = $id,
                                                      WorkGroupID = $WorkGroupID, SupportMemberID = $SupportMemberID,
                                                      WorkGroupRegistrationLastname = \"$WorkGroupRegistrationLastname\",
                                                      WorkGroupRegistrationFirstname = \"$WorkGroupRegistrationFirstname\",
                                                      WorkGroupRegistrationEmail = \"$WorkGroupRegistrationEmail\",
                                                      WorkGroupRegistrationReferent = $WorkGroupRegistrationReferent $FamilyID
                                                      $WorkGroupRegistrationDate");
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
 * Update an existing workgroup registration in the WorkGroupRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-14
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $WorkGroupRegistrationID           Integer      ID of the workgroup registration to update [1..n]
 * @param $WorkGroupRegistrationDate         Date         Creation date of the workgroup registration (yyyy-mm-dd hh:mm:ss)
 * @param $WorkGroupID                       Integer      ID of the workgroup concerned by the registration [1..n]
 * @param $SupportMemberID                   Integer      ID of the supporter, author of the workgroup registration [1..n]
 * @param $WorkGroupRegistrationLastname     String       Lastname of the person concerned by the registration
 * @param $WorkGroupRegistrationFirstname    String       Firstname of the person concerned by the registration
 * @param $WorkGroupRegistrationEmail        String       Email of the person concerned by the registration
 * @param $WorkGroupRegistrationReferent     Integer      1 if the person is a referent of the workgroup, 0 otherwise [0..1]
 * @param $FamilyID                          Integer      ID of the family concerned by the registration [1..n]
 *
 * @return Integer                           The primary key of the workgroup registration [1..n], 0 otherwise
 */
 function dbUpdateWorkGroupRegistration($DbConnection, $WorkGroupRegistrationID, $WorkGroupRegistrationDate, $WorkGroupID, $SupportMemberID, $WorkGroupRegistrationLastname, $WorkGroupRegistrationFirstname, $WorkGroupRegistrationEmail, $WorkGroupRegistrationReferent = 0, $FamilyID = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($WorkGroupRegistrationID < 1) || (!isInteger($WorkGroupRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the WorkGroupRegistrationDate is valide
     if (!is_null($WorkGroupRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $WorkGroupRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The WorkGroupRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "WorkGroupRegistrationDate = \"$WorkGroupRegistrationDate\"";
         }
     }

     if (!is_null($WorkGroupID))
     {
         if (($WorkGroupID < 1) || (!isInteger($WorkGroupID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "WorkGroupID = $WorkGroupID";
         }
     }

     if (!is_null($SupportMemberID))
     {
         if (($SupportMemberID < 1) || (!isInteger($SupportMemberID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "SupportMemberID = $SupportMemberID";
         }
     }

     if (!is_Null($WorkGroupRegistrationLastname))
     {
         if (empty($WorkGroupRegistrationLastname))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The WorkGroupRegistrationLastname field will be updated
             $ArrayParamsUpdate[] = "WorkGroupRegistrationLastname = \"$WorkGroupRegistrationLastname\"";
         }
     }

     if (!is_Null($WorkGroupRegistrationFirstname))
     {
         if (empty($WorkGroupRegistrationFirstname))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The WorkGroupRegistrationFirstname field will be updated
             $ArrayParamsUpdate[] = "WorkGroupRegistrationFirstname = \"$WorkGroupRegistrationFirstname\"";
         }
     }

     if (!is_Null($WorkGroupRegistrationEmail))
     {
         if (empty($WorkGroupRegistrationEmail))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The WorkGroupRegistrationEmail field will be updated
             $ArrayParamsUpdate[] = "WorkGroupRegistrationEmail = \"$WorkGroupRegistrationEmail\"";
         }
     }

     if (!is_Null($WorkGroupRegistrationReferent))
     {
         if (($WorkGroupRegistrationReferent < 0) || (!isInteger($WorkGroupRegistrationReferent)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The WorkGroupRegistrationReferent field will be updated
             $ArrayParamsUpdate[] = "WorkGroupRegistrationReferent = $WorkGroupRegistrationReferent";
         }
     }

     if (!is_null($FamilyID))
     {
         if (($FamilyID < 0) || (!isInteger($FamilyID)))
         {
             // ERROR
             return 0;
         }
         elseif (empty($FamilyID))
         {
             $ArrayParamsUpdate[] = "FamilyID = NULL";
         }
         else
         {
             $ArrayParamsUpdate[] = "FamilyID = $FamilyID";
         }
     }

     // Here, the parameters are correct, we check if the workgroup registration exists
     if (isExistingWorkGroupRegistration($DbConnection, $WorkGroupRegistrationID))
     {
         // We check if the workgroup registration is unique
         $DbResult = $DbConnection->query("SELECT WorkGroupRegistrationID FROM WorkGroupRegistrations WHERE WorkGroupID = $WorkGroupID
                                           AND WorkGroupRegistrationEmail = \"$WorkGroupRegistrationEmail\"
                                           AND WorkGroupRegistrationID <> $WorkGroupRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The workgroup registration entry exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE WorkGroupRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE WorkGroupRegistrationID = $WorkGroupRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Workgroup registration updated
                         return $WorkGroupRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $WorkGroupRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Delete a workgroup registration, thanks to its ID, or all registrations of a given workgroup
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $WorkGroupRegistrationID       Integer      ID of the workgroup registration to delete [1..n] (or NULL)
 * @param $WorkGroupID                   Integer      ID of the concerned workgroup [1..n] (or NULL)
 *
 * @return Boolean                       TRUE if the workgroup registration(s) is(are) deleted,
 *                                       FALSE otherwise
 */
 function dbDeleteWorkGroupRegistration($DbConnection, $WorkGroupRegistrationID, $WorkGroupID = NULL)
 {
     if ((empty($WorkGroupID)) && ($WorkGroupRegistrationID > 0))
     {
         // We delete one workgroup registration
         $DbResult = $DbConnection->query("DELETE FROM WorkGroupRegistrations WHERE WorkGroupRegistrationID = $WorkGroupRegistrationID");
         if (!DB::isError($DbResult))
         {
             // Registration deleted
             return TRUE;
         }
     }
     elseif ($WorkGroupID > 0)
     {
         // We delete all registrations of a workgroup
         $DbResult = $DbConnection->query("DELETE FROM WorkGroupRegistrations WHERE WorkGroupID = $WorkGroupID");
         if (!DB::isError($DbResult))
         {
             // Registrations deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Get workgroup registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the workgroup registrations
 * @param $OrderBy                  String                 Criteria used to sort the workgroup registrations. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of workgroup registrations per page to return [1..n]
 *
 * @return Array of String          List of workgroup registrations filtered, an empty array otherwise
 */
 function dbSearchWorkGroupRegistration($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find workgroup registrations
     $Select = "SELECT wgr.WorkGroupRegistrationID, wgr.WorkGroupRegistrationDate, wgr.WorkGroupRegistrationReferent,
                wgr.WorkGroupRegistrationLastname, wgr.WorkGroupRegistrationFirstname, wgr.WorkGroupRegistrationEmail, wgr.WorkGroupID,
                wgr.SupportMemberID, wg.WorkGroupName, wg.WorkGroupEmail, f.FamilyID, f.FamilyLastname";
     $From = "FROM WorkGroups wg, WorkGroupRegistrations wgr LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";
     $Where = "WHERE wg.WorkGroupID = wgr.WorkGroupID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< WorkGroupID field >>>
         if ((array_key_exists("WorkGroupID", $ArrayParams)) && (!empty($ArrayParams["WorkGroupID"])))
         {
             $Where .= " AND wg.WorkGroupID = ".$ArrayParams["WorkGroupID"];
         }

         // <<< WorkGroupName field >>>
         if ((array_key_exists("WorkGroupName", $ArrayParams)) && (!empty($ArrayParams["WorkGroupName"])))
         {
             $Where .= " AND wg.WorkGroupName LIKE \"".$ArrayParams["WorkGroupName"]."\"";
         }

         // <<< WorkGroupEmail field >>>
         if ((array_key_exists("WorkGroupEmail", $ArrayParams)) && (!empty($ArrayParams["WorkGroupEmail"])))
         {
             $Where .= " AND wg.WorkGroupEmail LIKE \"".$ArrayParams["WorkGroupEmail"]."\"";
         }

         // <<< WorkGroupRegistrationReferent field >>>
         if ((array_key_exists("WorkGroupRegistrationReferent", $ArrayParams)) && (count($ArrayParams["WorkGroupRegistrationReferent"]) > 0))
         {
             // Registered families to workgroups
             $Where .= " AND wgr.WorkGroupRegistrationReferent IN ".constructSQLINString($ArrayParams["WorkGroupRegistrationReferent"]);
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             // Registered families to workgroups
             $Where .= " AND wgr.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
         }

         // <<< FamilyLastname pseudo-field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             // Registered families to workgroups
             $Where .= " AND (f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"
                              OR wgr.WorkGroupRegistrationLastname LIKE \"".$ArrayParams["FamilyLastname"]."\")";
         }

         // <<< FamilyEmail pseudo-field >>>
         if ((array_key_exists("FamilyEmail", $ArrayParams)) && (!empty($ArrayParams["FamilyEmail"])))
         {
             // Registered families to workgroups
             $Where .= " AND (f.FamilyMainEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR f.FamilySecondEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR wgr.WorkGroupRegistrationEmail LIKE \"".$ArrayParams["FamilyEmail"]."\")";
         }

         // <<< Option : get activated families >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NULL";
             }
             else
             {
                 // Not activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NOT NULL";
             }
         }
     }

     // We take into account the page and the number of workgroup registrations per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY WorkGroupRegistrationID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "WorkGroupRegistrationID" => array(),
                                   "WorkGroupRegistrationDate" => array(),
                                   "WorkGroupRegistrationReferent" => array(),
                                   "WorkGroupRegistrationLastname" => array(),
                                   "WorkGroupRegistrationFirstname" => array(),
                                   "WorkGroupRegistrationEmail" => array(),
                                   "SupportMemberID" => array(),
                                   "WorkGroupID" => array(),
                                   "WorkGroupName" => array(),
                                   "WorkGroupEmail" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["WorkGroupRegistrationID"][] = $Record["WorkGroupRegistrationID"];
                 $ArrayRecords["WorkGroupRegistrationDate"][] = $Record["WorkGroupRegistrationDate"];
                 $ArrayRecords["WorkGroupRegistrationReferent"][] = $Record["WorkGroupRegistrationReferent"];
                 $ArrayRecords["WorkGroupRegistrationLastname"][] = $Record["WorkGroupRegistrationLastname"];
                 $ArrayRecords["WorkGroupRegistrationFirstname"][] = $Record["WorkGroupRegistrationFirstname"];
                 $ArrayRecords["WorkGroupRegistrationEmail"][] = $Record["WorkGroupRegistrationEmail"];
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
                 $ArrayRecords["WorkGroupID"][] = $Record["WorkGroupID"];
                 $ArrayRecords["WorkGroupName"][] = $Record["WorkGroupName"];
                 $ArrayRecords["WorkGroupEmail"][] = $Record["WorkGroupEmail"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of workgroup registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-13
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the workgroup registrations
 *
 * @return Integer              Number of the workgroup registrations found, 0 otherwise
 */
 function getNbdbSearchWorkGroupRegistration($DbConnection, $ArrayParams)
 {
     // SQL request to find workgroup registrations
     $Select = "SELECT wgr.WorkGroupRegistrationID";
     $From = "FROM WorkGroups wg, WorkGroupRegistrations wgr LEFT JOIN Families f ON (f.FamilyID = wgr.FamilyID)";
     $Where = "WHERE wg.WorkGroupID = wgr.WorkGroupID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< WorkGroupID field >>>
         if ((array_key_exists("WorkGroupID", $ArrayParams)) && (!empty($ArrayParams["WorkGroupID"])))
         {
             $Where .= " AND wg.WorkGroupID = ".$ArrayParams["WorkGroupID"];
         }

         // <<< WorkGroupName field >>>
         if ((array_key_exists("WorkGroupName", $ArrayParams)) && (!empty($ArrayParams["WorkGroupName"])))
         {
             $Where .= " AND wg.WorkGroupName LIKE \"".$ArrayParams["WorkGroupName"]."\"";
         }

         // <<< WorkGroupEmail field >>>
         if ((array_key_exists("WorkGroupEmail", $ArrayParams)) && (!empty($ArrayParams["WorkGroupEmail"])))
         {
             $Where .= " AND wg.WorkGroupEmail LIKE \"".$ArrayParams["WorkGroupEmail"]."\"";
         }

         // <<< WorkGroupRegistrationReferent field >>>
         if ((array_key_exists("WorkGroupRegistrationReferent", $ArrayParams)) && (count($ArrayParams["WorkGroupRegistrationReferent"]) > 0))
         {
             // Registered families to workgroups
             $Where .= " AND wgr.WorkGroupRegistrationReferent IN ".constructSQLINString($ArrayParams["WorkGroupRegistrationReferent"]);
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             // Registered families to workgroups
             $Where .= " AND wgr.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
         }

         // <<< FamilyLastname pseudo-field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             // Registered families to workgroups
             $Where .= " AND (f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"
                              OR wgr.WorkGroupRegistrationLastname LIKE \"".$ArrayParams["FamilyLastname"]."\")";
         }

         // <<< FamilyEmail pseudo-field >>>
         if ((array_key_exists("FamilyEmail", $ArrayParams)) && (!empty($ArrayParams["FamilyEmail"])))
         {
             // Registered families to workgroups
             $Where .= " AND (f.FamilyMainEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR f.FamilySecondEmail LIKE \"".$ArrayParams["FamilyEmail"]."\"
                        OR wgr.WorkGroupRegistrationEmail LIKE \"".$ArrayParams["FamilyEmail"]."\")";
         }

         // <<< Option : get activated families >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NULL";
             }
             else
             {
                 // Not activated families
                 $Where .= " AND f.FamilyDesactivationDate IS NOT NULL";
             }
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY WorkGroupRegistrationID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }
?>