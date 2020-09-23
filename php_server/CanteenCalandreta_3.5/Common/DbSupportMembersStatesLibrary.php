<?php
/* Copyright (C) 2007  STNA/7SQ (IVDS)
 *
 * This file is part of ASTRES.
 *
 * ASTRES is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ASTRES is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ASTRES; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Common module : library of database functions used for the SupportMembersStates table
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2004-01-17
 */


/**
 * Check if an alias exists in the Alias table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param $DbConnection            DB object    Object of the opened database connection
 * @param $SupportMemberStateID    String       ID of the supporter state searched [1..n]
 *
 * @return Boolean                 TRUE if the support member state exists, FALSE otherwise
 */
 function isExistingSupportMemberState($DbConnection, $SupportMemberStateID)
 {
     $DbResult = $DbConnection->query("SELECT SupportMemberStateID FROM SupportMembersStates
                                       WHERE SupportMemberStateID = $SupportMemberStateID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The support member state exists
             return TRUE;
         }
     }

     // The support member state doesn't exist
     return FALSE;
 }


/**
 * Give the ID of a supporter state thanks to its name
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-02-21
 *
 * @param $DbConnection              DB object  Object of the opened database connection
 * @param $SupportMemberStateName    String     Name of the supporter state searched
 *
 * @return Integer                              ID of the supporter state, 0 otherwise
 */
 function getSupportMemberStateID($DbConnection, $SupportMemberStateName)
 {
     $DbResult = $DbConnection->query("SELECT SupportMemberStateID FROM SupportMembersStates
                                      WHERE SupportMemberStateName = \"$SupportMemberStateName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["SupportMemberStateID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the name of a supporter state thanks to its ID
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-02-21
 *
 * @param $DbConnection              DB object  Object of the opened database connection
 * @param $SupportMemberStateID      String     ID of the supporter state searched
 *
 * @return String                               Name of the supporter state, empty string otherwise
 */
 function getSupportMemberStateName($DbConnection, $SupportMemberStateID)
 {
     $DbResult = $DbConnection->query("SELECT SupportMemberStateName FROM SupportMembersStates
                                      WHERE SupportMemberStateID = $SupportMemberStateID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["SupportMemberStateName"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Add a supporter state in the SupportMembersStates table if it doesn't exist
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-02-21
 *
 * @param $DbConnection                    DB object  Object of the opened database connection
 * @param $SupportMemberStateName          String     Name of the customer state
 * @param $SupportMemberStateDescription   String     Description of the supporter state
 *
 * @return Integer                         The primary key of the supporter state, 0 otherwise
 */
 function dbAddSupportMemberState($DbConnection, $SupportMemberStateName, $SupportMemberStateDescription = "")
 {
     if ($SupportMemberStateName != "")
     {
         // The supporter state is a new supporter state?
         $DbResult = $DbConnection->query("SELECT SupportMemberStateID FROM SupportMembersStates
                                          WHERE SupportMemberStateName = \"$SupportMemberStateName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // New supporter state : it's added
                 // For the auto-incrementation functionality
                 $id = getNewPrimaryKey($DbConnection, "SupportMembersStates", "SupportMemberStateID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO SupportMembersStates SET SupportMemberStateID = $id,
                                                      SupportMemberStateName = \"$SupportMemberStateName\",
                                                      SupportMemberStateDescription = \"$SupportMemberStateDescription\"");
                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // Old supporter state : we return its ID
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record["SupportMemberStateID"];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update a support member state in the SupportMembersStates table if it exists
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-04-06
 *
 * @param $DbConnection                    DB object    Object of the opened database connection
 * @param $SupportMemberStateID            Integer      ID of the customer state [1..n]
 * @param $SupportMemberStateName          String       Name of the customer state
 * @param $SupportMemberStateDescription   String       Description of the supporter state
 *
 * @return Integer                         The primary key of the support member state [1..n], 0 otherwise
 */
 function dbUpdateSupportMemberState($DbConnection, $SupportMemberStateID, $SupportMemberStateName, $SupportMemberStateDescription = NULL)
 {
     // The paramters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // The parameters are correct?
     if (($SupportMemberStateID < 1) || (!isInteger($SupportMemberStateID)))
     {
         // ERROR
         return 0;
     }

     if (!is_Null($SupportMemberStateName))
     {
         if ($SupportMemberStateName == "")
         {
             // ERROR
             return 0;
         }
         else
         {
             // The SupportMemberStateName field will be updated
             $ArrayParamsUpdate[] = "SupportMemberStateName = \"$SupportMemberStateName\"";
         }
     }

     if (!is_Null($SupportMemberStateDescription))
     {
         // The SupportMemberStateDescription field will be updated
         $ArrayParamsUpdate[] = "SupportMemberStateDescription = \"$SupportMemberStateDescription\"";
     }

     // Is the support member state the same as an other support member state?
     $DbResult = $DbConnection->query("SELECT SupportMemberStateID FROM SupportMembersStates
                                      WHERE SupportMemberStateID NOT IN ($SupportMemberStateID)
                                      AND SupportMemberStateName = \"$SupportMemberStateName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 0)
         {
             // we can update if there is at least 1 parameter
             if (count($ArrayParamsUpdate) > 0)
             {
                 $DbResult = $DbConnection->query("UPDATE SupportMembersStates SET ".implode(", ", $ArrayParamsUpdate)
                                                  ." WHERE SupportMemberStateID = $SupportMemberStateID");
                 if (!DB::isError($DbResult))
                 {
                     // Support member state updated
                     return $SupportMemberStateID;
                 }
             }
             else
             {
                 // The update isn't usefull
                 return $SupportMemberStateID;
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Delete a support member state, thanks to its ID if no support member linked to this state
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberStateID      Integer      ID of the support member state to delete [1..n]
 *
 * @return Boolean                   TRUE if the support member state is deleted, FALSE otherwise
 */
 function dbDeleteSupportMemberState($DbConnection, $SupportMemberStateID)
 {
     if ((!empty($SupportMemberStateID)) && ($SupportMemberStateID > 0))
     {
         // First, we check if there is no support member associated to this support member state
         $DbResult = $DbConnection->query("SELECT SupportMemberID FROM SupportMembers WHERE SupportMemberStateID = $SupportMemberStateID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // We delete the support member state
                 $DbResult = $DbConnection->query("DELETE FROM SupportMembersStates WHERE SupportMemberStateID = $SupportMemberStateID");
                 if (!DB::isError($DbResult))
                 {
                     // Support member state deleted
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Give the whole fields values of a support member state, thanks to his ID
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-04-06
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $SupportMemberStateID      Integer      ID of the support member state searched
 *
 * @return Mixed array               All fields values of a support member state if he exists,
 *                                   an empty array otherwise
 */
 function getSupportMemberStateInfos($DbConnection, $SupportMemberStateID)
 {
     $DbResult = $DbConnection->query("SELECT SupportMemberStateID, SupportMemberStateName, SupportMemberStateDescription
                                      FROM SupportMembersStates WHERE SupportMemberStateID = $SupportMemberStateID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             return $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
         }
     }

     // ERROR
     return array();
 }


/**
 * Give infos about all support members states
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-04-06
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $OrderBy              String       to sort the support member states
 *
 * @return Mixed array          All fields values of the found support members states,
 *                              an empty array if error
 */
 function getAllSupportMembersStatesInfos($DbConnection, $OrderBy = '')
 {
     if ($OrderBy == '')
     {
         $OrderBy = 'SupportMemberStateName';
     }

     $DbResult = $DbConnection->query("SELECT SupportMemberStateID, SupportMemberStateName, SupportMemberStateDescription
                                       FROM SupportMembersStates ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             // The result array
             $ArrayStates = array(
                                  'SupportMemberStateID' => array(),
                                  'SupportMemberStateName' => array(),
                                  'SupportMemberStateDescription' => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayStates['SupportMemberStateID'][] = $Record['SupportMemberStateID'];
                 $ArrayStates['SupportMemberStateName'][] = $Record['SupportMemberStateName'];
                 $ArrayStates['SupportMemberStateDescription'][] = $Record['SupportMemberStateDescription'];
             }

             return $ArrayStates;
         }
     }

     // ERROR
     return array();
 }
?>