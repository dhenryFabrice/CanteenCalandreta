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
 * Common module : library of database functions used for the NurseryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2012-02-14
 */


/**
 * Check if a nursery registration exists in the NurseryRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-14
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $NurseryRegistrationID    Integer      ID of the nursery registration searched [1..n]
 *
 * @return Boolean                  TRUE if the nursery registration exists, FALSE otherwise
 */
 function isExistingNurseryRegistration($DbConnection, $NurseryRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT NurseryRegistrationID FROM NurseryRegistrations WHERE NurseryRegistrationID = $NurseryRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The nursery registration exists
             return TRUE;
         }
     }

     // The nursery registration doesn't exist
     return FALSE;
 }


/**
 * Give its ID if a nursery registration exists in the NurseryRegistrations table, for a given date
 * and a given child
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2020-03-09
 *
 * @param $DbConnection                    DB object    Object of the opened database connection
 * @param $ChildID                         Integer      ID of the concerned child [1..n]
 * @param $NurseryRegistrationForDate      Date         Date for which the nursery registration is (yyyy-mm-dd)
 *
 * @return Integer                         Nursery registration IF if is exists for this child and date,
 *                                         0 otherwise
 */
 function getExistingNurseryRegistrationForChildAndDate($DbConnection, $ChildID, $NurseryRegistrationForDate)
 {
     if (($ChildID > 0) && (!empty($NurseryRegistrationForDate)))
     {
         $DbResult = $DbConnection->query("SELECT NurseryRegistrationID FROM NurseryRegistrations
                                           WHERE NurseryRegistrationForDate = \"$NurseryRegistrationForDate\" AND ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 // The nursery registration exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['NurseryRegistrationID'];
             }
         }
     }

     // The nursery registration doesn't exist
     return 0;
 }


/**
 * Add a nursery registration for a day (AM and/or PM) and for a child in the NurseryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2012-07-10 : v1.1. Taken into account NurseryRegistrationChildGrade and NurseryRegistrationChildClass fields
 *     - 2014-02-03 : v1.2. Taken into account NurseryRegistrationIsLate field
 *     - 2020-02-18 : v1.3. Taken into account NurseryRegistrationOtherTimeslots field
 *
 * @since 2012-02-14
 *
 * @param $DbConnection                         DB object    Object of the opened database connection
 * @param $NurseryRegistrationDate              Date         Creation date of the nursery registration (yyyy-mm-dd)
 * @param $NurseryRegistrationForDate           Date         Date for which the nursery registration is (yyyy-mm-dd)
 * @param $ChildID                              Integer      ID of the child of the nursery registration [1..n]
 * @param $SupportMemberID                      Integer      ID of the supporter who have registered the child [1..n]
 * @param $NurseryRegistrationForAM             Integer      The registration is for the AM [0..1]
 * @param $NurseryRegistrationForPM             Integer      The registration is for the PM [0..1]
 * @param $NurseryRegistrationChildGrade        Integer      Grade of the child [0..n]
 * @param $NurseryRegistrationChildClass        Integer      Class of the child [0..n]
 * @param $NurseryRegistrationAdminDate         Date         Date if the nursery registration was administrated (yyyy-mm-dd)
 * @param $NurseryRegistrationIsLate            Integer      1 if family is late to get the child, 0 otherwise [0..1]
 * @param $NurseryRegistrationOtherTimeslots    Integer      The other checked timeslots [0..255]. Each bit is a timeslot,
 *                                                           checked or not
 *
 * @return Integer                              The primary key of the nursery registration [1..n], 0 otherwise
 */
 function dbAddNurseryRegistration($DbConnection, $NurseryRegistrationDate, $NurseryRegistrationForDate, $ChildID, $SupportMemberID, $NurseryRegistrationForAM = 0, $NurseryRegistrationForPM = 0, $NurseryRegistrationChildGrade = 0, $NurseryRegistrationChildClass = 0, $NurseryRegistrationAdminDate = NULL, $NurseryRegistrationIsLate = 0, $NurseryRegistrationOtherTimeslots = NULL)
 {
     if (($ChildID > 0) && ($SupportMemberID > 0) && ($NurseryRegistrationForAM >= 0) && ($NurseryRegistrationForPM >= 0)
         && ($NurseryRegistrationChildGrade >= 0) && ($NurseryRegistrationChildClass >= 0) && ($NurseryRegistrationIsLate >= 0))
     {
         // Check if the nursery registration is a new nursery registration for a child and a day
         $DbResult = $DbConnection->query("SELECT NurseryRegistrationID FROM NurseryRegistrations
                                           WHERE NurseryRegistrationForDate = \"$NurseryRegistrationForDate\" AND ChildID = $ChildID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the NurseryRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $NurseryRegistrationDate) == 0)
                 {
                     // Note a date
                     return 0;
                 }
                 else
                 {
                     $NurseryRegistrationDate = ", NurseryRegistrationDate = \"$NurseryRegistrationDate\"";
                 }

                 // Check if the NurseryRegistrationForDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $NurseryRegistrationForDate) == 0)
                 {
                     // Not a date
                     return 0;
                 }
                 else
                 {
                     $NurseryRegistrationForDate = ", NurseryRegistrationForDate = \"$NurseryRegistrationForDate\"";
                 }

                 // Check if the CanteenRegistrationAdminDate is valide
                 if (!empty($NurseryRegistrationAdminDate))
                 {
                     if (preg_match("[\d\d\d\d-\d\d-\d\d]", $NurseryRegistrationAdminDate) == 0)
                     {
                         // Not a date
                         return 0;
                     }
                     else
                     {
                         $NurseryRegistrationAdminDate = ", NurseryRegistrationAdminDate = \"$NurseryRegistrationAdminDate\"";
                     }
                 }

                 if (empty($NurseryRegistrationOtherTimeslots))
                 {
                     $NurseryRegistrationOtherTimeslots = ", NurseryRegistrationOtherTimeslots = NULL";
                 }
                 elseif (($NurseryRegistrationOtherTimeslots > 0) && ($NurseryRegistrationOtherTimeslots <= 255))
                 {
                     $NurseryRegistrationOtherTimeslots = ", NurseryRegistrationOtherTimeslots = $NurseryRegistrationOtherTimeslots";
                 }
                 else
                 {
                     // Error because this value must be a TINYINT
                     return 0;
                 }

                 // It's a new nursery registration
                 $id = getNewPrimaryKey($DbConnection, "NurseryRegistrations", "NurseryRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO NurseryRegistrations SET NurseryRegistrationID = $id, ChildID = $ChildID,
                                                      SupportMemberID = $SupportMemberID, NurseryRegistrationForAM = $NurseryRegistrationForAM,
                                                      NurseryRegistrationForPM = $NurseryRegistrationForPM,
                                                      NurseryRegistrationChildGrade = $NurseryRegistrationChildGrade,
                                                      NurseryRegistrationChildClass = $NurseryRegistrationChildClass,
                                                      NurseryRegistrationIsLate = $NurseryRegistrationIsLate $NurseryRegistrationDate
                                                      $NurseryRegistrationForDate $NurseryRegistrationAdminDate $NurseryRegistrationOtherTimeslots");
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
 * Update an existing nursery registration for a child and a day (AM and/or PM) in the NurseryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2012-07-10 : v1.1. Taken into account NurseryRegistrationChildGrade and NurseryRegistrationChildClass fields
 *     - 2014-02-03 : v1.2. Taken into account NurseryRegistrationIsLate field
 *     - 2020-02-18 : v1.3. Taken into account NurseryRegistrationOtherTimeslots field
 *
 * @since 2012-02-14
 *
 * @param $DbConnection                          DB object    Object of the opened database connection
 * @param $NurseryRegistrationID                 Integer      ID of the nursery registration to update [1..n]
 * @param $NurseryRegistrationDate               Date         Creation date of the nursery registration (yyyy-mm-dd)
 * @param $NurseryRegistrationForDate            Date         Date for which the nursery registration is (yyyy-mm-dd)
 * @param $ChildID                               Integer      ID of the child of the nursery registration [1..n]
 * @param $SupportMemberID                       Integer      ID of the supporter who have registered the child [1..n]
 * @param $NurseryRegistrationForAM              Integer      The registration is for the AM [0..1]
 * @param $NurseryRegistrationForPM              Integer      The registration is for the PM [0..1]
 * @param $NurseryRegistrationChildGrade         Integer      Grade of the child [0..n]
 * @param $NurseryRegistrationChildClass         Integer      Class of the child [0..n]
 * @param $NurseryRegistrationAdminDate          Date         Date if the nursery registration was administrated (yyyy-mm-dd)
 * @param $NurseryRegistrationIsLate             Integer      1 if family is late to get the child, 0 otherwise [0..1]
 * @param $NurseryRegistrationOtherTimeslots     Integer      The other checked timeslots [0..255]. Each bit is a timeslot,
 *                                                            checked or not
 *
 * @return Integer                               The primary key of the nursery registration [1..n], 0 otherwise
 */
 function dbUpdateNurseryRegistration($DbConnection, $NurseryRegistrationID, $NurseryRegistrationDate, $NurseryRegistrationForDate, $ChildID, $SupportMemberID, $NurseryRegistrationForAM = NULL, $NurseryRegistrationForPM = NULL, $NurseryRegistrationChildGrade = NULL, $NurseryRegistrationChildClass = NULL, $NurseryRegistrationAdminDate = NULL, $NurseryRegistrationIsLate = NULL, $NurseryRegistrationOtherTimeslots = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($NurseryRegistrationID < 1) || (!isInteger($NurseryRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the NurseryRegistrationDate is valide
     if (!is_null($NurseryRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $NurseryRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The NurseryRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationDate = \"$NurseryRegistrationDate\"";
         }
     }

     // Check if the NurseryRegistrationForDate is valide
     if (!is_null($NurseryRegistrationForDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $NurseryRegistrationForDate) == 0)
         {
             return 0;
         }
         else
         {
             // The NurseryRegistrationForDate field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationForDate = \"$NurseryRegistrationForDate\"";
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

     if (!is_Null($NurseryRegistrationForAM))
     {
         if (($NurseryRegistrationForAM < 0) || (!isInteger($NurseryRegistrationForAM)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The NurseryRegistrationForAM field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationForAM = $NurseryRegistrationForAM";
         }
     }

     if (!is_Null($NurseryRegistrationForPM))
     {
         if (($NurseryRegistrationForPM < 0) || (!isInteger($NurseryRegistrationForPM)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The NurseryRegistrationForPM field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationForPM = $NurseryRegistrationForPM";
         }
     }

     if (!is_Null($NurseryRegistrationChildGrade))
     {
         if (($NurseryRegistrationChildGrade < 0) || (!isInteger($NurseryRegistrationChildGrade)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The NurseryRegistrationChildGrade field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationChildGrade = $NurseryRegistrationChildGrade";
         }
     }

     if (!is_Null($NurseryRegistrationChildClass))
     {
         if (($NurseryRegistrationChildClass < 0) || (!isInteger($NurseryRegistrationChildClass)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The NurseryRegistrationChildClass field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationChildClass = $NurseryRegistrationChildClass";
         }
     }

     if (!is_Null($NurseryRegistrationIsLate))
     {
         if (($NurseryRegistrationIsLate < 0) || (!isInteger($NurseryRegistrationIsLate)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The NurseryRegistrationIsLate field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationIsLate = $NurseryRegistrationIsLate";
         }
     }

     if (!is_null($NurseryRegistrationAdminDate))
     {
         if (empty($NurseryRegistrationAdminDate))
         {
             // The NurseryRegistrationAdminDate field will be updated
             $ArrayParamsUpdate[] = "NurseryRegistrationAdminDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $NurseryRegistrationAdminDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The NurseryRegistrationAdminDate field will be updated
                 $ArrayParamsUpdate[] = "NurseryRegistrationAdminDate = \"$NurseryRegistrationAdminDate\"";
             }
         }
     }

     if (!is_Null($NurseryRegistrationOtherTimeslots))
     {
         if (($NurseryRegistrationOtherTimeslots < 0) || (!isInteger($NurseryRegistrationOtherTimeslots)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The NurseryRegistrationOtherTimeslots field will be updated
             if ($NurseryRegistrationOtherTimeslots == 0)
             {
                 // No value selected
                 $ArrayParamsUpdate[] = "NurseryRegistrationOtherTimeslots = NULL";
             }
             else
             {
                 // Value selected
                 $ArrayParamsUpdate[] = "NurseryRegistrationOtherTimeslots = $NurseryRegistrationOtherTimeslots";
             }
         }
     }

     // Here, the parameters are correct, we check if the nursery registration exists
     if (isExistingNurseryRegistration($DbConnection, $NurseryRegistrationID))
     {
         // We check if the nursery registration is unique for a child and a day
         $DbResult = $DbConnection->query("SELECT NurseryRegistrationID FROM NurseryRegistrations
                                           WHERE NurseryRegistrationForDate = \"$NurseryRegistrationForDate\" AND ChildID = $ChildID
                                           AND NurseryRegistrationID <> $NurseryRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The nursery registration exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE NurseryRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE NurseryRegistrationID = $NurseryRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Nursery registration updated
                         return $NurseryRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $NurseryRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the children who can be listed in the nursery planning for a period (between 2 dates)
 * and with nursery registrations for only activated children or all.
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2012-07-10 : v1.1. Patch a bug about new children will appear in old plannings
 *     - 2013-02-11 : v1.2. Patch a bug about desactivated children will not appear in old plannings
 *     - 2014-02-06 : v1.3. Patch problem about search modes on "NurseryRegistrationForDate" field
 *
 *
 * @since 2012-02-14
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order children
 * @param $bActivated           Boolean              Get only activated children
 * @param $Mode                 Enum                 Mode to find children
 *
 * @return mixed Array          The children who can be listed in the nursery planning,
 *                              an empty array otherwise
 */
 function getChildrenListForNurseryPlanning($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'ChildClass', $bActivated = FALSE, $Mode = PLANNING_BETWEEN_DATES)
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

     $Select = "SELECT c.ChildID, c.ChildFirstname, IFNULL(NrTmp.NurseryRegistrationChildGrade, c.ChildGrade) AS ChildGrade,
                IFNULL(NrTmp.NurseryRegistrationChildClass, c.ChildClass) AS ChildClass, f.FamilyID, f.FamilyLastname";
     $From = "FROM Families f, Children c";
     $Where = "WHERE f.FamilyID = c.FamilyID $Conditions1";

     $Where2 = "WHERE";
     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where2 .= " \"".$StartDate."\" >= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') AND \""
                        .$EndDate."\" <= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where2 .= " ((\"".$StartDate."\" >= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')) OR (\""
                        .$EndDate."\" <= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where2 .= " (DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $From .= " LEFT JOIN (SELECT nr.ChildID, nr.NurseryRegistrationChildGrade, nr.NurseryRegistrationChildClass
               FROM NurseryRegistrations nr $Where2 GROUP BY nr.ChildID) NrTmp
               ON (c.ChildID = NrTmp.ChildID)";

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
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
                 $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
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
 * Give the nursery registrations between 2 dates for a given child or all.
 *
 * @author Christophe Javouhey
 * @version 1.4
 *     - 2012-07-10 : v1.1. Taken into account NurseryRegistrationChildGrade and NurseryRegistrationChildClass fields
 *     - 2013-11-29 : v1.2. $ChildID can be an array of integers
 *     - 2014-02-03 : v1.3. Taken into account NurseryRegistrationIsLate field and ForAM and ForPM criterion and patch pb
 *                    about search modes on "NurseryRegistrationForDate" field
 *     - 2020-02-18 : v1.4. Taken into account NurseryRegistrationOtherTimeslots field
 *
 * @since 2012-02-14
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order nursery registrations
 * @param $ChildID              Integer              ID of the child for which we want nursery registrations [1..n]
 * @param $Mode                 Enum                 Mode to find nursery registrations
 * @param $ArrayParams          Mixed Array          Other criterion to filter nursery registrations
 *
 * @return mixed Array          The nursery registrations and between the 2 dates for a given child or all,
 *                              an empty array otherwise
 */
 function getNurseryRegistrations($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'NurseryRegistrationForDate', $ChildID = NULL, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
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

     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['SupportMemberID'])) && (count($ArrayParams['SupportMemberID']) > 0))
         {
             $Conditions .= " AND nr.SupportMemberID IN ".constructSQLINString($ArrayParams['SupportMemberID']);
         }

         if ((isset($ArrayParams['ChildClass'])) && (count($ArrayParams['ChildClass']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationChildClass IN ".constructSQLINString($ArrayParams['ChildClass']);
         }

         if ((isset($ArrayParams['ChildGrade'])) && (count($ArrayParams['ChildGrade']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationChildGrade IN ".constructSQLINString($ArrayParams['ChildGrade']);
         }

         if ((isset($ArrayParams['ForAM'])) && (count($ArrayParams['ForAM']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationForAM IN ".constructSQLINString($ArrayParams['ForAM']);
         }

         if ((isset($ArrayParams['ForPM'])) && (count($ArrayParams['ForPM']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationForPM IN ".constructSQLINString($ArrayParams['ForPM']);
         }

         if ((isset($ArrayParams['NurseryRegistrationOtherTimeslots'])) && ($ArrayParams['NurseryRegistrationOtherTimeslots'] > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationOtherTimeslots & ".$ArrayParams['NurseryRegistrationOtherTimeslots'];
         }

         if ((isset($ArrayParams['IsLate'])) && (count($ArrayParams['IsLate']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationIsLate IN ".constructSQLINString($ArrayParams['IsLate']);
         }

         if ((isset($ArrayParams['FamilyID'])) && (count($ArrayParams['FamilyID']) > 0))
         {
             $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams['FamilyID']);
         }
     }

     $Select = "SELECT nr.NurseryRegistrationID, nr.NurseryRegistrationDate, nr.NurseryRegistrationForDate, nr.NurseryRegistrationAdminDate,
               nr.NurseryRegistrationForAM, nr.NurseryRegistrationForPM, nr.NurseryRegistrationOtherTimeslots, nr.NurseryRegistrationChildGrade,
               nr.NurseryRegistrationChildClass, nr.NurseryRegistrationIsLate, nr.SupportMemberID, c.ChildID, c.ChildFirstname,
               f.FamilyID, f.FamilyLastname";

     $From = "FROM NurseryRegistrations nr, Children c, Families f";

     $Where = "WHERE nr.ChildID = c.ChildID AND c.FamilyID = f.FamilyID $Conditions";

     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $Where .= " AND \"".$StartDate."\" >= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') AND \""
                       .$EndDate."\" <= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $Where .= " AND ((\"".$StartDate."\" >= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')) OR (\""
                       .$EndDate."\" <= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')))";
             break;

         default:
         case PLANNING_INCLUDED_IN_DATES:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $Where .= " AND (DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";
             break;
     }

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY nr.NurseryRegistrationID ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "NurseryRegistrationID" => array(),
                                   "NurseryRegistrationDate" => array(),
                                   "NurseryRegistrationForDate" => array(),
                                   "NurseryRegistrationAdminDate" => array(),
                                   "NurseryRegistrationForAM" => array(),
                                   "NurseryRegistrationForPM" => array(),
                                   "NurseryRegistrationOtherTimeslots" => array(),
                                   "NurseryRegistrationIsLate" => array(),
                                   "SupportMemberID" => array(),
                                   "ChildID" => array(),
                                   "ChildFirstname" => array(),
                                   "ChildGrade" => array(),
                                   "ChildClass" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["NurseryRegistrationID"][] = $Record["NurseryRegistrationID"];
                 $ArrayRecords["NurseryRegistrationDate"][] = $Record["NurseryRegistrationDate"];
                 $ArrayRecords["NurseryRegistrationForDate"][] = $Record["NurseryRegistrationForDate"];
                 $ArrayRecords["NurseryRegistrationAdminDate"][] = $Record["NurseryRegistrationAdminDate"];
                 $ArrayRecords["NurseryRegistrationForAM"][] = $Record["NurseryRegistrationForAM"];
                 $ArrayRecords["NurseryRegistrationForPM"][] = $Record["NurseryRegistrationForPM"];
                 $ArrayRecords["NurseryRegistrationOtherTimeslots"][] = $Record["NurseryRegistrationOtherTimeslots"];
                 $ArrayRecords["NurseryRegistrationIsLate"][] = $Record["NurseryRegistrationIsLate"];
                 $ArrayRecords["ChildGrade"][] = $Record["NurseryRegistrationChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["NurseryRegistrationChildClass"];
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
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
 * Give the children don't registered at the nursery between 2 dates for a given child or all.
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2020-02-18 : v1.1. Taken into account NurseryRegistrationOtherTimeslots field
 *
 * @since 2017-09-21
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $OrderBy              String               To order children
 * @param $ChildID              Integer              ID of the child for which we want [1..n]
 * @param $Mode                 Enum                 Mode to find children don't registered at the nursery
 * @param $ArrayParams          Mixed Array          Other criterion to filter canteen registrations
 *
 * @return mixed Array                               The children don't registered at the nursery between the 2 dates
 *                                                   for a given child or all, an empty array otherwise
 */
 function getNotNurseryRegistrations($DbConnection, $StartDate, $EndDate = NULL, $OrderBy = 'ChildID', $ChildID = NULL, $Mode = PLANNING_BETWEEN_DATES, $ArrayParams = array())
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

     if (!empty($ArrayParams))
     {
         if ((isset($ArrayParams['SupportMemberID'])) && (count($ArrayParams['SupportMemberID']) > 0))
         {
             $Conditions .= " AND nr.SupportMemberID IN ".constructSQLINString($ArrayParams['SupportMemberID']);
         }

         if ((isset($ArrayParams['ChildClass'])) && (count($ArrayParams['ChildClass']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationChildClass IN ".constructSQLINString($ArrayParams['ChildClass']);
         }

         if ((isset($ArrayParams['ChildGrade'])) && (count($ArrayParams['ChildGrade']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationChildGrade IN ".constructSQLINString($ArrayParams['ChildGrade']);
         }

         if ((isset($ArrayParams['ForAM'])) && (count($ArrayParams['ForAM']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationForAM IN ".constructSQLINString($ArrayParams['ForAM']);
         }

         if ((isset($ArrayParams['ForPM'])) && (count($ArrayParams['ForPM']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationForPM IN ".constructSQLINString($ArrayParams['ForPM']);
         }

         if ((isset($ArrayParams['NurseryRegistrationOtherTimeslots'])) && ($ArrayParams['NurseryRegistrationOtherTimeslots'] > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationOtherTimeslots & ".$ArrayParams['NurseryRegistrationOtherTimeslots'];
         }

         if ((isset($ArrayParams['IsLate'])) && (count($ArrayParams['IsLate']) > 0))
         {
             $Conditions .= " AND nr.NurseryRegistrationIsLate IN ".constructSQLINString($ArrayParams['IsLate']);
         }

         if ((isset($ArrayParams['FamilyID'])) && (count($ArrayParams['FamilyID']) > 0))
         {
             $Conditions .= " AND f.FamilyID IN ".constructSQLINString($ArrayParams['FamilyID']);
         }
     }

     // We get children activated at this period
     $Conditions .= " AND (((c.ChildSchoolDate <= \"$StartDate\") OR (c.ChildSchoolDate BETWEEN \"$StartDate\" AND \"$EndDate\"))
                            AND ((c.ChildDesactivationDate IS NULL) OR (c.ChildDesactivationDate BETWEEN \"$StartDate\" AND \"$EndDate\")))";

     $Select = "SELECT c.ChildID, c.ChildFirstname, c.ChildGrade, c.ChildClass, f.FamilyID, f.FamilyLastname,
                NrTmp.ChildID, SuspTmp.ChildID";

     $From = "FROM Families f, Children c";

     $Where = "WHERE c.FamilyID = f.FamilyID $Conditions";

     $WhereRegistrations = "WHERE";
     $WhereSuspension = "WHERE";
     switch($Mode)
     {
         case DATES_INCLUDED_IN_PLANNING:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $WhereRegistrations .= " \"".$StartDate."\" >= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') AND \""
                                    .$EndDate."\" <= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')";

             $WhereSuspension .= " \"".$StartDate."\" >= DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d') AND \""
                                 .$EndDate."\" <= DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d')";
             break;

         case PLANNING_INCLUDED_IN_DATES:
             // Period [start date ; end date] must be included in the planning [start date ; end date]
             $WhereRegistrations .= " (DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";

             $WhereSuspension .= " DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \"".$EndDate."\"";
             break;

         case DATES_BETWEEN_PLANNING:
             // Planning [start date ; end date] must be touch the period [start date ; end date]
             $WhereRegistrations .= " ((\"".$StartDate."\" >= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')) OR (\""
                                    .$EndDate."\" <= DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d')))";

             $WhereSuspension .= " ((\"".$StartDate."\" BETWEEN DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d')
                                 AND DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d')) OR (\"".$EndDate."\"
                                 BETWEEN DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d')
                                 AND DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d')))";
             break;

         default:
         case PLANNING_BETWEEN_DATES:
             // Period [start date ; end date] must be touch the planning [start date ; end date]
             $WhereRegistrations .= " (DATE_FORMAT(nr.NurseryRegistrationForDate,'%Y-%m-%d') BETWEEN \"$StartDate\" AND \"$EndDate\")";

             $WhereSuspension .= " ((DATE_FORMAT(s.SuspensionStartDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \"".$EndDate
                                 ."\") OR (DATE_FORMAT(s.SuspensionEndDate, '%Y-%m-%d') BETWEEN \"".$StartDate."\" AND \""
                                 .$EndDate."\"))";
             break;
     }

     // Children without canteen registration at this time
     $From .= " LEFT JOIN (SELECT nr.ChildID FROM NurseryRegistrations nr $WhereRegistrations GROUP BY nr.ChildID) AS NrTmp
               ON (c.ChildID = NrTmp.ChildID)";

     // Children without suspension at this period
     $From .= " LEFT JOIN (SELECT s.ChildID FROM Suspensions s $WhereSuspension GROUP BY s.ChildID) AS SuspTmp
               ON (c.ChildID = SuspTmp.ChildID)";

     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY c.ChildID
                                      HAVING NrTmp.ChildID IS NULL AND SuspTmp.ChildID IS NULL
                                      ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayRecords = array(
                                   "NurseryRegistrationID" => array(),
                                   "ChildID" => array(),
                                   "ChildFirstname" => array(),
                                   "ChildGrade" => array(),
                                   "ChildClass" => array(),
                                   "NurseryRegistrationForAM" => array(),
                                   "NurseryRegistrationForPM" => array(),
                                   "NurseryRegistrationOtherTimeslots" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array()
                                  );

             $i = 0;
             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $i++;
                 $ArrayRecords["NurseryRegistrationID"][] = $i;
                 $ArrayRecords["ChildID"][] = $Record["ChildID"];
                 $ArrayRecords["ChildFirstname"][] = $Record["ChildFirstname"];
                 $ArrayRecords["ChildGrade"][] = $Record["ChildGrade"];
                 $ArrayRecords["ChildClass"][] = $Record["ChildClass"];
                 $ArrayRecords["NurseryRegistrationForAM"][] = 0;
                 $ArrayRecords["NurseryRegistrationForPM"][] = 0;
                 $ArrayRecords["NurseryRegistrationOtherTimeslots"][] = 0;
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
 * Delete a nursery registration, thanks to its ID.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-14
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $NurseryRegistrationID     Integer      ID of the nursery registration to delete [1..n]
 *
 * @return Boolean                   TRUE if the nursery registration is deleted if it exists,
 *                                   FALSE otherwise
 */
 function dbDeleteNurseryRegistration($DbConnection, $NurseryRegistrationID)
 {
     // The parameters are correct?
     if ($NurseryRegistrationID > 0)
     {
         // Delete the nursery registration in the table
         $DbResult = $DbConnection->query("DELETE FROM NurseryRegistrations WHERE NurseryRegistrationID = $NurseryRegistrationID");
         if (!DB::isError($DbResult))
         {
             // Nursery registration deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the older date of the NurseryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-14
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the older date of the NurseryRegistrations table,
 *                              empty string otherwise
 */
 function getNurseryRegistrationMinDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MIN(NurseryRegistrationForDate) As minDate FROM NurseryRegistrations");
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
 * Give the earlier date of the NurseryRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-20
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 *
 * @return Date                 Date of the earlier date of the NurseryRegistrations table,
 *                              empty string otherwise
 */
 function getNurseryRegistrationMaxDate($DbConnection)
 {
     $DbResult = $DbConnection->query("SELECT MAX(NurseryRegistrationForDate) As maxDate FROM NurseryRegistrations");
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
 * Compute capacities to supervise children for a given period and defined capacities in relation
 * with the grade of chidren
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2020-02-26
 *
 * @param $DbConnection         DB object            Object of the opened database connection
 * @param $Capacities           Mixed array          Defined capacities for grades
 * @param $StartDate            Date                 Start date of the period (yyyy-mm-dd)
 * @param $EndDate              Date                 End date of the period (yyyy-mm-dd)
 * @param $NbOtherTimeslots     Integer              Number of other timeslots [0..n]
 *
 * @return mixed Array                               The capacities to supervise children for each date and grade,
 *                                                   an empty array otherwise
 */
 function getNurseryRegistrationCapacities($DbConnection, $Capacities, $StartDate, $EndDate = NULL, $NbOtherTimeslots = 0)
 {
     if ((!empty($Capacities)) && (!empty($StartDate)) && ($NbOtherTimeslots >= 0))
     {
         // First, we get nursery registrations for the period
         $Select = "SELECT nr.NurseryRegistrationID, nr.NurseryRegistrationForDate, nr.NurseryRegistrationChildGrade, nr.NurseryRegistrationForAM,
                    nr.NurseryRegistrationForPM, nr.NurseryRegistrationOtherTimeslots";
         $From = "FROM NurseryRegistrations nr";
         $Where = "WHERE nr.NurseryRegistrationForDate >= \"$StartDate\"";

         if (!empty($EndDate))
         {
             $Where .= " AND nr.NurseryRegistrationForDate <= \"$EndDate\"";
         }

         $DbResult = $DbConnection->query("$Select $From $Where ORDER BY nr.NurseryRegistrationForDate, nr.NurseryRegistrationChildGrade");

         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 // Index grades in relation with capacities
                 $ArrayGradesIndex = array();
                 $CapacityMax = 0;
                 foreach($Capacities as $c => $CurrentCapacity)
                 {
                     foreach($CurrentCapacity['Grade'] as $g => $CurrentGrade)
                     {
                         $ArrayGradesIndex[$CurrentGrade] = $c;
                     }

                     if ($CurrentCapacity['Nb'] > $CapacityMax)
                     {
                         $CapacityMax = $CurrentCapacity['Nb'];
                     }
                 }

                 $ArrayRecords = array();
                 $iNbTotalTimeslots = 2 + $NbOtherTimeslots;

                 while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     if (!isset($ArrayRecords[$Record['NurseryRegistrationForDate']]))
                     {
                         // Init the capacities of this date
                         for($i = 0; $i < $iNbTotalTimeslots; $i++)
                         {
                             $ArrayRecords[$Record['NurseryRegistrationForDate']][] = array_fill(0, count($Capacities), array(
                                                                                                                              'Registrations' => 0,
                                                                                                                              'Max' => 0,
                                                                                                                              'Available' => 0
                                                                                                                             ));
                         }
                     }

                     // Check AM timeslot
                     if ($Record['NurseryRegistrationForAM'] == 1)
                     {
                         $ArrayRecords[$Record['NurseryRegistrationForDate']][0][$ArrayGradesIndex[$Record['NurseryRegistrationChildGrade']]]['Registrations']++;
                     }

                     // If there are some other timeslots
                     if ($NbOtherTimeslots > 0)
                     {
                         for($i = 0; $i < $NbOtherTimeslots; $i++)
                         {
                             if ($Record['NurseryRegistrationOtherTimeslots'] & pow(2, $i))
                             {
                                 $ArrayRecords[$Record['NurseryRegistrationForDate']][$i + 1][$ArrayGradesIndex[$Record['NurseryRegistrationChildGrade']]]['Registrations']++;
                             }
                         }
                     }

                     // Check PM timeslot
                     if ($Record['NurseryRegistrationForPM'] == 1)
                     {
                         $ArrayRecords[$Record['NurseryRegistrationForDate']][$iNbTotalTimeslots - 1][$ArrayGradesIndex[$Record['NurseryRegistrationChildGrade']]]['Registrations']++;
                     }
                 }

                 // Now, we compute the number of supervisors for registered children
                 foreach($ArrayRecords as $Date => $TimeslotStats)
                 {
                     foreach($TimeslotStats as $t => $GradeStats)
                     {
                         $fNbSupervisors = 0;
                         $iAvailable = $CapacityMax;

                         foreach($GradeStats as $g => $CurrentStats)
                         {
                             $fCurrentNbSupervisors = $CurrentStats['Registrations'] / $Capacities[$g]['Nb'];
                             $ArrayRecords[$Date][$t][$g]['Available'] = (max(1, ceil($fCurrentNbSupervisors)) * $Capacities[$g]['Nb']) - $CurrentStats['Registrations'];
                             $ArrayRecords[$Date][$t][$g]['Max'] = max(1, ceil($fCurrentNbSupervisors)) * $Capacities[$g]['Nb'];

                             $fNbSupervisors += $fCurrentNbSupervisors;
                             $iAvailable -= $CurrentStats['Registrations'];
                         }

                         if ($fNbSupervisors <= 1)
                         {
                             $iAvailableForEachGrade = $iAvailable / count($Capacities);
                             foreach($GradeStats as $g => $CurrentStats)
                             {
                                 if ($g %2)
                                 {
                                     $ArrayRecords[$Date][$t][$g]['Available'] = ceil($iAvailableForEachGrade);
                                 }
                                 else
                                 {
                                     $ArrayRecords[$Date][$t][$g]['Available'] = floor($iAvailableForEachGrade);
                                 }

                                 $ArrayRecords[$Date][$t][$g]['Max'] = $CurrentStats['Registrations'] + $ArrayRecords[$Date][$t][$g]['Available'];
                             }
                         }
                     }
                 }

                 return $ArrayRecords;
             }
         }
     }

     // ERROR
     return array();
 }
?>