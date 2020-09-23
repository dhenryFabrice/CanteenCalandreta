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
 * Common module : library of database functions used for the Children, HistoLevelsChildren and
 * Suspensions tables
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2012-01-23
 */


/**
 * Check if a child exists in the Children table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-23
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $ChildID              Integer      ID of the child searched [1..n]
 *
 * @return Boolean              TRUE if the child exists, FALSE otherwise
 */
 function isExistingChild($DbConnection, $ChildID)
 {
     $DbResult = $DbConnection->query("SELECT ChildID FROM Children WHERE ChildID = $ChildID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The child exists
             return TRUE;
         }
     }

     // The child doesn't exist
     return FALSE;
 }


/**
 * Add a child in the Children table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-23
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $ChildSchoolDate               Date         Creation date of the child (yyyy-mm-dd)
 * @param $ChildFirstname                String       Firstname of the child
 * @param $FamilyID                      Integer      ID of the family of the child [1..n]
 * @param $ChildGrade                    Integer      Grade of the child [0..n]
 * @param $ChildClass                    Integer      Class of thne child [0..n]
 * @param $ChildWithoutPork              Integer      1 for meal without pork [0..1]
 * @param $ChildDesactivationDate        Date         Desactivation date of the child (to "close" the child)
 *
 * @return Integer                       The primary key of the child [1..n], 0 otherwise
 */
 function dbAddChild($DbConnection, $ChildSchoolDate, $ChildFirstname, $FamilyID, $ChildGrade = 0, $ChildClass = 0, $ChildWithoutPork = 0, $ChildDesactivationDate = NULL)
 {
     if ((!empty($ChildFirstname)) && ($FamilyID > 0) && ($ChildGrade >= 0) && ($ChildClass >= 0) && ($ChildWithoutPork >= 0))
     {
         // Check if the child is a new child
         $DbResult = $DbConnection->query("SELECT ChildID FROM Children WHERE ChildFirstname = \"$ChildFirstname\" AND FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the ChildSchoolDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $ChildSchoolDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $ChildSchoolDate = ", ChildSchoolDate = \"$ChildSchoolDate\"";
                 }

                 // Check if the ChildDesactivationDate is valide
                 if (!empty($ChildDesactivationDate))
                 {
                     if (preg_match("[\d\d\d\d-\d\d-\d\d]", $ChildDesactivationDate) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $ChildDesactivationDate = ", ChildDesactivationDate = \"$ChildDesactivationDate\"";
                     }
                 }

                 // It's a new child
                 $id = getNewPrimaryKey($DbConnection, "Children", "ChildID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Children SET ChildID = $id, ChildFirstname = \"$ChildFirstname\",
                                                      FamilyID = $FamilyID, ChildGrade = $ChildGrade, ChildClass = $ChildClass,
                                                      ChildWithoutPork = $ChildWithoutPork $ChildSchoolDate
                                                      $ChildDesactivationDate");
                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The child already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['ChildID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing child in the Children table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-09-21 : taken into account ChildEmail
 *
 * @since 2012-01-23
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $ChildID                       Integer      ID of the child to update [1..n]
 * @param $ChildSchoolDate               Date         Creation date of the child (yyyy-mm-dd)
 * @param $ChildFirstname                String       Firstname of the child
 * @param $FamilyID                      Integer      ID of the family of the child [1..n]
 * @param $ChildGrade                    Integer      Grade of the child [0..n]
 * @param $ChildClass                    Integer      Class of thne child [0..n]
 * @param $ChildWithoutPork              Integer      1 for meal without pork [0..1]
 * @param $ChildDesactivationDate        Date         Desactivation date of the child (to "close" the child)
 * @param $ChildEmail                    String       E-mail of the child
 *
 * @return Integer                       The primary key of the child [1..n], 0 otherwise
 */
 function dbUpdateChild($DbConnection, $ChildID, $ChildSchoolDate, $ChildFirstname, $FamilyID, $ChildGrade = NULL, $ChildClass = NULL, $ChildWithoutPork = NULL, $ChildDesactivationDate = NULL, $ChildEmail = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($ChildID < 1) || (!isInteger($ChildID)))
     {
         // ERROR
         return 0;
     }

     // Check if the ChildSchoolDate is valide
     if (!is_null($ChildSchoolDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $ChildSchoolDate) == 0)
         {
             return 0;
         }
         else
         {
             // The ChildSchoolDate field will be updated
             $ArrayParamsUpdate[] = "ChildSchoolDate = \"$ChildSchoolDate\"";
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

     if (!is_Null($ChildFirstname))
     {
         if (empty($ChildFirstname))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ChildFirstname field will be updated
             $ArrayParamsUpdate[] = "ChildFirstname = \"$ChildFirstname\"";
         }
     }

     if (!is_Null($ChildGrade))
     {
         if (($ChildGrade < 0) || (!isInteger($ChildGrade)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ChildGrade field will be updated
             $ArrayParamsUpdate[] = "ChildGrade = $ChildGrade";
         }
     }

     if (!is_Null($ChildClass))
     {
         if (($ChildClass < 0) || (!isInteger($ChildClass)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ChildClass field will be updated
             $ArrayParamsUpdate[] = "ChildClass = $ChildClass";
         }
     }

     if (!is_Null($ChildWithoutPork))
     {
         if (($ChildWithoutPork < 0) || (!isInteger($ChildWithoutPork)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ChildWithoutPork field will be updated
             $ArrayParamsUpdate[] = "ChildWithoutPork = $ChildWithoutPork";
         }
     }

     if (!is_null($ChildDesactivationDate))
     {
         if (empty($ChildDesactivationDate))
         {
             // The ChildDesactivationDate field will be updated
             $ArrayParamsUpdate[] = "ChildDesactivationDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $ChildDesactivationDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The ChildDesactivationDate field will be updated
                 $ArrayParamsUpdate[] = "ChildDesactivationDate = \"$ChildDesactivationDate\"";
             }
         }
     }

     if (!is_Null($ChildEmail))
     {
         // The ChildEmail field will be updated
         $ArrayParamsUpdate[] = "ChildEmail = \"$ChildEmail\"";
     }

     // Here, the parameters are correct, we check if the child exists
     if (isExistingChild($DbConnection, $ChildID))
     {
         // We check if the child name is unique for a family
         $DbResult = $DbConnection->query("SELECT ChildID FROM Children WHERE ChildFirstname = \"$ChildFirstname\"
                                          AND FamilyID = $FamilyID AND ChildID <> $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The child exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Children SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE ChildID = $ChildID");
                     if (!DB::isError($DbResult))
                     {
                         // Child updated
                         return $ChildID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $ChildID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Check if a child is closed (desactivated), thanks to its ID
 *
 * @author Christophe Javouhey
 * @since 2012-01-23
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $ChildID                   Integer      ID of the child to check [1..n]
 *
 * @return Boolean                   TRUE if the child is closed, FALSE otherwise
 */
 function isChildClosed($DbConnection, $ChildID)
 {
     if (!empty($ChildID))
     {
         // we used only the activation to check if the child is closed
         $DbResult = $DbConnection->query("SELECT ChildID, ChildDesactivationDate FROM Children WHERE ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 if (!empty($Record['ChildDesactivationDate']))
                 {
                     // The child is closed (desactivated)
                     return TRUE;
                 }
                 else
                 {
                     // The child is opened (activated)
                     return FALSE;
                 }
             }
         }
     }

     // Error
     return TRUE;
 }


/**
 * Give the children of a family, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2015-10-06 : return the FamilyID field too
 *
 * @since 2012-01-23
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $FamilyID                  Integer      ID of the family for which we want the children [1..n]
 * @param $OrderBy                   String       To order the children
 *
 * @return Mixed array               All fields values of the children of the family if it exists,
 *                                   an empty array otherwise
 */
 function getFamilyChildren($DbConnection, $FamilyID, $OrderBy = 'ChildID')
 {
     if ($FamilyID > 0)
     {
         if (empty($OrderBy))
         {
             $OrderBy = 'ChildID';
         }

         // We get the children of the family
         $DbResult = $DbConnection->query("SELECT c.ChildID, c.ChildFirstname, f.FamilyID, f.FamilyLastname, c.ChildSchoolDate,
                                          c.ChildDesactivationDate, c.ChildGrade, ChildClass, ChildWithoutPork
                                          FROM Children c, Families f WHERE c.FamilyID = $FamilyID AND c.FamilyID = f.FamilyID
                                          ORDER BY $OrderBy");
         if (!DB::isError($DbResult))
         {
             // Creation of the result array
             $ArrayRecords = array(
                                  "ChildID" => array(),
                                  "ChildFirstname" => array(),
                                  "FamilyID" => array(),
                                  "FamilyLastname" => array(),
                                  "ChildSchoolDate" => array(),
                                  "ChildDesactivationDate" => array(),
                                  "ChildGrade" => array(),
                                  "ChildClass" => array(),
                                  "ChildWithoutPork" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["ChildSchoolDate"][] = $Record["ChildSchoolDate"];
                 $ArrayRecords["ChildDesactivationDate"][] = $Record["ChildDesactivationDate"];
                 $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
                 $ArrayRecords["ChildWithoutPork"][] = $Record["ChildWithoutPork"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get children filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.6
 *     - 2012-10-01 : allow to get activated children for a school year or now
 *     - 2013-03-11 : patch a bug about activated children (taken into account children arrived at school after
 *                    the beginning of the school year)
 *     - 2014-03-13 : the ChildID criteria can be an array
 *     - 2015-07-02 : if no result, the associative array is returned
 *     - 2016-09-02 : the FamilyID criteria can be an array
 *     - 2017-09-21 : taken into account ChildEmail and patch a bug to get children of a past school year (EndSchoolYear)
 *
 * @since 2012-01-29
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the children
 * @param $OrderBy                  String                 Criteria used to sort the children. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of children per page to return [1..n]
 *
 * @return Array of String                                 List of children filtered, an empty array otherwise
 */
 function dbSearchChild($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find children
     $Select = "SELECT c.ChildID, c.ChildFirstname, c.ChildGrade, c.ChildClass, c.ChildWithoutPork, c.ChildEmail,
                f.FamilyID, f.FamilyLastname, t.TownID, t.TownName, t.TownCode";
     $From = "FROM Children c, Families f, Towns t";
     $Where = " WHERE c.FamilyID = f.FamilyID AND f.TownID = t.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< ChildID field >>>
         if ((array_key_exists("ChildID", $ArrayParams)) && (!empty($ArrayParams["ChildID"])))
         {
             if (is_array($ArrayParams["ChildID"]))
             {
                 $Where .= " AND c.ChildID IN ".constructSQLINString($ArrayParams["ChildID"]);
             }
             else
             {
                 $Where .= " AND c.ChildID = ".$ArrayParams["ChildID"];
             }
         }

         // <<< Firstname field >>>
         if ((array_key_exists("ChildFirstname", $ArrayParams)) && (!empty($ArrayParams["ChildFirstname"])))
         {
             $Where .= " AND c.ChildFirstname LIKE \"".$ArrayParams["ChildFirstname"]."\"";
         }

         // <<< Grade >>>
         if ((array_key_exists("ChildGrade", $ArrayParams)) && (count($ArrayParams["ChildGrade"]) > 0))
         {
             $Where .= " AND c.ChildGrade IN ".constructSQLINString($ArrayParams["ChildGrade"]);
         }

         // <<< Class >>>
         if ((array_key_exists("ChildClass", $ArrayParams)) && (count($ArrayParams["ChildClass"]) > 0))
         {
             $Where .= " AND c.ChildClass IN ".constructSQLINString($ArrayParams["ChildClass"]);
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (!empty($ArrayParams["FamilyID"])))
         {
             if (is_array($ArrayParams["FamilyID"]))
             {
                 $Where .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
             }
             else
             {
                 $Where .= " AND f.FamilyID = ".$ArrayParams["FamilyID"];
             }
         }

         // <<< Lastname field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }

         // <<< TownID >>>
         if ((array_key_exists("TownID", $ArrayParams)) && (count($ArrayParams["TownID"]) > 0))
         {
             $Where .= " AND t.TownID IN ".constructSQLINString($ArrayParams["TownID"]);
         }

         // <<< TownName fields >>>
         if ((array_key_exists("TownName", $ArrayParams)) && (!empty($ArrayParams["TownName"])))
         {
             $Where .= " AND t.TownName LIKE \"".$ArrayParams["TownName"]."\"";
         }

         // <<< TownCode fields >>>
         if ((array_key_exists("TownCode", $ArrayParams)) && (!empty($ArrayParams["TownCode"])))
         {
             $Where .= " AND t.TownCode LIKE \"".$ArrayParams["TownCode"]."\"";
         }

         // <<< Option : get activated children >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
             {
                 // We search children activated for a given school year
                 $SchoolYearStartDate = $GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$ArrayParams["SchoolYear"][0]];
                 $SchoolYearEndDate = date('Y-m-05',
                                           strtotime($ArrayParams["SchoolYear"][0].'-'.$GLOBALS['CONF_SCHOOL_YEAR_LAST_MONTH'].'-01'));

                 $Where .= " AND (((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                          ." AND (c.ChildDesactivationDate IS NULL OR c.ChildDesactivationDate >= \"$SchoolYearEndDate\"))";
             }
             else
             {
                 // We search current activated children
                 $Where .= " AND c.ChildDesactivationDate IS NULL";
             }
         }

         // <<< Option in relation with the last school year >>>
         if ((array_key_exists("EndSchoolYear", $ArrayParams)) && (count($ArrayParams["EndSchoolYear"]) > 0))
         {
             // We search children with an end date during the given school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["EndSchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["EndSchoolYear"][0]);

             $Where .= " AND (((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                       ." AND (c.ChildDesactivationDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))";

         }
     }

     // We take into account the page and the number of children per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY ChildID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         // Creation of the result array
         $ArrayRecords = array(
                               "ChildID" => array(),
                               "ChildFirstname" => array(),
                               "ChildGrade" => array(),
                               "ChildClass" => array(),
                               "ChildWithoutPork" => array(),
                               "ChildEmail" => array(),
                               "FamilyID" => array(),
                               "FamilyLastname" => array(),
                               "TownID" => array(),
                               "TownName" => array(),
                               "TownCode" => array()
                               );

         if ($DbResult->numRows() != 0)
         {
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
                 $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
                 $ArrayRecords["ChildWithoutPork"][] = $Record["ChildWithoutPork"];
                 $ArrayRecords["ChildEmail"][] = $Record["ChildEmail"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["TownID"][] = $Record["TownID"];
                 $ArrayRecords["TownName"][] = $Record["TownName"];
                 $ArrayRecords["TownCode"][] = $Record["TownCode"];
             }
         }

         // Return result
         return $ArrayRecords;
     }

     // ERROR
     return array();
 }


/**
 * Get the number of children filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.5
 *     - 2012-10-01 : allow to get activated children for a school year or now
 *     - 2013-03-11 : patch a bug about activated children (taken into account children arrived at school after
 *                    the beginning of the school year)
 *     - 2014-03-13 : the ChildID criteria can be an array
 *     - 2016-09-02 : the FamilyID criteria can be an array
 *     - 2017-10-02 : patch a bug to get children of a past school year (EndSchoolYear)
 *
 * @since 2012-01-29
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the children
 *
 * @return Integer              Number of the children found, 0 otherwise
 */
 function getNbdbSearchChild($DbConnection, $ArrayParams)
 {
     // SQL request to find children
     $Select = "SELECT c.ChildID";
     $From = "FROM Children c, Families f, Towns t";
     $Where = " WHERE c.FamilyID = f.FamilyID AND f.TownID = t.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< ChildID field >>>
         if ((array_key_exists("ChildID", $ArrayParams)) && (!empty($ArrayParams["ChildID"])))
         {
             if (is_array($ArrayParams["ChildID"]))
             {
                 $Where .= " AND c.ChildID IN ".constructSQLINString($ArrayParams["ChildID"]);
             }
             else
             {
                 $Where .= " AND c.ChildID = ".$ArrayParams["ChildID"];
             }
         }

         // <<< Firstname field >>>
         if ((array_key_exists("ChildFirstname", $ArrayParams)) && (!empty($ArrayParams["ChildFirstname"])))
         {
             $Where .= " AND c.ChildFirstname LIKE \"".$ArrayParams["ChildFirstname"]."\"";
         }

         // <<< Grade >>>
         if ((array_key_exists("ChildGrade", $ArrayParams)) && (count($ArrayParams["ChildGrade"]) > 0))
         {
             $Where .= " AND c.ChildGrade IN ".constructSQLINString($ArrayParams["ChildGrade"]);
         }

         // <<< Class >>>
         if ((array_key_exists("ChildClass", $ArrayParams)) && (count($ArrayParams["ChildClass"]) > 0))
         {
             $Where .= " AND c.ChildClass IN ".constructSQLINString($ArrayParams["ChildClass"]);
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (!empty($ArrayParams["FamilyID"])))
         {
             if (is_array($ArrayParams["FamilyID"]))
             {
                 $Where .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
             }
             else
             {
                 $Where .= " AND f.FamilyID = ".$ArrayParams["FamilyID"];
             }
         }

         // <<< Lastname field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             $Where .= " AND f.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\"";
         }

         // <<< TownID >>>
         if ((array_key_exists("TownID", $ArrayParams)) && (count($ArrayParams["TownID"]) > 0))
         {
             $Where .= " AND t.TownID IN ".constructSQLINString($ArrayParams["TownID"]);
         }

         // <<< TownName fields >>>
         if ((array_key_exists("TownName", $ArrayParams)) && (!empty($ArrayParams["TownName"])))
         {
             $Where .= " AND t.TownName LIKE \"".$ArrayParams["TownName"]."\"";
         }

         // <<< TownCode fields >>>
         if ((array_key_exists("TownCode", $ArrayParams)) && (!empty($ArrayParams["TownCode"])))
         {
             $Where .= " AND t.TownCode LIKE \"".$ArrayParams["TownCode"]."\"";
         }

         // <<< Option : get activated children >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
             {
                 // We search children activaed for a given school year
                 $SchoolYearStartDate = $GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$ArrayParams["SchoolYear"][0]];
                 $SchoolYearEndDate = date('Y-m-05',
                                           strtotime($ArrayParams["SchoolYear"][0].'-'.$GLOBALS['CONF_SCHOOL_YEAR_LAST_MONTH'].'-01'));

                 $Where .= " AND (((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                          ." AND (c.ChildDesactivationDate IS NULL OR c.ChildDesactivationDate >= \"$SchoolYearEndDate\"))";
             }
             else
             {
                 // We search current activated children
                 $Where .= " AND c.ChildDesactivationDate IS NULL";
             }
         }

         // <<< Option in relation with the last school year >>>
         if ((array_key_exists("EndSchoolYear", $ArrayParams)) && (count($ArrayParams["EndSchoolYear"]) > 0))
         {
             // We search children with an end date during the given school year
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["EndSchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["EndSchoolYear"][0]);

             $Where .= " AND (((c.ChildSchoolDate <= \"$SchoolYearStartDate\") OR (c.ChildSchoolDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))"
                       ." AND (c.ChildDesactivationDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"))";

         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY ChildID $Having");

     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Delete a child, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-24
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $ChildID                   Integer      ID of the child to delete [1..n]
 *
 * @return Boolean                   TRUE if the child is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteChild($DbConnection, $ChildID)
 {
     // The parameters are correct?
     if ($ChildID > 0)
     {
         // We check if the child is linked to records in other tables
         //...
         $ArrayRelationsToCheck = array(
                                        array("Table" => "Suspensions", "Id" => "SuspensionID"),
                                        array("Table" => "CanteenRegistrations", "Id" => "CanteenRegistrationID"),
                                        array("Table" => "NurseryRegistrations", "Id" => "NurseryRegistrationID"),
                                       );

         $bContinue = TRUE;
         foreach($ArrayRelationsToCheck as $r => $Relation)
         {
             $DbResult = $DbConnection->query("SELECT ".$Relation["Id"]." FROM ".$Relation["Table"]." WHERE ChildID = $ChildID LIMIT 0, 1");
             if (!DB::isError($DbResult))
             {
                 if ($DbResult->numRows() > 0)
                 {
                     // Relation found, we can't delete the child
                     $bContinue = FALSE;
                     break;
                 }
             }
         }

         if ($bContinue)
         {
             // Delete the child in the table
             $DbResult = $DbConnection->query("DELETE FROM Children WHERE ChildID = $ChildID");
             if (!DB::isError($DbResult))
             {
                 // Child deleted
                 return TRUE;
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Check if a history entry of a child exists in the HistoLevelsChildren table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-18
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $HistoLevelChildID    Integer      ID of the history entry searched [1..n]
 *
 * @return Boolean              TRUE if the history entry of the child exists, FALSE otherwise
 */
 function isExistingHistoLevelChild($DbConnection, $HistoLevelChildID)
 {
     if ($HistoLevelChildID > 0)
     {
         $DbResult = $DbConnection->query("SELECT HistoLevelChildID FROM HistoLevelsChildren WHERE HistoLevelChildID = $HistoLevelChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 // The history entry of the child exists
                 return TRUE;
             }
         }
     }

     // The history entry of the child doesn't exist
     return FALSE;
 }


/**
 * Add an entry in the history of a child in the HistoLevelsChildren table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-18
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $ChildID                       Integer      ID of the child concerned by the history entry [1..n]
 * @param $HistoLevelChildYear           Integer      Year of the entry
 * @param $HistoLevelChildGrade          Integer      Grade of the child for the year [0..n]
 * @param $HistoLevelChildClass          Integer      Class of the child for the year [0..n]
 * @param $HistoLevelChildWithoutPork    Integer      1 for meal without pork for the year [0..1]
 *
 * @return Integer                       The primary key of the history entry for the child [1..n],
 *                                       0 otherwise
 */
 function dbAddHistoLevelChild($DbConnection, $ChildID, $HistoLevelChildYear, $HistoLevelChildGrade = 0, $HistoLevelChildClass = 0, $HistoLevelChildWithoutPork = 0)
 {
     if (($HistoLevelChildYear >= 1980) && ($ChildID > 0) && ($HistoLevelChildGrade >= 0) && ($HistoLevelChildClass >= 0) && ($HistoLevelChildWithoutPork >= 0))
     {
         // Check if the history entry for the child is a new entry
         $DbResult = $DbConnection->query("SELECT HistoLevelChildID FROM HistoLevelsChildren WHERE HistoLevelChildYear = $HistoLevelChildYear
                                          AND HistoLevelChildGrade = $HistoLevelChildGrade AND HistoLevelChildClass = $HistoLevelChildClass
                                          AND ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new history entry
                 $id = getNewPrimaryKey($DbConnection, "HistoLevelsChildren", "HistoLevelChildID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO HistoLevelsChildren SET HistoLevelChildID = $id, HistoLevelChildYear = $HistoLevelChildYear,
                                                      ChildID = $ChildID, HistoLevelChildGrade = $HistoLevelChildGrade, HistoLevelChildClass = $HistoLevelChildClass,
                                                      HistoLevelChildWithoutPork = $HistoLevelChildWithoutPork");
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
 * Update an existing history entry of a child in the HistoLevelsChildren table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-18
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $HistoLevelChildID             Integer      ID of the history entry of the child to update [1..n]
 * @param $ChildID                       Integer      ID of the child concerned by the history entry [1..n]
 * @param $HistoLevelChildYear           Integer      Year of the entry
 * @param $HistoLevelChildGrade          Integer      Grade of the child for the year [0..n]
 * @param $HistoLevelChildClass          Integer      Class of the child for the year [0..n]
 * @param $HistoLevelChildWithoutPork    Integer      1 for meal without pork for the year [0..1]
 *
 * @return Integer                       The primary key of the history entry of the child [1..n],
 *                                       0 otherwise
 */
 function dbUpdateHistoLevelChild($DbConnection, $HistoLevelChildID, $ChildID, $HistoLevelChildYear, $HistoLevelChildGrade = NULL, $HistoLevelChildClass = NULL, $HistoLevelChildWithoutPork = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($HistoLevelChildID < 1) || (!isInteger($HistoLevelChildID)))
     {
         // ERROR
         return 0;
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

     if (!is_Null($HistoLevelChildYear))
     {
         if (($HistoLevelChildYear < 1980) || (!isInteger($HistoLevelChildYear)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The HistoLevelChildYear field will be updated
             $ArrayParamsUpdate[] = "HistoLevelChildYear = $HistoLevelChildYear";
         }
     }

     if (!is_Null($HistoLevelChildGrade))
     {
         if (($HistoLevelChildGrade < 0) || (!isInteger($HistoLevelChildGrade)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The HistoLevelChildGrade field will be updated
             $ArrayParamsUpdate[] = "HistoLevelChildGrade = $HistoLevelChildGrade";
         }
     }

     if (!is_Null($HistoLevelChildClass))
     {
         if (($HistoLevelChildClass < 0) || (!isInteger($HistoLevelChildClass)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The HistoLevelChildClass field will be updated
             $ArrayParamsUpdate[] = "HistoLevelChildClass = $HistoLevelChildClass";
         }
     }

     if (!is_Null($HistoLevelChildWithoutPork))
     {
         if (($HistoLevelChildWithoutPork < 0) || (!isInteger($HistoLevelChildWithoutPork)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The HistoLevelChildWithoutPork field will be updated
             $ArrayParamsUpdate[] = "HistoLevelChildWithoutPork = $HistoLevelChildWithoutPork";
         }
     }

     // Here, the parameters are correct, we check if the history entry of the child exists
     if (isExistingHistoLevelChild($DbConnection, $HistoLevelChildID))
     {
         // We check if the history entry is unique for the child
         $DbResult = $DbConnection->query("SELECT HistoLevelChildID FROM HistoLevelsChildren WHERE HistoLevelChildYear = $HistoLevelChildYear
                                          AND HistoLevelChildGrade = $HistoLevelChildGrade AND HistoLevelChildClass = $HistoLevelChildClass
                                          AND HistoLevelChildWithoutPork = $HistoLevelChildWithoutPork AND ChildID = $ChildID
                                          AND HistoLevelChildID <> $HistoLevelChildID");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The history entry of the child exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE HistoLevelsChildren SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE HistoLevelChildID = $HistoLevelChildID");
                     if (!DB::isError($DbResult))
                     {
                         // History entry updated
                         return $HistoLevelChildID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $HistoLevelChildID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the history of a child, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-18
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $ChildID                   Integer      ID of the child for which we want the history [1..n]
 * @param $OrderBy                   String       To order the history
 *
 * @return Mixed array               All fields values of the history of the child if it exists,
 *                                   an empty array otherwise
 */
 function getHistoLevelsChild($DbConnection, $ChildID, $OrderBy = 'HistoLevelChildYear DESC')
 {
     if ($ChildID > 0)
     {
         if (empty($OrderBy))
         {
             $OrderBy = 'HistoLevelChildYear DESC';
         }

         // We get the history of the child
         $DbResult = $DbConnection->query("SELECT HistoLevelChildID, HistoLevelChildYear, HistoLevelChildGrade, HistoLevelChildClass,
                                          HistoLevelChildWithoutPork FROM HistoLevelsChildren WHERE ChildID = $ChildID ORDER BY $OrderBy");

         if (!DB::isError($DbResult))
         {
             // Creation of the result array
             $ArrayRecords = array(
                                  "HistoLevelChildID" => array(),
                                  "HistoLevelChildYear" => array(),
                                  "HistoLevelChildGrade" => array(),
                                  "HistoLevelChildClass" => array(),
                                  "HistoLevelChildWithoutPork" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["HistoLevelChildID"][] = $Record["HistoLevelChildID"];
                 $ArrayRecords["HistoLevelChildYear"][] = $Record["HistoLevelChildYear"];
                 $ArrayRecords["HistoLevelChildGrade"][] = $Record["HistoLevelChildGrade"];
                 $ArrayRecords["HistoLevelChildClass"][] = $Record["HistoLevelChildClass"];
                 $ArrayRecords["HistoLevelChildWithoutPork"][] = $Record["HistoLevelChildWithoutPork"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Check if a suspension of a child exists in the Suspensions table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-20
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $SuspensionID         Integer      ID of the suspension searched [1..n]
 *
 * @return Boolean              TRUE if the suspension of the child exists, FALSE otherwise
 */
 function isExistingSuspension($DbConnection, $SuspensionID)
 {
     if ($SuspensionID > 0)
     {
         $DbResult = $DbConnection->query("SELECT SuspensionID FROM Suspensions WHERE SuspensionID = $SuspensionID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 // The suspension of the child exists
                 return TRUE;
             }
         }
     }

     // The suspension of the child doesn't exist
     return FALSE;
 }


/**
 * Add a suspension for a child in the Suspensions table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-21
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $ChildID                       Integer      ID of the child concerned by the suspension [1..n]
 * @param $SuspensionStartDate           Date         Start date of the suspension (yyyy-mm-dd)
 * @param $SuspensionEndDate             Date         End date of the suspension (yyyy-mm-dd)
 * @param $SuspensionReason              String       Reason of the suspension
 *
 * @return Integer                       The primary key of the hsitory entry for the child [1..n],
 *                                       0 otherwise
 */
 function dbAddSuspension($DbConnection, $ChildID, $SuspensionStartDate, $SuspensionEndDate = NULL, $SuspensionReason = NULL)
 {
     if (($ChildID > 0) && (!empty($SuspensionStartDate)))
     {
         // Check if the suspension for the child is a new suspension
         $DbResult = $DbConnection->query("SELECT SuspensionID FROM Suspensions WHERE SuspensionStartDate = \"$SuspensionStartDate\"
                                          AND ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the SuspensionStartDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $SuspensionStartDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $SuspensionStartDate = ", SuspensionStartDate = \"$SuspensionStartDate\"";
                 }

                 // Check if the SuspensionEndDate is valide
                 if (!empty($SuspensionEndDate))
                 {
                     if (preg_match("[\d\d\d\d-\d\d-\d\d]", $SuspensionEndDate) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $SuspensionEndDate = ", SuspensionEndDate = \"$SuspensionEndDate\"";
                     }
                 }

                 // It's a new suspension
                 $id = getNewPrimaryKey($DbConnection, "Suspensions", "SuspensionID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Suspensions SET SuspensionID = $id, ChildID = $ChildID,
                                                      SuspensionReason = \"$SuspensionReason\" $SuspensionStartDate
                                                      $SuspensionEndDate");
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
 * Update an existing suspension of a child in the Suspensions table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-21
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $SuspensionID                  Integer      ID of the suspension of the child to update [1..n]
 * @param $ChildID                       Integer      ID of the child concerned by the suspension [1..n]
 * @param $SuspensionStartDate           Date         Start date of the suspension (yyyy-mm-dd)
 * @param $SuspensionEndDate             Date         End date of the suspension (yyyy-mm-dd)
 * @param $SuspensionReason              String       Reason of the suspension
 *
 * @return Integer                       The primary key of the suspensoin of the child [1..n],
 *                                       0 otherwise
 */
 function dbUpdateSuspension($DbConnection, $SuspensionID, $ChildID, $SuspensionStartDate, $SuspensionEndDate = NULL, $SuspensionReason = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($SuspensionID < 1) || (!isInteger($SuspensionID)))
     {
         // ERROR
         return 0;
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

     // Check if the SuspensionStartDate is valide
     if (!is_null($SuspensionStartDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $SuspensionStartDate) == 0)
         {
             return 0;
         }
         else
         {
             // The SuspensionStartDate field will be updated
             $ArrayParamsUpdate[] = "SuspensionStartDate = \"$SuspensionStartDate\"";
         }
     }

     if (!is_null($SuspensionEndDate))
     {
         if (empty($SuspensionEndDate))
         {
             // The SuspensionEndDate field will be updated
             $ArrayParamsUpdate[] = "SuspensionEndDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $SuspensionEndDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The SuspensionEndDate field will be updated
                 $ArrayParamsUpdate[] = "SuspensionEndDate = \"$SuspensionEndDate\"";
             }
         }
     }

     if (!is_Null($SuspensionReason))
     {
         // The SuspensionReason field will be updated
         $ArrayParamsUpdate[] = "SuspensionReason = \"$SuspensionReason\"";
     }

     // Here, the parameters are correct, we check if the suspension of the child exists
     if (isExistingSuspension($DbConnection, $SuspensionID))
     {
         // We check if the suspension is unique for the child
         $DbResult = $DbConnection->query("SELECT SuspensionID FROM Suspensions WHERE SuspensionStartDate = \"$SuspensionStartDate\"
                                          AND ChildID = $ChildID AND SuspensionID <> $SuspensionID");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The suspension of the child exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Suspensions SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE SuspensionID = $SuspensionID");
                     if (!DB::isError($DbResult))
                     {
                         // Suspension updated
                         return $SuspensionID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $SuspensionID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the suspensions of a child, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-09-01 : allow NULL ChildID or ChildID can be an array, taken into account FamilyID, ChildGrade
 *                    and ChildClass fields in serach parameters, add more fields in the result (ChildID, FamilyID,...)
 *
 * @since 2012-02-20
 *
 * @param $DbConnection              DB object        Object of the opened database connection
 * @param $ChildID                   Integer          ID of the child for which we want the suspensions [1..n]. Can be NULL
 * @param $bOpened                   Boolean          TRUE to get only an opened suspension
 * @param $OrderBy                   String           To order the suspensions
 * @param $ArrayParams               Mixed Array      Other criterion to filter suspensions
 *
 * @return Mixed array               All fields values of the suspensions of the child if it exists,
 *                                   an empty array otherwise
 */
 function getSuspensionsChild($DbConnection, $ChildID = NULL, $bOpened = FALSE, $OrderBy = 'SuspensionStartDate DESC', $ArrayParams = array())
 {
     if (empty($OrderBy))
     {
         $OrderBy = 'SuspensionStartDate DESC';
     }

     // Conditions
     $Conditions = '';
     if (!is_null($ChildID))
     {
         if (is_array($ChildID))
         {
             $Conditions .= " AND s.ChildID IN ".constructSQLINString($ChildID);
         }
         elseif ($ChildID > 0)
         {
             $Conditions .= " AND s.ChildID = $ChildID";
         }
     }

     if ($bOpened)
     {
         $CurrentDate = date('Y-m-d');
         $Conditions .= " AND ((s.SuspensionEndDate IS NULL) OR (\"$CurrentDate\" BETWEEN s.SuspensionStartDate AND s.SuspensionEndDate))";
     }

     if (!empty($ArrayParams))
     {
         // <<< OpenedInPast option >>>
         if (isset($ArrayParams['OpenedInPast']))
         {
             if ($ArrayParams['OpenedInPast'])
             {
                 if ((isset($ArrayParams['StartDate'])) && (!empty($ArrayParams['StartDate'])))
                 {
                     $Conditions .= " AND s.SuspensionStartDate <= \"".$ArrayParams['StartDate']."\"";
                 }

                 if ((isset($ArrayParams['EndDate'])) && (!empty($ArrayParams['EndDate'])))
                 {
                     $Conditions .= " AND ((s.SuspensionEndDate >= \"".$ArrayParams['EndDate']."\") OR (s.SuspensionEndDate IS NULL))";
                 }
             }
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (!empty($ArrayParams["FamilyID"])))
         {
             if (is_array($ArrayParams["FamilyID"]))
             {
                 $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams["FamilyID"]);
             }
             else
             {
                 $Conditions .= " AND f.FamilyID = ".$ArrayParams["FamilyID"];
             }
         }

         // <<< Grade >>>
         if ((array_key_exists("ChildGrade", $ArrayParams)) && (count($ArrayParams["ChildGrade"]) > 0))
         {
             $Conditions .= " AND c.ChildGrade IN ".constructSQLINString($ArrayParams["ChildGrade"]);
         }

         // <<< Class >>>
         if ((array_key_exists("ChildClass", $ArrayParams)) && (count($ArrayParams["ChildClass"]) > 0))
         {
             $Conditions .= " AND c.ChildClass IN ".constructSQLINString($ArrayParams["ChildClass"]);
         }
     }

     // We get the suspensions of the child
     $DbResult = $DbConnection->query("SELECT s.SuspensionID, s.SuspensionStartDate, s.SuspensionEndDate, s.SuspensionReason,
                                       c.ChildID, c.ChildDesactivationDate, c.ChildGrade, c.ChildClass, c.ChildWithoutPork,
                                       f.FamilyID
                                       FROM Suspensions s, Children c INNER JOIN Families f ON (c.FamilyID = f.FamilyID)
                                       WHERE s.ChildID = c.ChildID $Conditions ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         // Creation of the result array
         $ArrayRecords = array(
                               "SuspensionID" => array(),
                               "SuspensionStartDate" => array(),
                               "SuspensionEndDate" => array(),
                               "ChildID" => array(),
                               "ChildDesactivationDate" => array(),
                               "ChildGrade" => array(),
                               "ChildClass" => array(),
                               "ChildWithoutPork" => array()
                              );

         while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
         {
             $ArrayRecords["SuspensionID"][] = $Record["SuspensionID"];
             $ArrayRecords["SuspensionStartDate"][] = $Record["SuspensionStartDate"];
             $ArrayRecords["SuspensionEndDate"][] = $Record["SuspensionEndDate"];
             $ArrayRecords["SuspensionReason"][] = $Record["SuspensionReason"];
             $ArrayRecords["ChildID"][] = $Record["ChildID"];
             $ArrayRecords["ChildDesactivationDate"][] = $Record["ChildDesactivationDate"];
             $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
             $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
             $ArrayRecords["ChildWithoutPork"][] = $Record["ChildWithoutPork"];
             $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
         }

         // Return result
         return $ArrayRecords;
     }

     // ERROR
     return array();
 }
?>