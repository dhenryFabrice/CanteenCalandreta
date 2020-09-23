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
 * Common module : library of database functions used for the MeetingRooms and MeetingRoomsRegistrations tables
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-22
 */


/**
 * Check if a meeting room exists in the MeetingRooms table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $MeetingRoomID        Integer      ID of the meeting room searched [1..n]
 *
 * @return Boolean              TRUE if the meeting room exists, FALSE otherwise
 */
 function isExistingMeetingRoom($DbConnection, $MeetingRoomID)
 {
     $DbResult = $DbConnection->query("SELECT MeetingRoomID FROM MeetingRooms WHERE MeetingRoomID = $MeetingRoomID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The meeting room exists
             return TRUE;
         }
     }

     // The meeting room doesn't exist
     return FALSE;
 }


/**
 * Give the ID of a meeting room thanks to its name
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $MeetingRoomName      String       Name of the meeting room searched
 *
 * @return Integer              ID of the meeting room, 0 otherwise
 */
 function getMeetingRoomID($DbConnection, $MeetingRoomName)
 {
     $DbResult = $DbConnection->query("SELECT MeetingRoomID FROM MeetingRooms WHERE MeetingRoomName = \"$MeetingRoomName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["MeetingRoomID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the name of a meeting room thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $MeetingRoomID        String       ID of the meeting room searched
 *
 * @return String               Name of the meeting room, empty string otherwise
 */
 function getMeetingRoomName($DbConnection, $MeetingRoomID)
 {
     $DbResult = $DbConnection->query("SELECT MeetingRoomName FROM MeetingRooms WHERE MeetingRoomID = $MeetingRoomID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["MeetingRoomName"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Give the restrictions of a meeting room thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $MeetingRoomID        String       ID of the meeting room searched
 *
 * @return String               Name of the meeting room, empty string otherwise
 */
 function getMeetingRoomRestrictions($DbConnection, $MeetingRoomID)
 {
     $DbResult = $DbConnection->query("SELECT MeetingRoomRestrictions FROM MeetingRooms WHERE MeetingRoomID = $MeetingRoomID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["MeetingRoomRestrictions"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Add a meeting room in the MeetingRooms table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $MeetingRoomName               String       Name of the meeting room
 * @param $MeetingRoomRestrictions       String       Restrictions for registrations of the meeting rooms
 *                                                    (ex : time slots)
 * @param $MeetingRoomEmail              String       Email address of the meeting room
 * @param $MeetingRoomActivated          Integer      1 the meeting room is activaed, 0, desactivated [0..1]
 *
 * @return Integer                       The primary key of the meeting room [1..n], 0 otherwise
 */
 function dbAddMeetingRoom($DbConnection, $MeetingRoomName, $MeetingRoomRestrictions = '', $MeetingRoomEmail = '', $MeetingRoomActivated = 1)
 {
     if ((!empty($MeetingRoomName)) && ($MeetingRoomActivated >= 0))
     {
         // Check if the meeting room is a new meeting room
         $DbResult = $DbConnection->query("SELECT MeetingRoomID FROM MeetingRooms WHERE MeetingRoomName = \"$MeetingRoomName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 if (empty($MeetingRoomRestrictions))
                 {
                     $MeetingRoomRestrictions = "";
                 }
                 else
                 {
                     $MeetingRoomRestrictions = ", MeetingRoomRestrictions = \"$MeetingRoomRestrictions\"";
                 }

                 if (empty($MeetingRoomEmail))
                 {
                     $MeetingRoomEmail = "";
                 }
                 else
                 {
                     $MeetingRoomEmail = ", MeetingRoomEmail = \"$MeetingRoomEmail\"";
                 }

                 // It's a new meeting room
                 $id = getNewPrimaryKey($DbConnection, "MeetingRooms", "MeetingRoomID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO MeetingRooms SET MeetingRoomID = $id, MeetingRoomName = \"$MeetingRoomName\",
                                                       MeetingRoomActivated = $MeetingRoomActivated $MeetingRoomRestrictions $MeetingRoomEmail");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The meeting room already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['MeetingRoomID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing meeting room in the MeetingRooms table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $MeetingRoomID                 Integer      ID of the meeting room to update [1..n]
 * @param $MeetingRoomName               String       Name of the meeting room
 * @param $MeetingRoomRestrictions       String       Restrictions for registrations of the meeting rooms
 *                                                    (ex : time slots)
 * @param $MeetingRoomEmail              String       Email address of the meeting room
 * @param $MeetingRoomActivated          Integer      If the meeting room is activated or not [0..1]
 *
 * @return Integer                       The primary key of the event type [1..n], 0 otherwise
 */
 function dbUpdateMeetingRoom($DbConnection, $MeetingRoomID, $MeetingRoomName = NULL, $MeetingRoomRestrictions = NULL, $MeetingRoomEmail = NULL, $MeetingRoomActivated = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($MeetingRoomID < 1) || (!isInteger($MeetingRoomID)))
     {
         // ERROR
         return 0;
     }

     // Check if the MeetingRoomName is valide
     if (!is_null($MeetingRoomName))
     {
         if (empty($MeetingRoomName))
         {
             return 0;
         }
         else
         {
             // The EventTypeName field will be updated
             $ArrayParamsUpdate[] = "EventTypeName = \"$EventTypeName\"";
         }
     }

     if (!is_Null($MeetingRoomRestrictions))
     {
         // The MeetingRoomRestrictions field will be updated
         $ArrayParamsUpdate[] = "MeetingRoomRestrictions = \"$MeetingRoomRestrictions\"";
     }

     if (!is_Null($MeetingRoomEmail))
     {
         // The MeetingRoomEmail field will be updated
         $ArrayParamsUpdate[] = "MeetingRoomEmail = \"$MeetingRoomEmail\"";
     }

     // Check if the MeetingRoomActivated is valide
     if (!is_null($MeetingRoomActivated))
     {
         if ($MeetingRoomActivated >= 0)
         {
             // The MeetingRoomActivated field will be updated
             $ArrayParamsUpdate[] = "MeetingRoomActivated = $MeetingRoomActivated";
         }
         else
         {
             return 0;
         }
     }

     // Here, the parameters are correct, we check if the meeting room exists
     if (isExistingMeetingRoom($DbConnection, $MeetingRoomID))
     {
         // We check if the meeting room name is unique
         $DbResult = $DbConnection->query("SELECT MeetingRoomID FROM MeetingRooms WHERE EventTypeName = \"$EventTypeName\"
                                           AND MeetingRoomID <> $MeetingRoomID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The meeting room exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE MeetingRooms SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE MeetingRoomID = $MeetingRoomID");
                     if (!DB::isError($DbResult))
                     {
                         // Meeting room updated
                         return $MeetingRoomID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $MeetingRoomID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Delete a meeting room, thanks to its ID if no registration linked to this meeting room
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $MeetingRoomID             Integer      ID of the meeting room to delete [1..n]
 *
 * @return Boolean                   TRUE if the meeting room is deleted, FALSE otherwise
 */
 function dbDeleteMeetingRoom($DbConnection, $MeetingRoomID)
 {
     if ((!empty($EventTypeID)) && ($EventTypeID > 0))
     {
         // First, we check if there is no event associated to this event type
         $DbResult = $DbConnection->query("SELECT MeetingRoomRegistrationID FROM MeetingRoomsRegistrations WHERE MeetingRoomID = $MeetingRoomID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // We delete the event type
                 $DbResult = $DbConnection->query("DELETE FROM MeetingRooms WHERE MeetingRoomID = $MeetingRoomID");
                 if (!DB::isError($DbResult))
                 {
                     // Event type deleted
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Get meeting rooms filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the meeting rooms
 * @param $OrderBy                  String                 Criteria used to sort the meeting rooms. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of meeting rooms per page to return [1..n]
 *
 * @return Array of String                                 List of meeting rooms filtered, an empty array otherwise
 */
 function dbSearchMeetingRoom($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find meeting rooms
     $Select = "SELECT mr.MeetingRoomID, mr.MeetingRoomName, mr.MeetingRoomEmail, mr.MeetingRoomActivated";
     $From = "FROM MeetingRooms mr";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< MeetingRoomID field >>>
         if ((array_key_exists("MeetingRoomID", $ArrayParams)) && (count($ArrayParams["MeetingRoomID"]) > 0))
         {
             $Where .= " AND mr.MeetingRoomID IN ".constructSQLINString($ArrayParams["MeetingRoomID"]);
         }

         // <<< MeetingRoomName field >>>
         if ((array_key_exists("MeetingRoomName", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomName"])))
         {
             $Where .= " AND mr.MeetingRoomName LIKE \"".$ArrayParams["MeetingRoomName"]."\"";
         }

         // <<< MeetingRoomEmail field >>>
         if ((array_key_exists("MeetingRoomEmail", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomEmail"])))
         {
             $Where .= " AND mr.MeetingRoomEmail LIKE \"".$ArrayParams["MeetingRoomEmail"]."\"";
         }

         // <<< MeetingRoomActivated field >>>
         if ((array_key_exists("MeetingRoomActivated", $ArrayParams)) && (count($ArrayParams["MeetingRoomActivated"]) > 0))
         {
             $Where .= " AND mr.MeetingRoomActivated IN ".constructSQLINString($ArrayParams["MeetingRoomActivated"]);
         }
     }

     // We take into account the page and the number of event registrations per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY MeetingRoomID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "MeetingRoomID" => array(),
                                   "MeetingRoomName" => array(),
                                   "MeetingRoomEmail" => array(),
                                   "MeetingRoomActivated" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["MeetingRoomID"][] = $Record["MeetingRoomID"];
                 $ArrayRecords["MeetingRoomName"][] = $Record["MeetingRoomName"];
                 $ArrayRecords["MeetingRoomEmail"][] = $Record["MeetingRoomEmail"];
                 $ArrayRecords["MeetingRoomActivated"][] = $Record["MeetingRoomActivated"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of meeting rooms filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the meeting rooms
 *
 * @return Integer              Number of the meeting rooms found, 0 otherwise
 */
 function getNbdbSearchMeetingRoom($DbConnection, $ArrayParams)
 {
     // SQL request to find event types
     $Select = "SELECT mr.MeetingRoomID";
     $From = "FROM MeetingRooms mr";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< MeetingRoomID field >>>
         if ((array_key_exists("MeetingRoomID", $ArrayParams)) && (count($ArrayParams["MeetingRoomID"]) > 0))
         {
             $Where .= " AND mr.MeetingRoomID IN ".constructSQLINString($ArrayParams["MeetingRoomID"]);
         }

         // <<< MeetingRoomName field >>>
         if ((array_key_exists("MeetingRoomName", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomName"])))
         {
             $Where .= " AND mr.MeetingRoomName LIKE \"".$ArrayParams["MeetingRoomName"]."\"";
         }

         // <<< MeetingRoomEmail field >>>
         if ((array_key_exists("MeetingRoomEmail", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomEmail"])))
         {
             $Where .= " AND mr.MeetingRoomEmail LIKE \"".$ArrayParams["MeetingRoomEmail"]."\"";
         }

         // <<< MeetingRoomActivated field >>>
         if ((array_key_exists("MeetingRoomActivated", $ArrayParams)) && (count($ArrayParams["MeetingRoomActivated"]) > 0))
         {
             $Where .= " AND mr.MeetingRoomActivated IN ".constructSQLINString($ArrayParams["MeetingRoomActivated"]);
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY MeetingRoomID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Check if a meeting room registration exists in the MeetingRoomsRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                     DB object    Object of the opened database connection
 * @param $MeetingRoomRegistrationID        Integer      ID of the meeting room registration searched [1..n]
 *
 * @return Boolean                          TRUE if the meeting room registration exists, FALSE otherwise
 */
 function isExistingMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT MeetingRoomRegistrationID FROM MeetingRoomsRegistrations WHERE MeetingRoomRegistrationID = $MeetingRoomRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The meeting room registration exists
             return TRUE;
         }
     }

     // The meeting room registration doesn't exist
     return FALSE;
 }


/**
 * Check if a meeting room is available for given dates/hours
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                          DB object    Object of the opened database connection
 * @param $MeetingRoomID                         Integer      ID of the meeting room to check if available [1..n]
 * @param $MeetingRoomRegistrationStartDate      DateTime     Start date/time to check (yyyy-mm-dd hh:mm:ss)
 * @param $MeetingRoomRegistrationEndDate        DateTime     End date/time to check (yyyy-mm-dd hh:mm:ss)
 *
 * @return Boolean                               TRUE if the meeting room is available for start date and end date,
                                                 FALSE otherwise
 */
 function dbCheckMeetingRoomIsAvailable($DbConnection, $MeetingRoomID, $MeetingRoomRegistrationStartDate, $MeetingRoomRegistrationEndDate)
 {
     if ($MeetingRoomID > 0)
     {
         $DbResult = $DbConnection->query("SELECT MeetingRoomRegistrationID
                                           FROM MeetingRoomsRegistrations
                                           WHERE MeetingRoomID = $MeetingRoomID
                                           AND ((MeetingRoomRegistrationStartDate BETWEEN \"$MeetingRoomRegistrationStartDate\" AND \"$MeetingRoomRegistrationEndDate\")
                                           OR (MeetingRoomRegistrationEndDate BETWEEN \"$MeetingRoomRegistrationStartDate\" AND \"$MeetingRoomRegistrationEndDate\")
                                           OR (\"$MeetingRoomRegistrationStartDate\" BETWEEN MeetingRoomRegistrationStartDate AND MeetingRoomRegistrationEndDate)
                                           OR (\"$MeetingRoomRegistrationEndDate\" BETWEEN MeetingRoomRegistrationStartDate AND MeetingRoomRegistrationEndDate))");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Meeting room available
                 return TRUE;
             }
             else
             {
                 // Meeting room not available
                 return FALSE;
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Add a meeting room registration in the MeetingRoomsRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                          DB object    Object of the opened database connection
 * @param $MeetingRoomRegistrationDate           DateTime     Creation date of the meeting room registration (yyyy-mm-dd hh:mm:ss)
 * @param $SupportMemberID                       Integer      ID of the supporter, author of the meeting room registration [1..n]
 * @param $MeetingRoomRegistrationTitle          String       Title of the meeting room registration
 * @param $MeetingRoomRegistrationStartDate      DateTime     Start date of the meeting room registration (yyyy-mm-dd hh:mm:ss)
 * @param $MeetingRoomRegistrationEndDate        DateTime     End date of the meeting room registration (yyyy-mm-dd hh:mm:ss)
 * @param $MeetingRoomID                         Integer      ID of the meeting room of the registration [1..n]
 * @param $MeetingRoomRegistrationMailingList    String       Mailing-list to notify
 * @param $MeetingRoomRegistrationDescription    String       Description of the meeting room registration
 * @param $EventID                               Integer      ID of the event linked to the registration [1..n] or NULL
 *
 * @return Integer                               The primary key of the meeting room registration [1..n], 0 otherwise
 */
 function dbAddMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationDate, $SupportMemberID, $MeetingRoomRegistrationTitle, $MeetingRoomRegistrationStartDate, $MeetingRoomRegistrationEndDate, $MeetingRoomID, $MeetingRoomRegistrationMailingList = NULL, $MeetingRoomRegistrationDescription = NULL, $EventID = NULL)
 {
     if ((!empty($MeetingRoomRegistrationTitle)) && ($SupportMemberID > 0) && ($MeetingRoomID > 0))
     {
         // Check if the MeetingRoomRegistrationStartDate is valide
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $MeetingRoomRegistrationStartDate) == 0)
         {
             return 0;
         }
         else
         {
             $StartDate = ", MeetingRoomRegistrationStartDate = \"$MeetingRoomRegistrationStartDate\"";
         }

         // Check if the MeetingRoomRegistrationEndDate is valide
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $MeetingRoomRegistrationEndDate) == 0)
         {
             return 0;
         }
         else
         {
             $EndDate = ", MeetingRoomRegistrationEndDate = \"$MeetingRoomRegistrationEndDate\"";
         }

         if (empty($MeetingRoomRegistrationMailingList))
         {
             $MeetingRoomRegistrationMailingList = "";
         }
         else
         {
             $MeetingRoomRegistrationMailingList = ", MeetingRoomRegistrationMailingList = \"$MeetingRoomRegistrationMailingList\"";
         }

         if (empty($MeetingRoomRegistrationDescription))
         {
             $MeetingRoomRegistrationDescription = "";
         }
         else
         {
             $MeetingRoomRegistrationDescription = ", MeetingRoomRegistrationDescription = \"$MeetingRoomRegistrationDescription\"";
         }

         // Check if the EventID is valide
         if (!is_Null($EventID))
         {
             if ($EventID < 0)
             {
                 return 0;
             }
             else
             {
                 if ($EventID == 0)
                 {
                     // No value selected
                     $EventID = ", EventID = NULL";
                 }
                 else
                 {
                     // Value selected
                     $EventID = ", EventID = $EventID";
                 }
             }
         }
         else
         {
             // The field isn't entered
             $EventID = ", EventID = NULL";
         }

         // We check if the meeitng room is available
         if (dbCheckMeetingRoomIsAvailable($DbConnection, $MeetingRoomID, $MeetingRoomRegistrationStartDate, $MeetingRoomRegistrationEndDate))
         {
             // It's a new meeting room registration
             $id = getNewPrimaryKey($DbConnection, "MeetingRoomsRegistrations", "MeetingRoomRegistrationID");
             if ($id != 0)
             {
                 $DbResult = $DbConnection->query("INSERT INTO MeetingRoomsRegistrations SET MeetingRoomRegistrationID = $id,
                                                   MeetingRoomRegistrationDate = \"$MeetingRoomRegistrationDate\",
                                                   SupportMemberID = $SupportMemberID, MeetingRoomRegistrationTitle = \"$MeetingRoomRegistrationTitle\",
                                                   MeetingRoomID = $MeetingRoomID $StartDate $EndDate $MeetingRoomRegistrationMailingList
                                                   $MeetingRoomRegistrationDescription $EventID");

                 if (!DB::isError($DbResult))
                 {
                     return $id;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing event in the MeetingRoomsRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                          DB object    Object of the opened database connection
 * @param $MeetingRoomRegistrationID             Integer      ID of the meeting room registration to update [1..n]
 * @param $MeetingRoomRegistrationDate           DateTime     Creation date of the meeting room registration (yyyy-mm-dd hh:mm:ss)
 * @param $SupportMemberID                       Integer      ID of the supporter, author of the meeting room registration [1..n]
 * @param $MeetingRoomRegistrationTitle          String       Title of the meeting room registration
 * @param $MeetingRoomRegistrationStartDate      DateTime     Start date of the meeting room registration (yyyy-mm-dd hh:mm:ss)
 * @param $MeetingRoomRegistrationEndDate        DateTime     End date of the meeting room registration (yyyy-mm-dd hh:mm:ss)
 * @param $MeetingRoomID                         Integer      ID of the meeting room of the registration [1..n]
 * @param $MeetingRoomRegistrationMailingList    String       Mailing-list to notify
 * @param $MeetingRoomRegistrationDescription    String       Description of the meeting room registration
 * @param $EventID                               Integer      ID of the event linked to the registration [1..n] or NULL
 *
 * @return Integer                               The primary key of the meeting room registration [1..n], 0 otherwise
 */
 function dbUpdateMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationID, $MeetingRoomRegistrationDate, $SupportMemberID, $MeetingRoomRegistrationTitle, $MeetingRoomRegistrationStartDate, $MeetingRoomRegistrationEndDate, $MeetingRoomID, $MeetingRoomRegistrationMailingList = NULL, $MeetingRoomRegistrationDescription = NULL, $EventID = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($MeetingRoomRegistrationID < 1) || (!isInteger($MeetingRoomRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the MeetingRoomRegistrationDate is valide
     if (!is_null($MeetingRoomRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $MeetingRoomRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The MeetingRoomRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "MeetingRoomRegistrationDate = \"$MeetingRoomRegistrationDate\"";
         }
     }

     if (!is_null($MeetingRoomID))
     {
         if (($MeetingRoomID < 1) || (!isInteger($MeetingRoomID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "MeetingRoomID = $MeetingRoomID";
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

     if (!is_Null($MeetingRoomRegistrationTitle))
     {
         if (empty($MeetingRoomRegistrationTitle))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The MeetingRoomRegistrationTitle field will be updated
             $ArrayParamsUpdate[] = "MeetingRoomRegistrationTitle = \"$MeetingRoomRegistrationTitle\"";
         }
     }

     // Check if the MeetingRoomRegistrationStartDate is valide
     if (!is_null($MeetingRoomRegistrationStartDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $MeetingRoomRegistrationStartDate) == 0)
         {
             return 0;
         }
         else
         {
             // The MeetingRoomRegistrationStartDate field will be updated
             $ArrayParamsUpdate[] = "MeetingRoomRegistrationStartDate = \"$MeetingRoomRegistrationStartDate\"";
         }
     }

     // Check if the MeetingRoomRegistrationEndDate is valide
     if (!is_null($MeetingRoomRegistrationEndDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $MeetingRoomRegistrationEndDate) == 0)
         {
             return 0;
         }
         else
         {
             // The MeetingRoomRegistrationEndDate field will be updated
             $ArrayParamsUpdate[] = "MeetingRoomRegistrationEndDate = \"$MeetingRoomRegistrationEndDate\"";
         }
     }

     if (!is_Null($MeetingRoomRegistrationMailingList))
     {
         // The MeetingRoomRegistrationMailingList field will be updated
         $ArrayParamsUpdate[] = "MeetingRoomRegistrationMailingList = \"$MeetingRoomRegistrationMailingList\"";
     }

     if (!is_Null($MeetingRoomRegistrationDescription))
     {
         // The MeetingRoomRegistrationDescription field will be updated
         $ArrayParamsUpdate[] = "MeetingRoomRegistrationDescription = \"$MeetingRoomRegistrationDescription\"";
     }

     if (!is_Null($EventID))
     {
         if (($EventID < 0) || (!isInteger($EventID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The EventID field will be updated
             if ($EventID == 0)
             {
                 // No value selected
                 $ArrayParamsUpdate[] = "EventID = NULL";
             }
             else
             {
                 $ArrayParamsUpdate[] = "EventID = $EventID";
             }
         }
     }

     // Here, the parameters are correct, we check if the meeting room registration exists
     if (isExistingMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationID))
     {
         // We get start date and end date of the registration
         if ((empty($MeetingRoomRegistrationStartDate)) || (empty($MeetingRoomRegistrationEndDate)) || (empty($MeetingRoomID)))
         {
             $RecordRegistration = getTableRecordInfos($DbConnection, 'MeetingRoomsRegistrations', $MeetingRoomRegistrationID);

             $MeetingRoomRegistrationStartDate = $RecordRegistration['MeetingRoomRegistrationStartDate'];
             $MeetingRoomRegistrationEndDate = $RecordRegistration['MeetingRoomRegistrationEndDate'];
             $MeetingRoomID = $RecordRegistration['MeetingRoomID'];
         }

         // We check if the meeting room is available
         $DbResult = $DbConnection->query("SELECT MeetingRoomRegistrationID
                                           FROM MeetingRoomsRegistrations
                                           WHERE MeetingRoomID = $MeetingRoomID
                                           AND ((MeetingRoomRegistrationStartDate BETWEEN \"$MeetingRoomRegistrationStartDate\" AND \"$MeetingRoomRegistrationEndDate\")
                                           OR (MeetingRoomRegistrationEndDate BETWEEN \"$MeetingRoomRegistrationStartDate\" AND \"$MeetingRoomRegistrationEndDate\")
                                           OR (\"$MeetingRoomRegistrationStartDate\" BETWEEN MeetingRoomRegistrationStartDate AND MeetingRoomRegistrationEndDate)
                                           OR (\"$MeetingRoomRegistrationEndDate\" BETWEEN MeetingRoomRegistrationStartDate AND MeetingRoomRegistrationEndDate))
                                           AND MeetingRoomRegistrationID <> $MeetingRoomRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The meeting room is available : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE MeetingRoomsRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE MeetingRoomRegistrationID = $MeetingRoomRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Meeting room registration updated
                         return $MeetingRoomRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $MeetingRoomRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Check if a meeting room registration is closed, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $MeetingRoomRegistrationID     Integer      ID of the meeting room registration to check [1..n]
 *
 * @return Boolean                       TRUE if the meeting room registration is closed, FALSE otherwise
 */
 function isMeetingRoomRegistrationClosed($DbConnection, $MeetingRoomRegistrationID)
 {
     if ($MeetingRoomRegistrationID > 0)
     {
         $DbResult = $DbConnection->query("SELECT MeetingRoomRegistrationID, MeetingRoomRegistrationStartDate, MeetingRoomRegistrationEndDate
                                           FROM MeetingRoomsRegistrations WHERE MeetingRoomRegistrationID = $MeetingRoomRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 if (strtotime($Record['MeetingRoomRegistrationStartDate']) >= strtotime(date('Y-m-d H:i:s')))
                 {
                     return FALSE;
                 }
                 else
                 {
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return TRUE;
 }


/**
 * Delete a meeting room registration, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                 DB object    Object of the opened database connection
 * @param $MeetingRoomRegistrationID    Integer      ID of the meeting room registration to delete [1..n]
 *
 * @return Boolean                      TRUE if the meeting room registration is deleted if it exists,
 *                                      FALSE otherwise
 */
 function dbDeleteMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationID)
 {
     // The parameters are correct?
     if ($MeetingRoomRegistrationID > 0)
     {
         // Delete the meeting room registration in the table
         $DbResult = $DbConnection->query("DELETE FROM MeetingRoomsRegistrations WHERE MeetingRoomRegistrationID = $MeetingRoomRegistrationID");
         if (!DB::isError($DbResult))
         {
             // Meeting room registration deleted
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Get meeting rooms registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the meeting rooms registrations
 * @param $OrderBy                  String                 Criteria used to sort the meeting rooms registrations. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of meeting rooms registrations per page to return [1..n]
 *
 * @return Array of String                                 List of meeting rooms registrations filtered, an empty array otherwise
 */
 function dbSearchMeetingRoomRegistration($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find events
     $Select = "SELECT mrr.MeetingRoomRegistrationID, mrr.MeetingRoomRegistrationDate, mrr.MeetingRoomRegistrationTitle, mrr.MeetingRoomRegistrationStartDate,
                mrr.MeetingRoomRegistrationEndDate, mrr.MeetingRoomRegistrationMailingList, sm.SupportMemberID, sm.SupportMemberLastname, sm.SupportMemberFirstname,
                mr.MeetingRoomID, mr.MeetingRoomName, e.EventID, e.EventTitle";
     $From = "FROM MeetingRooms mr LEFT JOIN MeetingRoomsRegistrations mrr ON (mr.MeetingRoomID = mrr.MeetingRoomID) LEFT JOIN Events e ON (mrr.EventID = e.EventID)
              LEFT JOIN SupportMembers sm ON (mrr.SupportMemberID = sm.SupportMemberID)";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {

         // <<< MeetingRoomRegistrationID field >>>
         if ((array_key_exists("MeetingRoomRegistrationID", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomRegistrationID"])))
         {
             if (is_array($ArrayParams["MeetingRoomRegistrationID"]))
             {
                 $Where .= " AND mrr.MeetingRoomRegistrationID IN ".constructSQLINString($ArrayParams["MeetingRoomRegistrationID"]);
             }
             else
             {
                 $Where .= " AND mrr.MeetingRoomRegistrationID = ".$ArrayParams["MeetingRoomRegistrationID"];
             }
         }

         // <<< MeetingRoomRegistrationTitle field >>>
         if ((array_key_exists("MeetingRoomRegistrationTitle", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomRegistrationTitle"])))
         {
             $Where .= " AND mrr.MeetingRoomRegistrationTitle LIKE \"".$ArrayParams["MeetingRoomRegistrationTitle"]."\"";
         }

         // <<< Meeting room registration still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // An activated event is an event with a start date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND mrr.MeetingRoomRegistrationStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         if ((array_key_exists("MeetingRoomRegistrationStartDate", $ArrayParams)) && (count($ArrayParams["MeetingRoomRegistrationStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND mrr.MeetingRoomRegistrationStartDate ".$ArrayParams["MeetingRoomRegistrationStartDate"][1]." \"".$ArrayParams["MeetingRoomRegistrationStartDate"][0]."\"";
         }

         if ((array_key_exists("MeetingRoomRegistrationEndDate", $ArrayParams)) && (count($ArrayParams["MeetingRoomRegistrationEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND mrr.MeetingRoomRegistrationEndDate ".$ArrayParams["MeetingRoomRegistrationEndDate"][1]." \"".$ArrayParams["MeetingRoomRegistrationEndDate"][0]."\"";
         }

         // <<< MeetingRoomID field >>>
         if ((array_key_exists("MeetingRoomID", $ArrayParams)) && (count($ArrayParams["MeetingRoomID"]) > 0))
         {
             $Where .= " AND mr.MeetingRoomID IN ".constructSQLINString($ArrayParams["MeetingRoomID"]);
         }

         // <<< MeetingRoomName field >>>
         if ((array_key_exists("MeetingRoomName", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomName"])))
         {
             $Where .= " AND mr.MeetingRoomName LIKE \"".$ArrayParams["MeetingRoomName"]."\"";
         }

         // <<< SupportMemberID >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (count($ArrayParams["SupportMemberID"]) > 0))
         {
             $Where .= " AND sm.SupportMemberID IN ".constructSQLINString($ArrayParams["SupportMemberID"]);
         }

         // <<< SupportMemberLastname field >>>
         if ((array_key_exists("SupportMemberLastname", $ArrayParams)) && (!empty($ArrayParams["SupportMemberLastname"])))
         {
             $Where .= " AND sm.SupportMemberLastname LIKE \"".$ArrayParams["SupportMemberLastname"]."\"";
         }

         // <<< Option : get activated meeting rooms >>>
         if (array_key_exists("MeetingRoomActivated", $ArrayParams))
         {
             $Where .= " AND mr.MeetingRoomActivated IN ".constructSQLINString($ArrayParams["MeetingRoomActivated"]);
         }
     }

     // We take into account the page and the number of meeting rooms registrations per page
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY MeetingRoomRegistrationID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "MeetingRoomRegistrationID" => array(),
                                   "MeetingRoomRegistrationDate" => array(),
                                   "MeetingRoomRegistrationTitle" => array(),
                                   "MeetingRoomRegistrationStartDate" => array(),
                                   "MeetingRoomRegistrationEndDate" => array(),
                                   "MeetingRoomRegistrationMailingList" => array(),
                                   "SupportMemberID" => array(),
                                   "Author" => array(),
                                   "MeetingRoomID" => array(),
                                   "MeetingRoomName" => array(),
                                   "EventID" => array(),
                                   "EventTitle" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["MeetingRoomRegistrationID"][] = $Record["MeetingRoomRegistrationID"];
                 $ArrayRecords["MeetingRoomRegistrationDate"][] = $Record["MeetingRoomRegistrationDate"];
                 $ArrayRecords["MeetingRoomRegistrationTitle"][] = $Record["MeetingRoomRegistrationTitle"];
                 $ArrayRecords["MeetingRoomRegistrationStartDate"][] = $Record["MeetingRoomRegistrationStartDate"];
                 $ArrayRecords["MeetingRoomRegistrationEndDate"][] = $Record["MeetingRoomRegistrationEndDate"];
                 $ArrayRecords["MeetingRoomRegistrationMailingList"][] = $Record["MeetingRoomRegistrationMailingList"];
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];

                 $sAuthor = '';
                 if (!empty($Record["SupportMemberID"]))
                 {
                     $sAuthor = $Record["SupportMemberLastname"].' '.$Record["SupportMemberFirstname"];
                 }

                 $ArrayRecords["Author"][] = $sAuthor;
                 $ArrayRecords["MeetingRoomID"][] = $Record["MeetingRoomID"];
                 $ArrayRecords["MeetingRoomName"][] = $Record["MeetingRoomName"];
                 $ArrayRecords["EventID"][] = $Record["EventID"];
                 $ArrayRecords["EventTitle"][] = $Record["EventTitle"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of meeting rooms registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the meeting rooms registrations
 *
 * @return Integer              Number of the meeting rooms regisrtations found, 0 otherwise
 */
 function getNbdbSearchMeetingRoomRegistration($DbConnection, $ArrayParams)
 {
     // SQL request to find meeting rooms registrations
     $Select = "SELECT mrr.MeetingRoomRegistrationID";
     $From = "FROM MeetingRooms mr LEFT JOIN MeetingRoomsRegistrations mrr ON (mr.MeetingRoomID = mrr.MeetingRoomID) LEFT JOIN Events e ON (mrr.EventID = e.EventID)
              LEFT JOIN SupportMembers sm ON (mrr.SupportMemberID = sm.SupportMemberID)";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {

         // <<< MeetingRoomRegistrationID field >>>
         if ((array_key_exists("MeetingRoomRegistrationID", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomRegistrationID"])))
         {
             if (is_array($ArrayParams["MeetingRoomRegistrationID"]))
             {
                 $Where .= " AND mrr.MeetingRoomRegistrationID IN ".constructSQLINString($ArrayParams["MeetingRoomRegistrationID"]);
             }
             else
             {
                 $Where .= " AND mrr.MeetingRoomRegistrationID = ".$ArrayParams["MeetingRoomRegistrationID"];
             }
         }

         // <<< MeetingRoomRegistrationTitle field >>>
         if ((array_key_exists("MeetingRoomRegistrationTitle", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomRegistrationTitle"])))
         {
             $Where .= " AND mrr.MeetingRoomRegistrationTitle LIKE \"".$ArrayParams["MeetingRoomRegistrationTitle"]."\"";
         }

         // <<< Meeting room registration still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // An activated event is an event with a start date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND mrr.MeetingRoomRegistrationStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         if ((array_key_exists("MeetingRoomRegistrationStartDate", $ArrayParams)) && (count($ArrayParams["MeetingRoomRegistrationStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND mrr.MeetingRoomRegistrationStartDate ".$ArrayParams["MeetingRoomRegistrationStartDate"][1]." \"".$ArrayParams["MeetingRoomRegistrationStartDate"][0]."\"";
         }

         if ((array_key_exists("MeetingRoomRegistrationEndDate", $ArrayParams)) && (count($ArrayParams["MeetingRoomRegistrationEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND mrr.MeetingRoomRegistrationEndDate ".$ArrayParams["MeetingRoomRegistrationEndDate"][1]." \"".$ArrayParams["MeetingRoomRegistrationEndDate"][0]."\"";
         }

         // <<< MeetingRoomID field >>>
         if ((array_key_exists("MeetingRoomID", $ArrayParams)) && (count($ArrayParams["MeetingRoomID"]) > 0))
         {
             $Where .= " AND mr.MeetingRoomID IN ".constructSQLINString($ArrayParams["MeetingRoomID"]);
         }

         // <<< MeetingRoomName field >>>
         if ((array_key_exists("MeetingRoomName", $ArrayParams)) && (!empty($ArrayParams["MeetingRoomName"])))
         {
             $Where .= " AND mr.MeetingRoomName LIKE \"".$ArrayParams["MeetingRoomName"]."\"";
         }

         // <<< SupportMemberID >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (count($ArrayParams["SupportMemberID"]) > 0))
         {
             $Where .= " AND sm.SupportMemberID IN ".constructSQLINString($ArrayParams["SupportMemberID"]);
         }

         // <<< SupportMemberLastname field >>>
         if ((array_key_exists("SupportMemberLastname", $ArrayParams)) && (!empty($ArrayParams["SupportMemberLastname"])))
         {
             $Where .= " AND sm.SupportMemberLastname LIKE \"".$ArrayParams["SupportMemberLastname"]."\"";
         }

         // <<< Option : get activated meeting rooms >>>
         if (array_key_exists("MeetingRoomActivated", $ArrayParams))
         {
             $Where .= " AND mr.MeetingRoomActivated IN ".constructSQLINString($ArrayParams["MeetingRoomActivated"]);
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY MeetingRoomREgistrationID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }
?>