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
 * Common module : library of database functions used for the CanteenRegistrations and
 * MoreMeals tables
 *
 * @author Christophe Javouhey
 * @version 2.6
 * @since 2012-01-28
 */


/**
 * Check if a canteen registration exists in the CanteenRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-23
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $CanteenRegistrationID    Integer      ID of the canteen registration searched [1..n]
 *
 * @return Boolean                  TRUE if the canteen registration exists, FALSE otherwise
 */
 function isExistingCanteenRegistration($DbConnection, $CanteenRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT CanteenRegistrationID FROM CanteenRegistrations
                                      WHERE CanteenRegistrationID = $CanteenRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The canteen registration exists
             return TRUE;
         }
     }

     // The canteen registration doesn't exist
     return FALSE;
 }


/**
 * Add a canteen registration for a day and for a child in the CanteenRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2012-07-10 : taken into account CanteenRegistrationChildGrade and CanteenRegistrationChildClass fields
 *
 * @since 2012-01-28
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $CanteenRegistrationDate           Date         Creation date of the canteen registration (yyyy-mm-dd)
 * @param $CanteenRegistrationForDate        Date         Date for which the canteen registration is (yyyy-mm-dd)
 * @param $ChildID                           Integer      ID of the child of the canteen registration [1..n]
 * @param $CanteenRegistrationChildGrade     Integer      Grade of the child [0..n]
 * @param $CanteenRegistrationChildClass     Integer      Class of the child [0..n]
 * @param $CanteenRegistrationWithoutPork    Integer      0 = meal with pork, 1 = meal without pork, 2 = packed lunch [0..2]
 * @param $CanteenRegistrationValided        Integer      1 if the canteen registration is valided [0..1]
 * @param $CanteenRegistrationAdminDate      Date         Date if the canteen registration was administrated (yyyy-mm-dd)
 *
 * @return Integer                           The primary key of the canteen registration [1..n], 0 otherwise
 */
 function dbAddCanteenRegistration($DbConnection, $CanteenRegistrationDate, $CanteenRegistrationForDate, $ChildID, $CanteenRegistrationChildGrade = 0, $CanteenRegistrationChildClass = 0, $CanteenRegistrationWithoutPork = 0, $CanteenRegistrationValided = 0, $CanteenRegistrationAdminDate = NULL)
 {
     if (($ChildID > 0) && ($CanteenRegistrationChildGrade >= 0) && ($CanteenRegistrationChildClass >= 0) && ($CanteenRegistrationWithoutPork >= 0) && ($CanteenRegistrationValided >= 0))
     {
         // Check if the canteen registration is a new canteen registration for a child and a day
         $DbResult = $DbConnection->query("SELECT CanteenRegistrationID FROM CanteenRegistrations
                                           WHERE CanteenRegistrationForDate = \"$CanteenRegistrationForDate\" AND ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the CanteenRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $CanteenRegistrationDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $CanteenRegistrationDate = ", CanteenRegistrationDate = \"$CanteenRegistrationDate\"";
                 }

                 // Check if the CanteenRegistrationForDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $CanteenRegistrationForDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $CanteenRegistrationForDate = ", CanteenRegistrationForDate = \"$CanteenRegistrationForDate\"";
                 }

                 // Check if the CanteenRegistrationAdminDate is valide
                 if (!empty($CanteenRegistrationAdminDate))
                 {
                     if (preg_match("[\d\d\d\d-\d\d-\d\d]", $CanteenRegistrationAdminDate) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $CanteenRegistrationAdminDate = ", CanteenRegistrationAdminDate = \"$CanteenRegistrationAdminDate\"";
                     }
                 }

                 // It's a new canteen registration
                 $id = getNewPrimaryKey($DbConnection, "CanteenRegistrations", "CanteenRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO CanteenRegistrations SET CanteenRegistrationID = $id, ChildID = $ChildID,
                                                      CanteenRegistrationWithoutPork = $CanteenRegistrationWithoutPork,
                                                      CanteenRegistrationChildGrade = $CanteenRegistrationChildGrade,
                                                      CanteenRegistrationChildClass = $CanteenRegistrationChildClass,
                                                      CanteenRegistrationValided = $CanteenRegistrationValided $CanteenRegistrationDate
                                                      $CanteenRegistrationForDate $CanteenRegistrationAdminDate");
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
 * Update an existing canteen registration for a child and a day in the CanteenRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2012-07-10 : taken into account CanteenRegistrationChildGrade and CanteenRegistrationChildClass fields
 *
 * @since 2012-01-28
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $CanteenRegistrationID             Integer      ID of the canteen registration to update [1..n]
 * @param $CanteenRegistrationDate           Date         Creation date of the canteen registration (yyyy-mm-dd)
 * @param $CanteenRegistrationForDate        Date         Date for which the canteen registration is (yyyy-mm-dd)
 * @param $ChildID                           Integer      ID of the child of the canteen registration [1..n]
 * @param $CanteenRegistrationChildGrade     Integer      Grade of the child [0..n]
 * @param $CanteenRegistrationChildClass     Integer      Class of the child [0..n]
 * @param $CanteenRegistrationWithoutPork    Integer      0 = meal with pork, 1 = meal without pork, 2 = packed lunch [0..2]
 * @param $CanteenRegistrationValided        Integer      1 if the canteen registration is valided [0..1]
 * @param $CanteenRegistrationAdminDate      Date         Date if the canteen registration was administrated (yyyy-mm-dd)
 *
 * @return Integer                           The primary key of the canteen registration [1..n], 0 otherwise
 */
 function dbUpdateCanteenRegistration($DbConnection, $CanteenRegistrationID, $CanteenRegistrationDate, $CanteenRegistrationForDate, $ChildID, $CanteenRegistrationChildGrade = NULL, $CanteenRegistrationChildClass = NULL, $CanteenRegistrationWithoutPork = NULL, $CanteenRegistrationValided = NULL, $CanteenRegistrationAdminDate = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($CanteenRegistrationID < 1) || (!isInteger($CanteenRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the CanteenRegistrationDate is valide
     if (!is_null($CanteenRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $CanteenRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The CanteenRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationDate = \"$CanteenRegistrationDate\"";
         }
     }

     // Check if the CanteenRegistrationForDate is valide
     if (!is_null($CanteenRegistrationForDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $CanteenRegistrationForDate) == 0)
         {
             return 0;
         }
         else
         {
             // The CanteenRegistrationForDate field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationForDate = \"$CanteenRegistrationForDate\"";
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

     if (!is_Null($CanteenRegistrationChildGrade))
     {
         if (($CanteenRegistrationChildGrade < 0) || (!isInteger($CanteenRegistrationChildGrade)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The CanteenRegistrationChildGrade field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationChildGrade = $CanteenRegistrationChildGrade";
         }
     }

     if (!is_Null($CanteenRegistrationChildClass))
     {
         if (($CanteenRegistrationChildClass < 0) || (!isInteger($CanteenRegistrationChildClass)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The CanteenRegistrationChildClass field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationChildClass = $CanteenRegistrationChildClass";
         }
     }

     if (!is_Null($CanteenRegistrationWithoutPork))
     {
         if (($CanteenRegistrationWithoutPork < 0) || (!isInteger($CanteenRegistrationWithoutPork)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The CanteenRegistrationWithoutPork field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationWithoutPork = $CanteenRegistrationWithoutPork";
         }
     }

     if (!is_Null($CanteenRegistrationValided))
     {
         if (($CanteenRegistrationValided < 0) || (!isInteger($CanteenRegistrationValided)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The CanteenRegistrationValided field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationValided = $CanteenRegistrationValided";
         }
     }

     if (!is_null($CanteenRegistrationAdminDate))
     {
         if (empty($CanteenRegistrationAdminDate))
         {
             // The CanteenRegistrationAdminDate field will be updated
             $ArrayParamsUpdate[] = "CanteenRegistrationAdminDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $CanteenRegistrationAdminDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The CanteenRegistrationAdminDate field will be updated
                 $ArrayParamsUpdate[] = "CanteenRegistrationAdminDate = \"$CanteenRegistrationAdminDate\"";
             }
         }
     }

     // Here, the parameters are correct, we check if the canteen registration exists
     if (isExistingCanteenRegistration($DbConnection, $CanteenRegistrationID))
     {
         // We check if the canteen registration is unique for a child and a day
         $DbResult = $DbConnection->query("SELECT CanteenRegistrationID FROM CanteenRegistrations
                                          WHERE CanteenRegistrationForDate = \"$CanteenRegistrationForDate\" AND ChildID = $ChildID
                                          AND CanteenRegistrationID <> $CanteenRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The canteen registration exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE CanteenRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE CanteenRegistrationID = $CanteenRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Canteen registration updated
                         return $CanteenRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $CanteenRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the children who can be listed in the canteen planning for a period (between 2 dates)
 * and with canteen registrations valided or not and for only activated children or all.
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2012-07-10 : patch a bug about new children will appear in old plannings
 *     - 2013-02-11 : patch a bug about desactivated children will not appear in old plannings
 *     - 2014-02-06 : patch pb about search modes on "CanteenRegistrationForDate" field
 *
 * @since 2012-01-28
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order children
 * @param $bActivated           Boolean              Get only activated children
 * @param $bValided             Boolean              Get only the valided canteen registrations
 * @param $Mode                 Enum                 Mode to find children
 *
 * @return mixed Array                               The children who can be listed in the canteen planning,
 *                                                   an empty array otherwise
 */
 function getChildrenListForCanteenPlanning($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'ChildClass', $bActivated = FALSE, $bValided = FALSE, $Mode = PLANNING_BETWEEN_DATES)
 {
     if (empty($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Conditions1 = "";
     if ($bActivated)
     {
         // We get only the (now) activated children
         $Conditions1 .= " AND c.ChildDesactivationDate IS NULL";
     }
     else
     {
         // We get children activated at this period
         $Conditions1 .= " AND (((c.ChildSchoolDate <= \"$StartDate\") OR (c.ChildSchoolDate BETWEEN \"$StartDate\" AND \"$EndDate\"))
                           AND ((c.ChildDesactivationDate IS NULL) OR ((c.ChildDesactivationDate >= \"$StartDate\") OR (c.ChildDesactivationDate >= \"$EndDate\"))))";
     }

     $Select = "SELECT c.ChildID, c.ChildFirstname, IFNULL(CrTmp.CanteenRegistrationChildGrade, c.ChildGrade) AS ChildGrade,
                IFNULL(CrTmp.CanteenRegistrationChildClass, c.ChildClass) AS ChildClass,
                IFNULL(CrTmp.CanteenRegistrationWithoutPork, c.ChildWithoutPork) AS ChildWithoutPork, f.FamilyID, f.FamilyLastname";
     $From = "FROM Families f, Children c";
     $Where = "WHERE f.FamilyID = c.FamilyID $Conditions1";

     $Where2 = "WHERE";
     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where2 .= " \"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') AND \""
                        .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where2 .= " ((\"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')) OR (\""
                        .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where2 .= " (DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $Conditions2 = "";
     if ($bValided)
     {
         // We get only the valided planning entries
         $Conditions2 .= " AND cr.CanteenRegistrationValided > 0";
     }

     $From .= " LEFT JOIN (SELECT cr.ChildID, cr.CanteenRegistrationChildGrade, cr.CanteenRegistrationChildClass,
               cr.CanteenRegistrationWithoutPork FROM CanteenRegistrations cr $Where2 $Conditions2 GROUP BY cr.ChildID) CrTmp
               ON (c.ChildID = CrTmp.ChildID)";

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY ChildID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "ChildID" => array(),
                                   "ChildFirstname" => array(),
                                   "ChildGrade" => array(),
                                   "ChildClass" => array(),
                                   "ChildWithoutPork" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
                 $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
                 $ArrayRecords["ChildWithoutPork"][] = $Record["ChildWithoutPork"];
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
 * Give the canteen registrations between 2 dates and valided or not for a given child or all.
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2012-07-10 : taken into account CanteenRegistrationChildGrade and CanteenRegistrationChildClass fields
 *     - 2013-11-29 : $ChildID can be an array of integers
 *     - 2014-02-06 : patch pb about search modes on "CanteenRegistrationForDate" field
 *
 * @since 2012-01-28
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order canteen registrations
 * @param $ChildID              Integer              ID of the child for which we want canteen registrations [1..n]
 * @param $bValided             Boolean              Get only the valided canteen registrations
 * @param $Mode                 Enum                 Mode to find canteen registrations
 * @param $ArrayParams          Mixed Array          Other criterion to filter canteen registrations
 *
 * @return mixed Array                               The canteen registrations and between the 2 dates
 *                                                   for a given child or all, an empty array otherwise
 */
 function getCanteenRegistrations($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'CanteenRegistrationForDate', $ChildID = NULL, $bValided = FALSE, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
 {
     if (empty($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Conditions = "";
     if (!empty($ChildID))
     {
         if (is_array($ChildID))
         {
              $Conditions .= " AND c.ChildID IN ".constructSQLINString($ChildID);
         }
         elseif (($ChildID >= 1) && (isInteger($ChildID)))
         {
             $Conditions .= " AND c.ChildID = $ChildID";
         }
     }

     if ($bValided)
     {
         // We get only the valided planning entries
         $Conditions .= " AND cr.CanteenRegistrationValided > 0";
     }

     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['ChildClass'])) && (count($ArrayParams['ChildClass']) > 0))
         {
             $Conditions .= " AND cr.CanteenRegistrationChildClass IN ".constructSQLINString($ArrayParams['ChildClass']);
         }

         if ((isset($ArrayParams['ChildGrade'])) && (count($ArrayParams['ChildGrade']) > 0))
         {
             $Conditions .= " AND cr.CanteenRegistrationChildGrade IN ".constructSQLINString($ArrayParams['ChildGrade']);
         }

         if ((isset($ArrayParams['CanteenRegistrationWithoutPork'])) && (count($ArrayParams['CanteenRegistrationWithoutPork']) > 0))
         {
             $Conditions .= " AND cr.CanteenRegistrationWithoutPork IN ".constructSQLINString($ArrayParams['CanteenRegistrationWithoutPork']);
         }

         if ((isset($ArrayParams['FamilyID'])) && (count($ArrayParams['FamilyID']) > 0))
         {
             $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams['FamilyID']);
         }
     }

     $Select = "SELECT cr.CanteenRegistrationID, cr.CanteenRegistrationDate, cr.CanteenRegistrationForDate, cr.CanteenRegistrationAdminDate,
               cr.CanteenRegistrationWithoutPork, cr.CanteenRegistrationValided, cr.CanteenRegistrationChildGrade,
               cr.CanteenRegistrationChildClass, c.ChildID, c.ChildFirstname, f.FamilyID, f.FamilyLastname";

     $From = "FROM CanteenRegistrations cr, Children c, Families f";

     $Where = "WHERE cr.ChildID = c.ChildID AND c.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') AND \""
                       .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')) OR (\""
                       .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND (DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY cr.CanteenRegistrationID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "CanteenRegistrationID" => array(),
                                   "CanteenRegistrationDate" => array(),
                                   "CanteenRegistrationForDate" => array(),
                                   "CanteenRegistrationAdminDate" => array(),
                                   "CanteenRegistrationWithoutPork" => array(),
                                   "CanteenRegistrationValided" => array(),
                                   "ChildID" => array(),
                                   "ChildFirstname" => array(),
                                   "ChildGrade" => array(),
                                   "ChildClass" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["CanteenRegistrationID"][] = $Record["CanteenRegistrationID"];
                 $ArrayRecords["CanteenRegistrationDate"][] = $Record["CanteenRegistrationDate"];
                 $ArrayRecords["CanteenRegistrationForDate"][] = $Record["CanteenRegistrationForDate"];
                 $ArrayRecords["CanteenRegistrationAdminDate"][] = $Record["CanteenRegistrationAdminDate"];
                 $ArrayRecords["CanteenRegistrationWithoutPork"][] = $Record["CanteenRegistrationWithoutPork"];
                 $ArrayRecords["CanteenRegistrationValided"][] = $Record["CanteenRegistrationValided"];
                 $ArrayRecords["ChildGrade"][] = $Record["CanteenRegistrationChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["CanteenRegistrationChildClass"];
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
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
 * Give the number canteen registrations between 2 dates and valided or not for a given child or all,
 * group by date, month, year or not.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-02-09
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $GroupBy              String               To group number of canteen registrations by date, month, year...
 * @param $ChildID              Integer              ID of the child for which we want canteen registrations [1..n]
 * @param $bValided             Boolean              Get only the valided canteen registrations
 * @param $Mode                 Enum                 Mode to find canteen registrations
 * @param $ArrayParams          Mixed Array          Other criterion to filter canteen registrations
 *
 * @return mixed Array                               The canteen registrations and between the 2 dates
 *                                                   for a given child or all, an empty array otherwise
 */
 function getNbCanteenRegistrations($DbConnection, $StartDate, $EndDate = NULL, $GroupBy = array(), $ChildID = NULL, $bValided = FALSE, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
 {
     if (empty($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Conditions = "";
     $HavingConditions = "";
     if (!empty($ChildID))
     {
         if (is_array($ChildID))
         {
              $Conditions .= " AND c.ChildID IN ".constructSQLINString($ChildID);
         }
         elseif (($ChildID >= 1) && (isInteger($ChildID)))
         {
             $Conditions .= " AND c.ChildID = $ChildID";
         }
     }

     if ($bValided)
     {
         // We get only the valided planning entries
         $Conditions .= " AND cr.CanteenRegistrationValided > 0";
     }

     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['ChildClass'])) && (count($ArrayParams['ChildClass']) > 0))
         {
             $Conditions .= " AND cr.CanteenRegistrationChildClass IN ".constructSQLINString($ArrayParams['ChildClass']);
         }

         if ((isset($ArrayParams['ChildGrade'])) && (count($ArrayParams['ChildGrade']) > 0))
         {
             $Conditions .= " AND cr.CanteenRegistrationChildGrade IN ".constructSQLINString($ArrayParams['ChildGrade']);
         }

         if ((isset($ArrayParams['CanteenRegistrationWithoutPork'])) && (count($ArrayParams['CanteenRegistrationWithoutPork']) > 0))
         {
             $Conditions .= " AND cr.CanteenRegistrationWithoutPork IN ".constructSQLINString($ArrayParams['CanteenRegistrationWithoutPork']);
         }

         if ((isset($ArrayParams['NbCanteenregistrations'])) && (count($ArrayParams['NbCanteenregistrations']) == 2))
         {
             if (empty($HavingConditions))
             {
                 $HavingConditions = "HAVING ";
             }
             else
             {
                 $HavingConditions .= ", ";
             }

             $HavingConditions .= "NB ".$ArrayParams['NbCanteenregistrations'][0].' '.$ArrayParams['NbCanteenregistrations'][1];
         }

         if ((isset($ArrayParams['FamilyID'])) && (count($ArrayParams['FamilyID']) > 0))
         {
             $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams['FamilyID']);
         }
     }

     // Build the GROUP BY content
     $ArraySelectGroupBy = array();
     $ArrayGroupByCondition = array();
     if (!empty($GroupBy))
     {
         foreach($GroupBy as $g => $Term)
         {
             switch($Term)
             {
                 case GROUP_BY_FOR_DATE_BY_DAY:
                     $ArraySelectGroupBy[] = "DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m-%d') AS ForDayDate";
                     $ArrayGroupByCondition[] = "ForDayDate";
                     break;

                 case GROUP_BY_FOR_DATE_BY_YEARMONTH:
                     $ArraySelectGroupBy[] = "DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m') AS ForYearMonth";
                     $ArrayGroupByCondition[] = "ForYearMonth";
                     break;

                 case GROUP_BY_FOR_DATE_BY_YEAR:
                     $ArraySelectGroupBy[] = "DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y') AS ForYear";
                     $ArrayGroupByCondition[] = "ForYear";
                     break;

                 case GROUP_BY_CREATION_DATE_BY_DAY:
                     $ArraySelectGroupBy[] = "DATE_FORMAT(cr.CanteenRegistrationDate, '%Y-%m-%d') AS DayDate";
                     $ArrayGroupByCondition[] = "DayDate";
                     break;

                 case GROUP_BY_CREATION_DATE_BY_YEARMONTH:
                     $ArraySelectGroupBy[] = "DATE_FORMAT(cr.CanteenRegistrationDate, '%Y-%m') AS YearMonth";
                     $ArrayGroupByCondition[] = "YearMonth";
                     break;

                 case GROUP_BY_CREATION_DATE_BY_YEAR:
                     $ArraySelectGroupBy[] = "DATE_FORMAT(cr.CanteenRegistrationDate, '%Y') AS Year";
                     $ArrayGroupByCondition[] = "Year";
                     break;

                 case GROUP_BY_GRADE:
                     $ArraySelectGroupBy[] = "cr.CanteenRegistrationChildGrade AS Grade";
                     $ArrayGroupByCondition[] = "Grade";
                     break;

                 case GROUP_BY_CLASSROOM:
                     $ArraySelectGroupBy[] = "cr.CanteenRegistrationChildClass AS Classroom";
                     $ArrayGroupByCondition[] = "Classroom";
                     break;

                 case GROUP_BY_CHILD_ID:
                     $ArraySelectGroupBy[] = "c.ChildID";
                     $ArrayGroupByCondition[] = "ChildID";
                     break;

                 case GROUP_BY_FAMILY_ID:
                     $ArraySelectGroupBy[] = "f.FamilyID";
                     $ArrayGroupByCondition[] = "FamilyID";
                     break;
             }
         }
     }

     $ArraySelectGroupBy[] = "COUNT(cr.CanteenRegistrationID) AS NB";

     $Select = "SELECT ".implode(', ', $ArraySelectGroupBy);

     $From = "FROM CanteenRegistrations cr, Children c, Families f";

     $Where = "WHERE cr.ChildID = c.ChildID AND c.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') AND \""
                       .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')) OR (\""
                       .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND (DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $sGroupByCondition = '';
     if (!empty($ArrayGroupByCondition))
     {
         $sGroupByCondition = "GROUP BY ".implode(', ', $ArrayGroupByCondition);
     }

     $DbResult = $DbConnection->query("$Select $From $Where $sGroupByCondition $HavingConditions");

     // Taken into account the NB field to generate the array result
     $ArrayGroupByCondition[] = "NB";

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array();

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 foreach($ArrayGroupByCondition as $f => $Fieldname)
                 {
                     $ArrayRecords[$Fieldname][] = $Record[$Fieldname];
                 }
             }

             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Give the children don't eating to the canteen between 2 dates for a given child or all.
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2014-02-06 : patch pb about search modes on "CanteenRegistrationForDate" field
 *
 * @since 2012-10-17
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order children
 * @param $ChildID              Integer              ID of the child for which we want [1..n]
 * @param $Mode                 Enum                 Mode to find children don't eating to the canteen
 * @param $ArrayParams          Mixed Array          Other criterion to filter canteen registrations
 *
 * @return mixed Array                               The children don't eating to the canteen between the 2 dates
 *                                                   for a given child or all, an empty array otherwise
 */
 function getNotCanteenRegistrations($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'ChildID', $ChildID = NULL, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
 {
     if (empty($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Conditions = "";
     if ((!empty($ChildID)) && ($ChildID >= 1) && (isInteger($ChildID)))
     {
         $Conditions .= " AND c.ChildID = $ChildID";
     }

     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['ChildClass'])) && (count($ArrayParams['ChildClass']) > 0))
         {
             $Conditions .= " AND c.ChildClass IN ".constructSQLINString($ArrayParams['ChildClass']);
         }

         if ((isset($ArrayParams['ChildGrade'])) && (count($ArrayParams['ChildGrade']) > 0))
         {
             $Conditions .= " AND c.ChildGrade IN ".constructSQLINString($ArrayParams['ChildGrade']);
         }

         if ((isset($ArrayParams['CanteenRegistrationWithoutPork'])) && (count($ArrayParams['CanteenRegistrationWithoutPork']) > 0))
         {
             $Conditions .= " AND c.ChildWithoutPork IN ".constructSQLINString($ArrayParams['CanteenRegistrationWithoutPork']);
         }

         if ((isset($ArrayParams['FamilyID'])) && (count($ArrayParams['FamilyID']) > 0))
         {
             $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams['FamilyID']);
         }
     }

     // We get children activated at this period
     $Conditions .= " AND (((c.ChildSchoolDate <= \"$StartDate\") OR (c.ChildSchoolDate BETWEEN \"$StartDate\" AND \"$EndDate\"))
                            AND ((c.ChildDesactivationDate IS NULL) OR (c.ChildDesactivationDate BETWEEN \"$StartDate\" AND \"$EndDate\")))";

     $Select = "SELECT c.ChildID, c.ChildFirstname, c.ChildGrade, c.ChildClass, c.ChildWithoutPork,
               f.FamilyID, f.FamilyLastname, CrTmp.ChildID, SuspTmp.ChildID";

     $From = "FROM Families f, Children c";

     $Where = "WHERE c.FamilyID = f.FamilyID $Conditions";

     $WhereRegistrations = "WHERE";
     $WhereSuspension = "WHERE";
     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $WhereRegistrations .= " \"".$StartDate."\" >= DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m-%d') AND \""
                                    .$EndDate."\" <= DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m-%d')";

             $WhereSuspension .= " \"".$StartDate."\" >= DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d') AND \""
                                 .$EndDate."\" <= DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d')";
             break;

         case PLANNING_INCLUDED_IN_DATES:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $WhereRegistrations .= " DATE_FORMAT(cr.CanteenRegistrationForDate,'%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \""
                                    .$EndDate."\"";

             $WhereSuspension .= " DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \"".$EndDate."\"";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $WhereRegistrations .= " (DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \""
                                    .$EndDate."\")";

             $WhereSuspension .= " ((\"".$StartDate."\" BETWEEN DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d')
                                 AND DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d')) OR (\"".$EndDate."\"
                                 BETWEEN DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d')
                                 AND DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d')))";
             break;

         default:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $WhereRegistrations .= " (DATE_FORMAT(cr.CanteenRegistrationForDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \""
                                    .$EndDate."\")";

             $WhereSuspension .= " ((DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \"".$EndDate
                                 ."\") OR (DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \""
                                 .$EndDate."\"))";
             break;
     }

     // Children without canteen registration at this time
     $From .= " LEFT JOIN (SELECT cr.ChildID FROM CanteenRegistrations cr $WhereRegistrations GROUP BY cr.ChildID) CrTmp
               ON (c.ChildID = CrTmp.ChildID)";

     // Children without suspension at this period
     $From .= " LEFT JOIN (SELECT s.ChildID FROM Suspensions s $WhereSuspension GROUP BY s.ChildID) SuspTmp
               ON (c.ChildID = SuspTmp.ChildID)";

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY c.ChildID
                                      HAVING CrTmp.ChildID IS NULL AND SuspTmp.ChildID IS NULL
                                      ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "CanteenRegistrationID" => array(),
                                   "ChildID" => array(),
                                   "ChildFirstname" => array(),
                                   "ChildGrade" => array(),
                                   "ChildClass" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             $i = 0;
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $i++;
                 $ArrayRecords["CanteenRegistrationID"][] = $i;
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
                 $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
                 $ArrayRecords["ChildWithoutPork"][] = $Record["ChildWithoutPork"];
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
 * Delete a canteen registration, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-28
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $CanteenRegistrationID     Integer      ID of the canteen registration to delete [1..n]
 *
 * @return Boolean                   TRUE if the canteen registration is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteCanteenRegistration($DbConnection, $CanteenRegistrationID)
 {
     // The parameters are correct?
     if ($CanteenRegistrationID > 0)
     {
         // Delete the canteen registration in the table
         $DbResult = $DbConnection->query("DELETE FROM CanteenRegistrations WHERE CanteenRegistrationID = $CanteenRegistrationID");
         if (!DB::isError($DbResult))
         {
             // Canteen registration deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the older date of the CanteenRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-02
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the CanteenRegistrations table,
 *                              empty string otherwise
 */
 function getCanteenRegistrationMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(CanteenRegistrationForDate) As minDate FROM CanteenRegistrations");
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
 * Give the earlier date of the CanteenRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-21
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the CanteenRegistrations table,
 *                              empty string otherwise
 */
 function getCanteenRegistrationMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(CanteenRegistrationForDate) As maxDate FROM CanteenRegistrations");
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
 * Check if a "more meal" entry exists in the MoreMeals table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-01
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $MoreMealID               Integer      ID of the searched entry [1..n]
 *
 * @return Boolean                  TRUE if the "more meal" entry exists, FALSE otherwise
 */
 function isExistingMoreMeal($DbConnection, $MoreMealID)
 {
     $DbResult = $DbConnection->query("SELECT MoreMealID FROM MoreMeals WHERE MoreMealID = $MoreMealID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The "more meal" entry exists
             return TRUE;
         }
     }

     // The "more meal" entry doesn't exist
     return FALSE;
 }


/**
 * Add a "more meal" entry for a day in the MoreMeals table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-01
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $MoreMealDate                      Date         Creation date of the entry (yyyy-mm-dd)
 * @param $MoreMealForDate                   Date         Date for which the entry is (yyyy-mm-dd)
 * @param $SupportMemberID                   Integer      ID of the supporter, author of the entry [1..n]
 * @param $MoreMealQuantity                  Integer      Number of more meals with pork [0..n]
 * @param $MoreMealWithoutPorkQuantity       Integer      Number of more meals without pork [0..n]
 *
 * @return Integer                           The primary key of the entry [1..n], 0 otherwise
 */
 function dbAddMoreMeal($DbConnection, $MoreMealDate, $MoreMealForDate, $SupportMemberID, $MoreMealQuantity = 1, $MoreMealWithoutPorkQuantity = 0)
 {
     if (($SupportMemberID > 0) && ($MoreMealQuantity >= 0) && ($MoreMealWithoutPorkQuantity >= 0) && ($MoreMealQuantity + $MoreMealWithoutPorkQuantity >= 1))
     {
         // Check if the entry is a new entry for a day
         $DbResult = $DbConnection->query("SELECT MoreMealID FROM MoreMeals WHERE MoreMealForDate = \"$MoreMealForDate\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the MoreMealDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $MoreMealDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $MoreMealDate = ", MoreMealDate = \"$MoreMealDate\"";
                 }

                 // Check if the MoreMealForDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $MoreMealForDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $MoreMealForDate = ", MoreMealForDate = \"$MoreMealForDate\"";
                 }

                 // It's a new entry
                 $id = getNewPrimaryKey($DbConnection, "MoreMeals", "MoreMealID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO MoreMeals SET MoreMealID = $id, SupportMemberID = $SupportMemberID,
                                                      MoreMealQuantity = $MoreMealQuantity,
                                                      MoreMealWithoutPorkQuantity = $MoreMealWithoutPorkQuantity $MoreMealDate
                                                      $MoreMealForDate");
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
 * Update an existing "more meal" entry for a day in the MoreMeals table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-01
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $MoreMealID                        Integer      ID of the entry to update [1..n]
 * @param $MoreMealDate                      Date         Creation date of the entry (yyyy-mm-dd)
 * @param $MoreMealForDate                   Date         Date for which the entry is (yyyy-mm-dd)
 * @param $SupportMemberID                   Integer      ID of the supporter, author of the entry [1..n]
 * @param $MoreMealQuantity                  Integer      Number of more meals with pork [0..n]
 * @param $MoreMealWithoutPorkQuantity       Integer      Number of more meals without pork [0..n]
 *
 * @return Integer                           The primary key of the entry [1..n], 0 otherwise
 */
 function dbUpdateMoreMeal($DbConnection, $MoreMealID, $MoreMealDate, $MoreMealForDate, $SupportMemberID, $MoreMealQuantity = NULL, $MoreMealWithoutPorkQuantity = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($MoreMealID < 1) || (!isInteger($MoreMealID)))
     {
         // ERROR
         return 0;
     }

     // Check if the MoreMealDate is valide
     if (!is_null($MoreMealDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $MoreMealDate) == 0)
         {
             return 0;
         }
         else
         {
             // The MoreMealDate field will be updated
             $ArrayParamsUpdate[] = "MoreMealDate = \"$MoreMealDate\"";
         }
     }

     // Check if the MoreMealForDate is valide
     if (!is_null($MoreMealForDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $MoreMealForDate) == 0)
         {
             return 0;
         }
         else
         {
             // The MoreMealForDate field will be updated
             $ArrayParamsUpdate[] = "MoreMealForDate = \"$MoreMealForDate\"";
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

     if (!is_Null($MoreMealQuantity))
     {
         if (($MoreMealQuantity < 0) || (!isInteger($MoreMealQuantity)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The MoreMealQuantity field will be updated
             $ArrayParamsUpdate[] = "MoreMealQuantity = $MoreMealQuantity";
         }
     }

     if (!is_Null($MoreMealWithoutPorkQuantity))
     {
         if (($MoreMealWithoutPorkQuantity < 0) || (!isInteger($MoreMealWithoutPorkQuantity)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The MoreMealWithoutPorkQuantity field will be updated
             $ArrayParamsUpdate[] = "MoreMealWithoutPorkQuantity = $MoreMealWithoutPorkQuantity";
         }
     }

     // Here, the parameters are correct, we check if the entry exists
     if (isExistingMoreMeal($DbConnection, $MoreMealID))
     {
         // We check if the entry is unique for a day
         $DbResult = $DbConnection->query("SELECT MoreMealID FROM MoreMeals WHERE MoreMealForDate = \"$MoreMealForDate\"
                                          AND MoreMealID <> $MoreMealID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The entry exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE MoreMeals SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE MoreMealID = $MoreMealID");
                     if (!DB::isError($DbResult))
                     {
                         // Entry updated
                         return $MoreMealID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $MoreMealID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the "more meal" entries between 2 dates.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-01
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order "more meals" entries
 * @param $Mode                 Enum                 Mode to find entries
 *
 * @return mixed Array                               The "more meals" entries and between the 2 dates,
 *                                                   an empty array otherwise
 */
 function getMoreMeals($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'MoreMealForDate', $Mode = PLANNING_BETWEEN_DATES)
 {
     if (is_Null($EndDate))
     {
         // No end date specified : we get the today date
         $EndDate = date("Y-m-d");
     }

     $Select = "SELECT mm.MoreMealID, mm.MoreMealDate, mm.MoreMealForDate, mm.MoreMealQuantity, mm.MoreMealWithoutPorkQuantity,
               mm.SupportMemberID";
     $From = "FROM MoreMeals mm";
     $Where = "WHERE";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " \"$StartDate\" >= DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d')
                       AND \"$EndDate\" <= DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d')";
             break;

         case PLANNING_INCLUDED_IN_DATES:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d') >= \"$StartDate\"
                       AND DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d') <= \"$EndDate\"";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " ((\"$StartDate\" BETWEEN DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d') AND
                       DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d')) OR (\"$EndDate\" BETWEEN DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d')
                       AND DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " ((DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")
                       OR (DATE_FORMAT(mm.MoreMealForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\"))";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY mm.MoreMealID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "MoreMealID" => array(),
                                   "MoreMealDate" => array(),
                                   "MoreMealForDate" => array(),
                                   "MoreMealQuantity" => array(),
                                   "MoreMealWithoutPorkQuantity" => array(),
                                   "SupportMemberID" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["MoreMealID"][] = $Record["MoreMealID"];
                 $ArrayRecords["MoreMealDate"][] = $Record["MoreMealDate"];
                 $ArrayRecords["MoreMealForDate"][] = $Record["MoreMealForDate"];
                 $ArrayRecords["MoreMealQuantity"][] = $Record["MoreMealQuantity"];
                 $ArrayRecords["MoreMealWithoutPorkQuantity"][] = $Record["MoreMealWithoutPorkQuantity"];
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
             }

             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Delete a "more meal" entry, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-01
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $MoreMealID                Integer      ID of the entry to delete [1..n]
 *
 * @return Boolean                   TRUE if the entry is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteMoreMeal($DbConnection, $MoreMealID)
 {
     // The parameters are correct?
     if ($MoreMealID > 0)
     {
         // Delete the "more meal" entry in the table
         $DbResult = $DbConnection->query("DELETE FROM MoreMeals WHERE MoreMealID = $MoreMealID");
         if (!DB::isError($DbResult))
         {
             // Entry deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }
?>