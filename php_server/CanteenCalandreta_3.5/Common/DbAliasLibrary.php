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
 * Common module : library of database functions used for the alias table
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2016-03-01
 */


/**
 * Check if an alias exists in the Alias table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $AliasID              Integer      ID of the alias searched [1..n]
 *
 * @return Boolean              TRUE if the alias exists, FALSE otherwise
 */
 function isExistingAlias($DbConnection, $AliasID)
 {
     $DbResult = $DbConnection->query("SELECT AliasID FROM Alias WHERE AliasID = $AliasID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The alias exists
             return TRUE;
         }
     }

     // The alias doesn't exist
     return FALSE;
 }


/**
 * Give the ID of an alias thanks to its name
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $AliasName            String       Name of the alias searched
 *
 * @return Integer              ID of the alias, 0 otherwise
 */
 function getAliasID($DbConnection, $AliasName)
 {
     $DbResult = $DbConnection->query("SELECT AliasID FROM Alias WHERE AliasName = \"$AliasName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["AliasID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the name of an alias thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $AliasID              Integer      ID of the alias searched
 *
 * @return String               Name of the alias, empty string otherwise
 */
 function getAliasName($DbConnection, $AliasID)
 {
     $DbResult = $DbConnection->query("SELECT AliasName FROM Alias WHERE AliasID = $AliasID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["AliasName"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Add an alias in the Alias table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $AliasName                     String       Name of the alias
 * @param $AliasMailingList              String       Mailing-list of the alias
 * @param $AliasDescription              String       Description of the alias
 *
 * @return Integer                       The primary key of the alias [1..n], 0 otherwise
 */
 function dbAddAlias($DbConnection, $AliasName, $AliasMailingList, $AliasDescription = NULL)
 {
     if ((!empty($AliasName)) && (!empty($AliasMailingList)))
     {
         // Check if the alias is a new alias
         $DbResult = $DbConnection->query("SELECT AliasID FROM Alias WHERE AliasName = \"$AliasName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 if (empty($AliasDescription))
                 {
                     $AliasDescription = "";
                 }
                 else
                 {
                     $AliasDescription = ", AliasDescription = \"$AliasDescription\"";
                 }

                 // It's a new alias
                 $id = getNewPrimaryKey($DbConnection, "Alias", "AliasID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Alias SET AliasID = $id, AliasName = \"$AliasName\",
                                                       AliasMailingList = \"$AliasMailingList\" $AliasDescription");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The alias already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['AliasID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing alias in the Alias table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $AliasID                       Integer      ID of the alias to update [1..n]
 * @param $AliasName                     String       Name of the alias
 * @param $AliasMailingList              String       Mailing-list of the alias
 * @param $AliasDescription              String       Description of the alias
 *
 * @return Integer                       The primary key of the alias [1..n], 0 otherwise
 */
 function dbUpdateAlias($DbConnection, $AliasID, $AliasName = NULL, $AliasMailingList = NULL, $AliasDescription = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($AliasID < 1) || (!isInteger($AliasID)))
     {
         // ERROR
         return 0;
     }

     // Check if the AliasName is valide
     if (!is_null($AliasName))
     {
         if (empty($AliasName))
         {
             return 0;
         }
         else
         {
             // The AliasName field will be updated
             $ArrayParamsUpdate[] = "AliasName = \"$AliasName\"";
         }
     }

     // Check if the AliasMailingList is valide
     if (!is_null($AliasMailingList))
     {
         if (empty($AliasMailingList))
         {
             return 0;
         }
         else
         {
             // The AliasMailingList field will be updated
             $ArrayParamsUpdate[] = "AliasMailingList = \"$AliasMailingList\"";
         }
     }

     if (!is_Null($AliasDescription))
     {
         // The AliasDescription field will be updated
         $ArrayParamsUpdate[] = "AliasDescription = \"$AliasDescription\"";
     }

     // Here, the parameters are correct, we check if the alias exists
     if (isExistingAlias($DbConnection, $AliasID))
     {
         // We check if the alias name is unique
         $DbResult = $DbConnection->query("SELECT AliasID FROM Alias WHERE AliasName = \"$AliasName\"
                                           AND AliasID <> $AliasID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The alias exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Alias SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE AliasID = $AliasID");
                     if (!DB::isError($DbResult))
                     {
                         // Alias updated
                         return $AliasID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $AliasID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Get alias filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the alias
 * @param $OrderBy                  String                 Criteria used to sort the alias. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of alias per page to return [1..n]
 *
 * @return Array of String                                 List of alias filtered, an empty array otherwise
 */
 function dbSearchAlias($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find alias
     $Select = "SELECT a.AliasID, a.AliasName, a.AliasDescription, a.AliasMailingList";
     $From = "FROM Alias a";
     $Where = "WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< AliasID field >>>
         if ((array_key_exists("AliasID", $ArrayParams)) && (!empty($ArrayParams["AliasID"])))
         {
             $Where .= " AND a.AliasID = ".$ArrayParams["AliasID"];
         }

         // <<< AliasName field >>>
         if ((array_key_exists("AliasName", $ArrayParams)) && (!empty($ArrayParams["AliasName"])))
         {
             $Where .= " AND a.AliasName LIKE \"".$ArrayParams["AliasName"]."\"";
         }

         // <<< AliasDescription field >>>
         if ((array_key_exists("AliasDescription", $ArrayParams)) && (!empty($ArrayParams["AliasDescription"])))
         {
             $Where .= " AND a.AliasDescription LIKE \"".$ArrayParams["AliasDescription"]."\"";
         }

         // <<< AliasMailingList field >>>
         if ((array_key_exists("AliasMailingList", $ArrayParams)) && (!empty($ArrayParams["AliasMailingList"])))
         {
             $Where .= " AND a.AliasMailingList LIKE \"".$ArrayParams["AliasMailingList"]."\"";
         }
     }

     // We take into account the page and the number of alias per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY AliasID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "AliasID" => array(),
                                   "AliasName" => array(),
                                   "AliasDescription" => array(),
                                   "AliasMailingList" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["AliasID"][] = $Record["AliasID"];
                 $ArrayRecords["AliasName"][] = $Record["AliasName"];
                 $ArrayRecords["AliasDescription"][] = $Record["AliasDescription"];
                 $ArrayRecords["AliasMailingList"][] = $Record["AliasMailingList"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of alias filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the alias
 *
 * @return Integer              Number of the alias found, 0 otherwise
 */
 function getNbdbSearchAlias($DbConnection, $ArrayParams)
 {
     // SQL request to find alias
     $Select = "SELECT a.AliasID";
     $From = "FROM Alias a";
     $Where = "WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< AliasID field >>>
         if ((array_key_exists("AliasID", $ArrayParams)) && (!empty($ArrayParams["AliasID"])))
         {
             $Where .= " AND a.AliasID = ".$ArrayParams["AliasID"];
         }

         // <<< AliasName field >>>
         if ((array_key_exists("AliasName", $ArrayParams)) && (!empty($ArrayParams["AliasName"])))
         {
             $Where .= " AND a.AliasName LIKE \"".$ArrayParams["AliasName"]."\"";
         }

         // <<< AliasDescription field >>>
         if ((array_key_exists("AliasDescription", $ArrayParams)) && (!empty($ArrayParams["AliasDescription"])))
         {
             $Where .= " AND a.AliasDescription LIKE \"".$ArrayParams["AliasDescription"]."\"";
         }

         // <<< AliasMailingList field >>>
         if ((array_key_exists("AliasMailingList", $ArrayParams)) && (!empty($ArrayParams["AliasMailingList"])))
         {
             $Where .= " AND a.AliasMailingList LIKE \"".$ArrayParams["AliasMailingList"]."\"";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY AliasID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Delete an alias, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $AliasID                   Integer      ID of the alias to delete [1..n]
 *
 * @return Boolean                   TRUE if the alias is deleted, FALSE otherwise
 */
 function dbDeleteAlias($DbConnection, $AliasID)
 {
     // The parameters are correct?
     if ($AliasID > 0)
     {
         // Delete the alias
         $DbResult = $DbConnection->query("DELETE FROM Alias WHERE AliasID = $AliasID");
         if (!DB::isError($DbResult))
         {
             // Alias deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Get recipients for messages, filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2018-02-26 : taken into account Towns as search criteria to contact families
 *
 * @since 2016-03-02
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the alias
 * @param $OrderBy                  String                 Criteria used to sort the recipients. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of recipients per page to return [1..n]
 *
 * @return Array of String                                 List of recipients filtered, an empty array otherwise
 */
 function dbSearchMessageRecipients($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find recipients for messages
     $sSQLRequest = "";

     // Search in Families table
     $Select = "SELECT CONCAT('F', f.FamilyID) AS rID, f.FamilyLastname AS rName, fsms.SupportMemberStateName AS rStateName";
     $From = "FROM Families f, Children c, SupportMembers fsm, SupportMembersStates fsms";
     $Where = "WHERE f.FamilyID = c.FamilyID AND f.FamilyID = fsm.FamilyID AND fsm.SupportMemberStateID = fsms.SupportMemberStateID";

     if (count($ArrayParams) >= 0)
     {
         // <<< Lastname fields >>>
         if ((array_key_exists("Name", $ArrayParams)) && (!empty($ArrayParams["Name"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["Name"]."\"";
         }

         // <<< Family still activated for some school years >>>
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             /* An activated family is a family with :
              * a creation date <= school year start date or a creation date between school year start date and end date
              * and with a desactivaton date NULL or >= school year end date
              * and with at least one activated child for the school year :
              * same criterion for the ChildSchoolDate and ChildDesactivationDate
              */
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND ("
                      ."((f.FamilyDate <= \"$SchoolYearStartDate\") OR (f.FamilyDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                      ." AND (f.FamilyDesactivationDate IS NULL OR f.FamilyDesactivationDate >= \"$SchoolYearEndDate\")"
                      ." AND ((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                      ." AND (c.ChildDesactivationDate IS NULL OR c.ChildDesactivationDate >= \"$SchoolYearEndDate\")"
                      .")";
         }
     }

     $sSQLRequest = "$Select $From $Where GROUP BY rName";

     // Search in SupportMembers table
     $Select = "SELECT CONCAT('S', sm.SupportMemberID) AS rID,
                IF(CHAR_LENGTH(sm.SupportMemberFirstname) >= 2, CONCAT(sm.SupportMemberLastname, ' ', sm.SupportMemberFirstname), sm.SupportMemberLastname) AS rName,
                sms.SupportMemberStateName AS rStateName";
     $From = "FROM SupportMembers sm, SupportMembersStates sms";
     $Where = "WHERE sm.SupportMemberStateID = sms.SupportMemberStateID";

     if (count($ArrayParams) >= 0)
     {
         // <<< Lastname fields >>>
         if ((array_key_exists("Name", $ArrayParams)) && (!empty($ArrayParams["Name"])))
         {
             $Where .= " AND ((sm.SupportMemberLastname LIKE \"".$ArrayParams["Name"]."\") OR (sm.SupportMemberFirstname LIKE \"".$ArrayParams["Name"]."\"))";
         }

         // <<< SupportMemberActivated >>>
         if ((array_key_exists("SupportMemberActivated", $ArrayParams)) && (count($ArrayParams["SupportMemberActivated"]) > 0))
         {
             $Where .= " AND sm.SupportMemberActivated IN ".constructSQLINString($ArrayParams["SupportMemberActivated"]);
         }
     }

     $sSQLRequest .= " UNION $Select $From $Where GROUP BY rName";

     // Search in Towns table
     $Select = "SELECT CONCAT('T', t.TownID) AS rID, t.TownName AS rName, '".$GLOBALS['LANG_TOWN']."' AS rStateName";
     $From = "FROM Towns t";
     $Where = "WHERE 1=1";

     if (count($ArrayParams) >= 0)
     {
         // <<< TownName fields >>>
         if ((array_key_exists("Name", $ArrayParams)) && (!empty($ArrayParams["Name"])))
         {
             $Where .= " AND ((t.TownName LIKE \"".$ArrayParams["Name"]."\") OR (t.TownCode LIKE \"".$ArrayParams["Name"]."\"))";
         }
     }

     $sSQLRequest .= " UNION $Select $From $Where GROUP BY rName";

     // Search in Alias table
     $Select = "SELECT CONCAT('A', a.AliasID) AS rID, a.AliasName AS rName, '".$GLOBALS['LANG_ALIAS']."' AS rStateName";
     $From = "FROM Alias a";
     $Where = "WHERE 1=1";

     if (count($ArrayParams) >= 0)
     {
         // <<< AliasName fields >>>
         if ((array_key_exists("Name", $ArrayParams)) && (!empty($ArrayParams["Name"])))
         {
             $Where .= " AND a.AliasName LIKE \"".$ArrayParams["Name"]."\"";
         }
     }

     $sSQLRequest .= " UNION $Select $From $Where GROUP BY rName";

     // Search in Workgroups table
     $Select = "SELECT CONCAT('W', wg.WorkGroupID) AS rID, wg.WorkGroupName AS rName, '".$GLOBALS['LANG_WORKGROUP']."' AS rStateName";
     $From = "FROM WorkGroups wg";
     $Where = "WHERE 1=1";

     if (count($ArrayParams) >= 0)
     {
         // <<< WorkGroupName fields >>>
         if ((array_key_exists("Name", $ArrayParams)) && (!empty($ArrayParams["Name"])))
         {
             $Where .= " AND ((wg.WorkGroupName LIKE \"".$ArrayParams["Name"]."\") OR (wg.WorkGroupEmail LIKE \"".$ArrayParams["Name"]."\"))";
         }
     }

     $sSQLRequest .= " UNION $Select $From $Where GROUP BY rName";

     // Search in WorkGroupRegistrations table
     $Select = "SELECT CONCAT('R', wgr.WorkGroupRegistrationID) AS rID,
                CONCAT(wgr.WorkGroupRegistrationLastname, ' ', wgr.WorkGroupRegistrationFirstname) AS rName,
                '".$GLOBALS['LANG_WORKGROUP']."' AS rStateName";
     $From = "FROM WorkGroupRegistrations wgr";
     $Where = "WHERE 1=1";

     if (count($ArrayParams) >= 0)
     {
         // <<< WorkGroupRegistrationLastname / WorkGroupRegistrationFirstname fields >>>
         if ((array_key_exists("Name", $ArrayParams)) && (!empty($ArrayParams["Name"])))
         {
             $Where .= " AND ((wgr.WorkGroupRegistrationLastname LIKE \"".$ArrayParams["Name"]."\")
                        OR (wgr.WorkGroupRegistrationFirstname LIKE \"".$ArrayParams["Name"]."\"))";
         }
     }

     $sSQLRequest .= " UNION $Select $From $Where GROUP BY rName";

     // We search recipients by other criteria
     $ArraySpecialResults = array(
                                  "rID" => array(),
                                  "rName" => array(),
                                  "rStateName" => array()
                                 );

     $sSearchedName = str_replace(array('%'), array(''), $ArrayParams["Name"]);

     // Search recipients in relation with grades of their children
     foreach($GLOBALS['CONF_GRADES'] as $g => $CurrentGrade)
     {
         if (stripos($CurrentGrade, $sSearchedName) !== FALSE)
         {
             $ArraySpecialResults['rID'][] = "G".$g;
             $ArraySpecialResults['rName'][] = $CurrentGrade;
             $ArraySpecialResults['rStateName'][] = $GLOBALS['LANG_CHILD_GRADE'];
         }
     }

     // Search recipients in relation with classroom of their children
     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
     if (isset($GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear]))
     {
         foreach($GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear] as $c => $CurrentClassroom)
         {
             if (stripos($CurrentClassroom, $sSearchedName) !== FALSE)
             {
                 $ArraySpecialResults['rID'][] = "C".$c;
                 $ArraySpecialResults['rName'][] = $CurrentClassroom;
                 $ArraySpecialResults['rStateName'][] = $GLOBALS['LANG_CHILD_CLASS'];
             }
         }
     }

     // Search recipients in relation with last school year of their children
     $ArraySchoolYears = array_keys($GLOBALS['CONF_SCHOOL_YEAR_START_DATES']);
     $sLastGrade = $GLOBALS['CONF_GRADES'][count($GLOBALS['CONF_GRADES']) - 1];
     foreach($ArraySchoolYears as $sy => $CurrentSY)
     {
         // School year YYYY-YYYY
         $sTmpSchoolYear = (((integer)$CurrentSY) - 1).'-'.$CurrentSY;

         if (stripos($sTmpSchoolYear, $sSearchedName) !== FALSE)
         {
             $ArraySpecialResults['rID'][] = "Y".$CurrentSY;
             $ArraySpecialResults['rName'][] = $sLastGrade.' '.$sTmpSchoolYear;
             $ArraySpecialResults['rStateName'][] = $GLOBALS['LANG_SCHOOL_YEAR'];
         }
     }

     unset($ArraySchoolYears);

     // We take into account the page and the number of recipients per page
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
     $DbResult = $DbConnection->query("$sSQLRequest $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         // Creation of the result array
         $ArrayRecords = array(
                               "rID" => array(),
                               "rName" => array(),
                               "rStateName" => array()
                              );

         if ($DbResult->numRows() != 0)
         {
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["rID"][] = $Record["rID"];
                 $ArrayRecords["rName"][] = $Record["rName"];
                 $ArrayRecords["rStateName"][] = $Record["rStateName"];
             }
         }

         // Take into account special resultats (as grades)
         if (!empty($ArraySpecialResults['rID']))
         {
             if (isset($ArrayRecords))
             {
                 $ArrayRecords["rID"] = array_merge($ArrayRecords["rID"], $ArraySpecialResults['rID']);
                 $ArrayRecords["rName"] = array_merge($ArrayRecords["rName"], $ArraySpecialResults['rName']);
                 $ArrayRecords["rStateName"] = array_merge($ArrayRecords["rStateName"], $ArraySpecialResults['rStateName']);
             }
             else
             {
                 $ArrayRecords = $ArraySpecialResults;
             }
         }

         // Return result
         return $ArrayRecords;
     }

     // ERROR
     return array();
 }
?>