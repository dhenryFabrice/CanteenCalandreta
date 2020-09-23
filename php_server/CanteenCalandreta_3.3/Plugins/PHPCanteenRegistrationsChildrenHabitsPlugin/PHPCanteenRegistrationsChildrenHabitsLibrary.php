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
 * PHP plugin canteen registrations children habits module : library of functions for this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-06-10
 */


//########################### DB FUNCTIONS ###########################
/**
 * Generate a new primary key to simulate the auto-incrementation
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-06-10
 *
 * @param $DbConnection               DB object    Object of the opened database connection
 * @param $Database                   String       Concerned database
 * @param $TableName                  String       The primary key is generated for this table
 * @param $PrimaryKeyFieldName        String       Name of the primary key field
 *
 * @return Integer                    ID generated, 0 otherwise
 */
 function getNewPrimaryKeyCRFH($DbConnection, $Database, $TableName, $PrimaryKeyFieldName)
 {
     $DbResult = $DbConnection->getOne("SELECT MAX($PrimaryKeyFieldName) FROM $Database.$TableName");
     if (!DB::isError($DbResult))
     {
         // Auto-incrementation
         return $DbResult + 1;
     }

     // ERROR
     return 0;
 }


//########################### ANALYSIS FUNCTIONS ###########################
/**
 * Add a canteen registration child's habit's profil in the table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-06-10
 *
 * @param $DbConnection               DB object    Object of the opened database connection
 * @param $ChildID                    Integer      ID of the concerned child [1..n]
 * @param $HabitType                  Const        Type of profil habits [1..n]
 * @param $HabitProfil                Integer      Value of the profil [0..n]
 * @param $HabitRate                  Integer      Rate of the profil [0..100]
 *
 * @return Boolean                    TRUE if the profil has been added, FALSE otherwise
 */
 function dbAddCanteenRegistrationChildHabitProfil($DbConnection, $ChildID, $HabitType, $HabitProfil, $HabitRate)
 {
     if (($ChildID > 0) && ($HabitType > 0) && ($HabitProfil >= 0) && ($HabitRate >= 0))
     {
         // Check if the profil is a new profil for the family
         $DbResult = $DbConnection->query("SELECT CanteenRegistrationChildHabitID FROM ".$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB']
                                          .".".$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE']
                                          ." WHERE ChildID = $ChildID AND CanteenRegistrationChildHabitProfil = $HabitProfil
                                          AND CanteenRegistrationChildHabitType = $HabitType");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new bank
                 $id = getNewPrimaryKeyCRFH($DbConnection, $GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB'],
                                            $GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE'],
                                            'CanteenRegistrationChildHabitID');

                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO ".$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB']."."
                                                      .$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE']
                                                      ." SET CanteenRegistrationChildHabitID = $id,
                                                      CanteenRegistrationChildHabitProfil = $HabitProfil,
                                                      CanteenRegistrationChildHabitRate = $HabitRate,
                                                      CanteenRegistrationChildHabitType = $HabitType, ChildID = $ChildID");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The profil already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['CanteenRegistrationChildHabitID'];
             }
         }

         if (!DB::isError($DbResult))
         {
             return true;
         }
     }

     return false;
 }


/**
 * Detect if the habit profil of canteen registrations of a child for a given period is
 * conforme to his previous habits
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2014-07-11 : don't take into account vacations days in the period
 *
 * @since 2014-06-11
 *
 * @param $DbConnection               DB object        Object of the opened database connection
 * @param $ChildID                    Integer          ID of the concerned child [1..n]
 * @param $HabitType                  Const            Type of profil habits [1..n]
 * @param $StartDate                  Date             Start date of the concerned period to compare (yyyy-mm-dd)
 * @param $EndDate                    Date             End date of the concerned period to compare (yyyy-mm-dd)
 * @param $NotValidDays               Array of Date    Dates not to take into account to compare profils of weeks
 *
 * @return Mixed value                TRUE if the profil is conforme to habits, an array with habits profils of the child,
 *                                    if no conformity is found, FALSE otherwise
 */
 function dbDetectNoConformityCanteenRegistrationChildHabit($DbConnection, $ChildID, $HabitType, $StartDate, $EndDate, $NotValidDays = array())
 {
     if (($ChildID > 0) && ($HabitType > 0) && (preg_match("[\d\d\d\d-\d\d-\d\d]", $StartDate) != 0)
         && (preg_match("[\d\d\d\d-\d\d-\d\d]", $EndDate) != 0))
     {
         // First, we get canteen registrations of the child for the concerned period
         $PeriodProfil = 0;
         $ArrayCanteenregistrations = getCanteenRegistrations($DbConnection, $StartDate, $EndDate, 'CanteenRegistrationForDate',
                                                              $ChildID);

         if ((isset($ArrayCanteenregistrations['CanteenRegistrationID']))
             && (count($ArrayCanteenregistrations['CanteenRegistrationID']) > 0))
         {
             foreach($ArrayCanteenregistrations['CanteenRegistrationID'] as $cr => $CanteenRegistrationID)
             {
                 $NumDay = date("N", strtotime($ArrayCanteenregistrations['CanteenRegistrationForDate'][$cr]));
                 $PeriodProfil += pow(2, $NumDay);
             }
         }

         // Compute the mask to use to compare profils because of not valided days
         $MaskToUse = 54;
         if (!empty($NotValidDays))
         {
             foreach($NotValidDays as $d => $CurrDay)
             {
                 $NumDay = date("N", strtotime($CurrDay));
                 $MaskToUse -= pow(2, $NumDay);
             }
         }

         // Next, we check if there is a same habit profil for the child
         $DbResult = $DbConnection->query("SELECT CanteenRegistrationChildHabitID
                                           FROM ".$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB']."."
                                           .$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE']
                                           ." WHERE ChildID = $ChildID AND CanteenRegistrationChildHabitType = $HabitType
                                           AND (CanteenRegistrationChildHabitProfil & $MaskToUse) = $PeriodProfil");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 // Same profil found : all is OK
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return TRUE;
             }
             else
             {
                 // We search profils for the child
                 $DbResult = $DbConnection->query("SELECT CanteenRegistrationChildHabitProfil, CanteenRegistrationChildHabitRate
                                                   FROM ".$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB']."."
                                                   .$GLOBALS['CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE']
                                                   ." WHERE ChildID = $ChildID AND CanteenRegistrationChildHabitType = $HabitType
                                                   ORDER BY CanteenRegistrationChildHabitRate DESC");
                 if (!DB::isError($DbResult))
                 {
                     $ArrayProfilRates = array(
                                               'CanteenRegistrationChildHabitProfil' => array(),
                                               'CanteenRegistrationChildHabitRate' => array()
                                              );

                     if ($DbResult->numRows() > 0)
                     {
                         while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                         {
                             $ArrayProfilRates['CanteenRegistrationChildHabitProfil'][] = $Record['CanteenRegistrationChildHabitProfil'];
                             $ArrayProfilRates['CanteenRegistrationChildHabitRate'][] = $Record['CanteenRegistrationChildHabitRate'];
                         }
                     }

                     // Return profils of the child
                     return $ArrayProfilRates;
                 }
             }
         }
     }

     return FALSE;
 }
?>
