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
 * Common module : library of database functions used for the EventTypes and Events tables
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2013-04-04
 */


/**
 * Check if an event type exists in the EventTypes table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-04
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $EventTypeID          Integer      ID of the event type searched [1..n]
 *
 * @return Boolean              TRUE if the event type exists, FALSE otherwise
 */
 function isExistingEventType($DbConnection, $EventTypeID)
 {
     $DbResult = $DbConnection->query("SELECT EventTypeID FROM EventTypes WHERE EventTypeID = $EventTypeID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The event type exists
             return TRUE;
         }
     }

     // The event type doesn't exist
     return FALSE;
 }


/**
 * Give the ID of an event type thanks to its name
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-04
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $EventTypeName        String       Name of the event type searched
 *
 * @return Integer              ID of the event type, 0 otherwise
 */
 function getEventTypeID($DbConnection, $EventTypeName)
 {
     $DbResult = $DbConnection->query("SELECT EventTypeID FROM EventTypes WHERE EventTypeName = \"$EventTypeName\"");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["EventTypeID"];
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the name of an event type thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-07-04
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $EventTypeID          String       ID of the event type searched
 *
 * @return String               Name of the event type, empty string otherwise
 */
 function getEventTypeName($DbConnection, $EventTypeID)
 {
     $DbResult = $DbConnection->query("SELECT EventTypeName FROM EventTypes WHERE EventTypeID = $EventTypeID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
             return $Record["EventTypeName"];
         }
     }

     // ERROR
     return "";
 }


/**
 * Add an event type in the EventTypes table
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-10-27 : EventTypeCategory is in [0..n] and not [1..n]
 *
 * @since 2013-04-04
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $EventTypeName                 String       Name of the even type
 * @param $EventTypeCategory             Integer      Category of the event type [1..n]
 *
 * @return Integer                       The primary key of the event type [1..n], 0 otherwise
 */
 function dbAddEventType($DbConnection, $EventTypeName, $EventTypeCategory)
 {
     if ((!empty($EventTypeName)) && ($EventTypeCategory >= 0))
     {
         // Check if the event type is a new event type
         $DbResult = $DbConnection->query("SELECT EventTypeID FROM EventTypes WHERE EventTypeName = \"$EventTypeName\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // It's a new even type
                 $id = getNewPrimaryKey($DbConnection, "EventTypes", "EventTypeID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO EventTypes SET EventTypeID = $id, EventTypeName = \"$EventTypeName\",
                                                      EventTypeCategory = $EventTypeCategory");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The even type already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['EventTypeID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing even type in the EventTypes table
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2015-10-12 : patch a bug in the SQL (EventTypes instead of Towns)
 *     - 2016-10-27 : EventTypeCategory is in [0..n] and not [1..n]
 *
 * @since 2013-04-04
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $EventTypeID                   Integer      ID of the even type to update [1..n]
 * @param $EventTypeName                 String       Name of the even type
 * @param $EventTypeCategory             Integer      Category of the event type [0..n]
 *
 * @return Integer                       The primary key of the event type [1..n], 0 otherwise
 */
 function dbUpdateEventType($DbConnection, $EventTypeID, $EventTypeName = NULL, $EventTypeCategory = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($EventTypeID < 1) || (!isInteger($EventTypeID)))
     {
         // ERROR
         return 0;
     }

     // Check if the EventTypeName is valide
     if (!is_null($EventTypeName))
     {
         if (empty($EventTypeName))
         {
             return 0;
         }
         else
         {
             // The EventTypeName field will be updated
             $ArrayParamsUpdate[] = "EventTypeName = \"$EventTypeName\"";
         }
     }

     // Check if the EventTypeCategory is valide
     if (!is_null($EventTypeCategory))
     {
         if ($EventTypeCategory >= 0)
         {
             // The EventTypeCategory field will be updated
             $ArrayParamsUpdate[] = "EventTypeCategory = $EventTypeCategory";
         }
         else
         {
             return 0;
         }
     }

     // Here, the parameters are correct, we check if the event type exists
     if (isExistingEventType($DbConnection, $EventTypeID))
     {
         // We check if the event type name is unique
         $DbResult = $DbConnection->query("SELECT EventTypeID FROM EventTypes WHERE EventTypeName = \"$EventTypeName\"
                                          AND EventTypeID <> $EventTypeID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The even type exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE EventTypes SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE EventTypeID = $EventTypeID");
                     if (!DB::isError($DbResult))
                     {
                         // Event type updated
                         return $EventTypeID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $EventTypeID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Delete an event type, thanks to its ID if no event linked to this type
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-27
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $EventTypeID               Integer      ID of the event type to delete [1..n]
 *
 * @return Boolean                   TRUE if the event type is deleted, FALSE otherwise
 */
 function dbDeleteEventType($DbConnection, $EventTypeID)
 {
     if ((!empty($EventTypeID)) && ($EventTypeID > 0))
     {
         // First, we check if there is no event associated to this event type
         $DbResult = $DbConnection->query("SELECT EventID FROM Events WHERE EventTypeID = $EventTypeID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // We delete the event type
                 $DbResult = $DbConnection->query("DELETE FROM EventTypes WHERE EventTypeID = $EventTypeID");
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
 * Get event types filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-27
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the event types
 * @param $OrderBy                  String                 Criteria used to sort the event types. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of even types per page to return [1..n]
 *
 * @return Array of String                                 List of event types filtered, an empty array otherwise
 */
 function dbSearchEventType($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find event types
     $Select = "SELECT et.EventTypeID, et.EventTypeName, et.EventTypeCategory";
     $From = "FROM EventTypes et";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< EventTypeID field >>>
         if ((array_key_exists("EventTypeID", $ArrayParams)) && (count($ArrayParams["EventTypeID"]) > 0))
         {
             $Where .= " AND et.EventTypeID IN ".constructSQLINString($ArrayParams["EventTypeID"]);
         }

         // <<< EventTypeName field >>>
         if ((array_key_exists("EventTypeName", $ArrayParams)) && (!empty($ArrayParams["EventTypeName"])))
         {
             $Where .= " AND et.EventTypeName LIKE \"".$ArrayParams["EventTypeName"]."\"";
         }

         // <<< EventTypeCategory field >>>
         if ((array_key_exists("EventTypeCategory", $ArrayParams)) && (count($ArrayParams["EventTypeCategory"]) > 0))
         {
             $Where .= " AND et.EventTypeCategory IN ".constructSQLINString($ArrayParams["EventTypeCategory"]);
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY EventTypeID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "EventTypeID" => array(),
                                   "EventTypeName" => array(),
                                   "EventTypeCategory" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["EventTypeID"][] = $Record["EventTypeID"];
                 $ArrayRecords["EventTypeName"][] = $Record["EventTypeName"];
                 $ArrayRecords["EventTypeCategory"][] = $Record["EventTypeCategory"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of event types filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-27
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the event types
 *
 * @return Integer              Number of the event registrations found, 0 otherwise
 */
 function getNbdbSearchEventType($DbConnection, $ArrayParams)
 {
     // SQL request to find event types
     $Select = "SELECT et.EventTypeID";
     $From = "FROM EventTypes et";
     $Where = " WHERE 1=1";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< EventTypeID field >>>
         if ((array_key_exists("EventTypeID", $ArrayParams)) && (count($ArrayParams["EventTypeID"]) > 0))
         {
             $Where .= " AND et.EventTypeID IN ".constructSQLINString($ArrayParams["EventTypeID"]);
         }

         // <<< EventTypeName field >>>
         if ((array_key_exists("EventTypeName", $ArrayParams)) && (!empty($ArrayParams["EventTypeName"])))
         {
             $Where .= " AND et.EventTypeName LIKE \"".$ArrayParams["EventTypeName"]."\"";
         }

         // <<< EventTypeCategory field >>>
         if ((array_key_exists("EventTypeCategory", $ArrayParams)) && (count($ArrayParams["EventTypeCategory"]) > 0))
         {
             $Where .= " AND et.EventTypeCategory IN ".constructSQLINString($ArrayParams["EventTypeCategory"]);
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY EventTypeID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Check if an event exists in the Events table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-04
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $EventID              Integer      ID of the event searched [1..n]
 *
 * @return Boolean              TRUE if the event exists, FALSE otherwise
 */
 function isExistingEvent($DbConnection, $EventID)
 {
     $DbResult = $DbConnection->query("SELECT EventID FROM Events WHERE EventID = $EventID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The event exists
             return TRUE;
         }
     }

     // The event doesn't exist
     return FALSE;
 }


/**
 * Add an event in the Events table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-04
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $EventDate                     DateTime     Creation date of the purpose (yyyy-mm-dd hh:mm:ss)
 * @param $SupportMemberID               Integer      ID of the supporter, author of the event [1..n]
 * @param $EventTitle                    String       Title of the event
 * @param $EventStartDate                Date         Start date of the event (yyyy-mm-dd)
 * @param $EventEndDate                  Date         End date of the event (yyyy-mm-dd)
 * @param $EventDescription              String       Description of the event
 * @param $EventTypeID                   Integer      ID of the event type of the event [1..n]
 * @param $TownID                        Integer      ID of the town of the event [1..n]
 * @param $EventMaxParticipants          Integer      Number of maximum participants to the event [0..n]
 * @param $EventRegistrationDelay        Integer      Registration delay to the event [0..n]
 * @param $EventStartTime                Time         Start time of the event (hh:mm:ss)
 * @param $EventEndTime                  Time         End time of the event (hh:mm:ss)
 * @param $EventClosingDate              Date         Closing date of the event (yyyy-mm-dd)
 * @param $ParentEventID                 Integer      ID of the parent event [1..n] or NULL
 *
 * @return Integer                       The primary key of the event [1..n], 0 otherwise
 */
 function dbAddEvent($DbConnection, $EventDate, $SupportMemberID, $EventTitle, $EventStartDate, $EventEndDate, $EventDescription, $EventTypeID, $TownID, $EventMaxParticipants = 1, $EventRegistrationDelay = 1, $EventStartTime = NULL, $EventEndTime = NULL, $EventClosingDate = NULL, $ParentEventID = NULL)
 {
     if ((!empty($EventTitle)) && ($EventMaxParticipants >= 0) && (!empty($EventDescription)) && ($EventTypeID > 0) && ($TownID > 0))
     {
         // Check if the event is a new event
         $DbResult = $DbConnection->query("SELECT EventID FROM Events WHERE EventTitle = \"$EventTitle\"");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the EventStartDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $EventStartDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $EventStartDate = ", EventStartDate = \"$EventStartDate\"";
                 }

                 // Check if the EventEndDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d]", $EventEndDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $EventEndDate = ", EventEndDate = \"$EventEndDate\"";
                 }

                 // Check if the EventStartTime is valide
                 if (!is_Null($EventStartTime))
                 {
                     if (preg_match("[\d\d:\d\d:\d\d]", $EventStartTime) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $EventStartTime = ", EventStartTime = \"$EventStartTime\"";
                     }
                 }
                 else
                 {
                     $EventStartTime = ", EventStartTime = NULL";
                 }

                 // Check if the EventEndTime is valide
                 if (!is_Null($EventEndTime))
                 {
                     if (preg_match("[\d\d:\d\d:\d\d]", $EventEndTime) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $EventEndTime = ", EventEndTime = \"$EventEndTime\"";
                     }
                 }
                 else
                 {
                     $EventEndTime = ", EventEndTime = NULL";
                 }

                 // Check if the EventClosingDate is valide
                 if (!is_Null($EventClosingDate))
                 {
                     if (preg_match("[\d\d\d\d-\d\d-\d\d]", $EventClosingDate) == 0)
                     {
                         return 0;
                     }
                     else
                     {
                         $EventClosingDate = ", EventClosingDate = \"$EventClosingDate\"";
                     }
                 }
                 else
                 {
                     $EventClosingDate = ", EventClosingDate = NULL";
                 }

                 // Check if the ParentEventID is valide
                 if (!is_Null($ParentEventID))
                 {
                     if ($ParentEventID < 0)
                     {
                         return 0;
                     }
                     else
                     {
                         if ($ParentEventID == 0)
                         {
                             // No value selected
                             $BackupParentID = NULL;
                             $ParentEventID = ", ParentEventID = NULL";
                         }
                         else
                         {
                             // Value selected
                             $BackupParentID = $ParentEventID;
                             $ParentEventID = ", ParentEventID = $ParentEventID";
                         }
                     }
                 }
                 else
                 {
                     // The field isn't entered
                     $BackupParentID = $ParentEventID;
                     $ParentEventID = ", ParentEventID = NULL";
                 }

                 // It's a new event
                 $id = getNewPrimaryKey($DbConnection, "Events", "EventID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO Events SET EventID = $id, EventDate = \"$EventDate\",
                                                       SupportMemberID = $SupportMemberID, EventTitle = \"$EventTitle\",
                                                       EventDescription = \"$EventDescription\",
                                                       EventMaxParticipants = $EventMaxParticipants,
                                                       EventRegistrationDelay = $EventRegistrationDelay, TownID = $TownID,
                                                       EventTypeID = $EventTypeID $EventStartDate $EventEndDate $EventStartTime
                                                       $EventEndTime $EventClosingDate $ParentEventID");
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
 * Update an existing event in the Events table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-05
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $EventID                       Integer      ID of the event to update [1..n]
 * @param $EventDate                     DateTime     Creation date of the purpose (yyyy-mm-dd hh:mm:ss)
 * @param $SupportMemberID               Integer      ID of the supporter, author of the event [1..n]
 * @param $EventTitle                    String       Title of the event
 * @param $EventStartDate                Date         Start date of the event (yyyy-mm-dd)
 * @param $EventEndDate                  Date         End date of the event (yyyy-mm-dd)
 * @param $EventDescription              String       Description of the event
 * @param $EventTypeID                   Integer      ID of the event type of the event [1..n]
 * @param $TownID                        Integer      ID of the town of the event [1..n]
 * @param $EventMaxParticipants          Integer      Number of maximum participants to the event [0..n]
 * @param $EventRegistrationDelay        Integer      Registration delay to the event [0..n]
 * @param $EventStartTime                Time         Start time of the event (hh:mm:ss)
 * @param $EventEndTime                  Time         End time of the event (hh:mm:ss)
 * @param $EventClosingDate              Date         Closing date of the event (yyyy-mm-dd)
 * @param $ParentEventID                 Integer      ID of the parent event [1..n] or NULL
 *
 * @return Integer                       The primary key of the event [1..n], 0 otherwise
 */
 function dbUpdateEvent($DbConnection, $EventID, $EventDate, $SupportMemberID, $EventTitle, $EventStartDate, $EventEndDate, $EventDescription, $EventTypeID, $TownID, $EventMaxParticipants = NULL, $EventRegistrationDelay = NULL, $EventStartTime = NULL, $EventEndTime = NULL, $EventClosingDate = NULL, $ParentEventID = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($EventID < 1) || (!isInteger($EventID)))
     {
         // ERROR
         return 0;
     }

     // Check if the EventDate is valide
     if (!is_null($EventDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventDate) == 0)
         {
             return 0;
         }
         else
         {
             // The EventDate field will be updated
             $ArrayParamsUpdate[] = "EventDate = \"$EventDate\"";
         }
     }

     if (!is_null($EventTypeID))
     {
         if (($EventTypeID < 1) || (!isInteger($EventTypeID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "EventTypeID = $EventTypeID";
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

     if (!is_null($TownID))
     {
         if (($TownID < 1) || (!isInteger($TownID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "TownID = $TownID";
         }
     }

     if (!is_Null($EventTitle))
     {
         if (empty($EventTitle))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The EventTitle field will be updated
             $ArrayParamsUpdate[] = "EventTitle = \"$EventTitle\"";
         }
     }

     // Check if the EventStartDate is valide
     if (!is_null($EventStartDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d]", $EventStartDate) == 0)
         {
             return 0;
         }
         else
         {
             // The EventStartDate field will be updated
             $ArrayParamsUpdate[] = "EventStartDate = \"$EventStartDate\"";
         }
     }

     // Check if the EventStartTime is valide
     if (!is_Null($EventStartTime))
     {
         if ($EventStartTime == '')
         {
             $ArrayParamsUpdate[] = "EventStartTime = NULL";
         }
         else
         {
             if (preg_match("[\d\d:\d\d:\d\d]", $EventStartTime) == 0)
             {
                 return 0;
             }
             else
             {
                 $ArrayParamsUpdate[] = "EventStartTime = \"$EventStartTime\"";
             }
         }
     }

     // Check if the EventEndDate is valide
     if (!is_null($EventEndDate))
     {
         if ($EventEndDate == '')
         {
             // The EventEndDate field will be updated
             $ArrayParamsUpdate[] = "EventEndDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $EventEndDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The EventEndDate field will be updated
                 $ArrayParamsUpdate[] = "EventEndDate = \"$EventEndDate\"";
             }
         }
     }

     // Check if the EventEndTime is valide
     if (!is_Null($EventEndTime))
     {
         if ($EventEndTime == '')
         {
             $ArrayParamsUpdate[] = "EventEndTime = NULL";
         }
         else
         {
             if (preg_match("[\d\d:\d\d:\d\d]", $EventEndTime) == 0)
             {
                 return 0;
             }
             else
             {
                 $ArrayParamsUpdate[] = "EventEndTime = \"$EventEndTime\"";
             }
         }
     }

     if (!is_Null($EventMaxParticipants))
     {
         if (($EventMaxParticipants < 0) || (!isInteger($EventMaxParticipants)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The EventMaxParticipants field will be updated
             $ArrayParamsUpdate[] = "EventMaxParticipants = $EventMaxParticipants";
         }
     }

     if (!is_Null($EventRegistrationDelay))
     {
         if (($EventRegistrationDelay < 0) || (!isInteger($EventRegistrationDelay)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The EventRegistrationDelay field will be updated
             $ArrayParamsUpdate[] = "EventRegistrationDelay = $EventRegistrationDelay";
         }
     }

     if (!is_null($EventDescription))
     {
         if (empty($EventDescription))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The EventDescription field will be updated
             $ArrayParamsUpdate[] = "EventDescription = \"$EventDescription\"";
         }
     }

     if (!is_null($EventClosingDate))
     {
         if (empty($EventClosingDate))
         {
             // The EventClosingDate field will be updated
             $ArrayParamsUpdate[] = "EventClosingDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d]", $EventClosingDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The EventClosingDate field will be updated
                 $ArrayParamsUpdate[] = "EventClosingDate = \"$EventClosingDate\"";
             }
         }
     }

     if (!is_Null($ParentEventID))
     {
         if (($ParentEventID < 0) || (!isInteger($ParentEventID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The ParentEventID field will be updated
             if ($ParentEventID == 0)
             {
                 // No value selected
                 $ArrayParamsUpdate[] = "ParentEventID = NULL";
             }
             else
             {
                 // Value selected : we check if the parent ID is <> event ID
                 if ($ParentEventID != $EventID)
                 {
                     $ArrayParamsUpdate[] = "ParentEventID = $ParentEventID";
                 }
             }
         }
     }

     // Here, the parameters are correct, we check if the event exists
     if (isExistingEvent($DbConnection, $EventID))
     {
         // We check if the event title is unique
         $DbResult = $DbConnection->query("SELECT EventID FROM Events WHERE EventTitle = \"$EventTitle\"
                                          AND EventID <> $EventID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The event entry exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE Events SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE EventID = $EventID");
                     if (!DB::isError($DbResult))
                     {
                         // Event updated
                         return $EventID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $EventID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Give the events tree or a part of this tree just for an event, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-04
 *
 * @param $DbConnection              DB object           Object of the opened database connection
 * @param $EventID                   Integer             ID of the event searched [1..n]
 * @param $EventActivated            Array of Integer    To filter activated or not events
 * @param $OrderBy                   String              Field used to order the events
 *
 * @return Mixed array               The events tree with some fields, an empty array otherwise
 */
 function getEventsTree($DbConnection, $EventID = NULL, $EventActivated = array(), $OrderBy = 'EventTitle')
 {
     $EventIDCondition = " AND e.ParentEventID IS NULL";
     if ((!is_null($EventID)) && ($EventID > 0))
     {
         $EventIDCondition = " AND e.EventID = $EventID";
     }

     $ActivatedCondition = '';
     if (count($EventActivated) > 0)
     {
         if ((in_array(0, $EventActivated)) && (in_array(1, $EventActivated)))
         {
             $ActivatedCondition = "";
         }
         elseif (in_array(0, $EventActivated))
         {
             $ActivatedCondition = " AND e.EventClosingDate IS NOT NULL";
         }
         elseif (in_array(1, $EventActivated))
         {
             $ActivatedCondition = " AND e.EventClosingDate IS NULL";
         }
     }

     if (empty($OrderBy))
     {
         // No order by : we use EventName
         $OrderBy = 'EventName';
     }

     // We get the events of the first tree level => ParentEventID = NULL
     $DbResult = $DbConnection->query("SELECT e.EventID, e.EventDate, e.EventTitle, e.EventStartDate, e.EventMaxParticipants,
                                      e.EventClosingDate, e.ParentEventID, et.EventTypeID, et.EventTypeName, et.EventTypeCategory
                                      FROM Events e, EventTypes et WHERE e.EventTypeID = et.EventTypeID $EventIDCondition
                                      $ActivatedCondition ORDER BY $OrderBy");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayEvents = array(
                                  "EventID" => array(),
                                  "EventDate" => array(),
                                  "EventTitle" => array(),
                                  "EventStartDate" => array(),
                                  "EventMaxParticipants" => array(),
                                  "EventClosingDate" => array(),
                                  "ParentEventID" => array(),
                                  "EventTypeID" => array(),
                                  "EventTypeName" => array(),
                                  "EventTypeCategory" => array(),
                                  "Level" => array()
                                 );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayEvents["EventID"][] = $Record["EventID"];
                 $ArrayEvents["EventDate"][] = $Record["EventDate"];
                 $ArrayEvents["EventTitle"][] = $Record["EventTitle"];
                 $ArrayEvents["EventStartDate"][] = $Record["EventStartDate"];
                 $ArrayEvents["EventMaxParticipants"][] = $Record["EventMaxParticipants"];
                 $ArrayEvents["EventClosingDate"][] = $Record["EventClosingDate"];
                 $ArrayEvents["ParentEventID"][] = $Record["ParentEventID"];
                 $ArrayEvents["EventTypeID"][] = $Record["EventTypeID"];
                 $ArrayEvents["EventTypeName"][] = $Record["EventTypeName"];
                 $ArrayEvents["EventTypeCategory"][] = $Record["EventTypeCategory"];
                 $ArrayEvents["Level"][] = 0;
             }

             $CurrentIndexEvent = 0;  // Point to the current event, in the array $ArrayEvents
             $ArrayEventsSize = $DbResult->numRows();
             while ($CurrentIndexEvent < $ArrayEventsSize)
             {
                 // We get the sublevels events of the current event
                 $DbResult = $DbConnection->query("SELECT e.EventID, e.EventDate, e.EventTitle, e.EventStartDate, e.EventMaxParticipants,
                                                  e.EventClosingDate, e.ParentEventID, et.EventTypeID, et.EventTypeName,
                                                  et.EventTypeCategory
                                                  FROM Events e, EventTypes et WHERE e.EventTypeID = et.EventTypeID AND ParentEventID = "
                                                  .$ArrayEvents["EventID"][$CurrentIndexEvent]
                                                  ." ORDER BY $OrderBy");
                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() > 0)
                     {
                         $CurrentLevel = $ArrayEvents["Level"][$CurrentIndexEvent] + 1;
                         $CurrentIndexToInsert = $CurrentIndexEvent;
                         while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                         {
                             $ArrayEvents["EventID"] = array_insertElement($ArrayEvents["EventID"], $Record["EventID"], $CurrentIndexToInsert);
                             $ArrayEvents["EventDate"] = array_insertElement($ArrayEvents["EventDate"], $Record["EventDate"], $CurrentIndexToInsert);
                             $ArrayEvents["EventTitle"] = array_insertElement($ArrayEvents["EventTitle"], $Record["EventTitle"], $CurrentIndexToInsert);
                             $ArrayEvents["EventStartDate"] = array_insertElement($ArrayEvents["EventStartDate"], $Record["EventStartDate"], $CurrentIndexToInsert);
                             $ArrayEvents["EventMaxParticipants"] = array_insertElement($ArrayEvents["EventMaxParticipants"], $Record["EventMaxParticipants"], $CurrentIndexToInsert);
                             $ArrayEvents["EventClosingDate"] = array_insertElement($ArrayEvents["EventClosingDate"], $Record["EventClosingDate"], $CurrentIndexToInsert);
                             $ArrayEvents["ParentEventID"] = array_insertElement($ArrayEvents["ParentEventID"], $Record["ParentEventID"], $CurrentIndexToInsert);
                             $ArrayEvents["EventTypeID"] = array_insertElement($ArrayEvents["EventTypeID"], $Record["EventTypeID"], $CurrentIndexToInsert);
                             $ArrayEvents["EventTypeName"] = array_insertElement($ArrayEvents["EventTypeName"], $Record["EventTypeName"], $CurrentIndexToInsert);
                             $ArrayEvents["EventTypeCategory"] = array_insertElement($ArrayEvents["EventTypeCategory"], $Record["EventTypeCategory"], $CurrentIndexToInsert);
                             $ArrayEvents["Level"] = array_insertElement($ArrayEvents["Level"], $CurrentLevel, $CurrentIndexToInsert);

                             $CurrentIndexToInsert++;   // To insert the next sublevel event after this sublevel event
                             $ArrayEventsSize++;        // Because we added a event in the array
                         }

                         // We go to the next event in the array
                         $CurrentIndexEvent++;
                     }
                     else
                     {
                         // We go to the next event in the array
                         $CurrentIndexEvent++;
                     }
                 }
                 else
                 {
                     // We go to the next event in the array
                     $CurrentIndexEvent++;
                 }
             }

             return $ArrayEvents;
         }
     }

     // ERROR
     return array();
 }


/**
 * Check if an event is closed, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-04
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $EventID                   Integer      ID of the event to check [1..n]
 *
 * @return Boolean                   TRUE if the event is closed, FALSE otherwise
 */
 function isEventClosed($DbConnection, $EventID)
 {
     if ($EventID > 0)
     {
         $DbResult = $DbConnection->query("SELECT EventID, EventClosingDate FROM Events WHERE EventID = $EventID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 if (is_null($Record['EventClosingDate']))
                 {
                     // No closing date, the event isn't closed
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
 * Delete a not closed event, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-12
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $EventID                   Integer      ID of the event to delete [1..n]
 *
 * @return Boolean                   TRUE if the event is deleted if it exists and not closed,
 *                                   FALSE otherwise
 */
 function dbDeleteEvent($DbConnection, $EventID)
 {
     // The parameters are correct?
     if ($EventID > 0)
     {
         $bContinue = FALSE;
         $RecordEvent = getTableRecordInfos($DbConnection, "Events", $EventID);
         if (isset($RecordEvent['EventID']))
         {
             if (empty($RecordEvent['EventClosingDate']))
             {
                 // No closing date for the given event
                 $bContinue = TRUE;

                 // We check if the event has closed child-event
                 $ArrayChildEvents = getEventsTree($DbConnection, $EventID);
                 if (isset($ArrayChildEvents['EventID']))
                 {
                     foreach($ArrayChildEvents['EventID'] as $e => $CurrentEventID)
                     {
                         if (!empty($ArrayChildEvents['EventClosingDate'][$e]))
                         {
                             // At least one child-event is closed
                             return FALSE;
                         }
                     }
                 }
                 else
                 {
                     $ArrayChildEvents['EventID'][] = $EventID;
                 }
             }
         }

         if ($bContinue)
         {
             // Delete the event (and its child-events) in the table
             $DbResult = $DbConnection->query("DELETE FROM Events WHERE EventID IN ".constructSQLINString($ArrayChildEvents['EventID']));
             if (!DB::isError($DbResult))
             {
                 // Event(s) deleted
                 return TRUE;
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Get events filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-09-09 : EventID criteria can be an array
 *
 * @since 2013-04-10
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the events
 * @param $OrderBy                  String                 Criteria used to sort the events. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of events per page to return [1..n]
 *
 * @return Array of String                                 List of events filtered, an empty array otherwise
 */
 function dbSearchEvent($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find events
     $Select = "SELECT e.EventID, e.EventDate, e.EventTitle, e.EventStartDate, e.EventStartTime, e.EventEndDate, e.EventEndTime,
                e.EventMaxParticipants, e.EventRegistrationDelay, e.EventClosingDate, et.EventTypeID, et.EventTypeName,
                et.EventTypeCategory, t.TownID, t.TownName, t.TownCode, EvR.NbRegistrations";
     $From = "FROM EventTypes et, Towns t, Events e";
     $Where = " WHERE e.TownID = t.TownID AND e.EventTypeID = et.EventTypeID";
     $Having = "";

     $FromRegistrations = "";
     if (count($ArrayParams) >= 0)
     {
         $bEventChildrenIncluded = FALSE;
         if ((array_key_exists("EventChildrenIncluded", $ArrayParams)) && ($ArrayParams["EventChildrenIncluded"]))
         {
             // Includes event children in the search
             $bEventChildrenIncluded = TRUE;
         }

         // <<< EventID field >>>
         if ((array_key_exists("EventID", $ArrayParams)) && (!empty($ArrayParams["EventID"])))
         {
             if (is_array($ArrayParams["EventID"]))
             {
                 $Where .= " AND e.EventID IN ".constructSQLINString($ArrayParams["EventID"]);
             }
             else
             {
                 $Where .= " AND e.EventID = ".$ArrayParams["EventID"];
             }
         }

         // <<< EventTitle field >>>
         if ((array_key_exists("EventTitle", $ArrayParams)) && (!empty($ArrayParams["EventTitle"])))
         {
             $Where .= " AND e.EventTitle LIKE \"".$ArrayParams["EventTitle"]."\"";
         }

         // <<< Event still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // An activated event is an event with a start date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND e.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         if ((array_key_exists("EventStartDate", $ArrayParams)) && (count($ArrayParams["EventStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND e.EventStartDate ".$ArrayParams["EventStartDate"][1]." \"".$ArrayParams["EventStartDate"][0]."\"";
         }

         if ((array_key_exists("EventEndDate", $ArrayParams)) && (count($ArrayParams["EventEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND e.EventEndDate ".$ArrayParams["EventEndDate"][1]." \"".$ArrayParams["EventEndDate"][0]."\"";
         }

         if ((array_key_exists("EventClosingDate", $ArrayParams)) && (count($ArrayParams["EventClosingDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND e.EventClosingDate ".$ArrayParams["EventClosingDate"][1]." \"".$ArrayParams["EventClosingDate"][0]."\"";
         }

         // <<< EventTypeID field >>>
         if ((array_key_exists("EventTypeID", $ArrayParams)) && (count($ArrayParams["EventTypeID"]) > 0))
         {
             $Where .= " AND et.EventTypeID IN ".constructSQLINString($ArrayParams["EventTypeID"]);
         }

         // <<< EventTypeName field >>>
         if ((array_key_exists("EventTypeName", $ArrayParams)) && (!empty($ArrayParams["EventTypeName"])))
         {
             $Where .= " AND et.EventTypeName LIKE \"".$ArrayParams["EventTypeName"]."\"";
         }

         // <<< EventTypeCategory field >>>
         if ((array_key_exists("EventTypeCategory", $ArrayParams)) && (count($ArrayParams["EventTypeCategory"]) > 0))
         {
             $Where .= " AND et.EventTypeCategory IN ".constructSQLINString($ArrayParams["EventTypeCategory"]);
         }

         // <<< TownID >>>
         if ((array_key_exists("TownID", $ArrayParams)) && (count($ArrayParams["TownID"]) > 0))
         {
             $Where .= " AND t.TownID IN ".constructSQLINString($ArrayParams["TownID"]);
         }

         // <<< TownName field >>>
         if ((array_key_exists("TownName", $ArrayParams)) && (!empty($ArrayParams["TownName"])))
         {
             $Where .= " AND t.TownName LIKE \"".$ArrayParams["TownName"]."\"";
         }

         // <<< TownCode field >>>
         if ((array_key_exists("TownCode", $ArrayParams)) && (!empty($ArrayParams["TownCode"])))
         {
             $Where .= " AND t.TownCode LIKE \"".$ArrayParams["TownCode"]."\"";
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             // Registered families to events
             $FromRegistrations = " INNER JOIN (SELECT tmpe.EventID, tmpe.ParentEventID, COUNT(tmper.EventRegistrationID) AS NbRegistrations
                                  FROM Events tmpe, EventRegistrations tmper
                                  WHERE tmpe.EventID = tmper.EventID AND tmper.FamilyID IN "
                                  .constructSQLINString($ArrayParams["FamilyID"])." GROUP BY tmpe.EventID) AS EvR";

             if ($bEventChildrenIncluded)
             {
                 // Includes event children in the search of registered family
                 $FromRegistrations .= " ON ((e.EventID = EvR.EventID) OR (EvR.ParentEventID = e.EventID))";
             }
             else
             {
                 // Don't include event children in the search of registered family
                 $FromRegistrations .= " ON (e.EventID = EvR.EventID)";
             }
         }

         // <<< FamilyLastname field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             // Registered families to events
             $FromRegistrations = " INNER JOIN (SELECT tmpe.EventID, tmpe.ParentEventID, COUNT(tmper.EventRegistrationID) AS NbRegistrations
                                  FROM Events tmpe, EventRegistrations tmper, Families tmpf
                                  WHERE tmpe.EventID = tmper.EventID AND tmper.FamilyID = tmpf.FamilyID
                                  AND tmpf.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\" GROUP BY tmpe.EventID) AS EvR";

             if ($bEventChildrenIncluded)
             {
                 // Includes event children in the search of registered family
                 $FromRegistrations .= " ON ((e.EventID = EvR.EventID) OR (EvR.ParentEventID = e.EventID))";
             }
             else
             {
                 // Don't include event children in the search of registered family
                 $FromRegistrations .= " ON (e.EventID = EvR.EventID)";
             }
         }

         // <<< Option : get activated events >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated events
                 $Where .= " AND e.EventClosingDate IS NULL";
             }
             else
             {
                 // Not activated events
                 $Where .= " AND e.EventClosingDate IS NOT NULL";
             }
         }

         // <<< Option : get opened events to registrations >>>
         if (array_key_exists("OpenedRegistrations", $ArrayParams))
         {
             if ($ArrayParams["OpenedRegistrations"])
             {
                 // Opened events
                 $Where .= " AND (TO_DAYS(e.EventStartDate) - TO_DAYS(NOW())) >= e.EventRegistrationDelay";
             }
             else
             {
                 // Not opened events
                 $Where .= " AND (TO_DAYS(e.EventStartDate) - TO_DAYS(NOW())) < e.EventRegistrationDelay";
             }
         }

         // <<< Option : get events with enough or not enough registered families >>>
         if (array_key_exists("PbNbRegistrations", $ArrayParams))
         {
             if ($ArrayParams["PbNbRegistrations"])
             {
                 // Events with not enough registrations
                 if (empty($Having))
                 {
                     $Having = "HAVING NbRegistrations < e.EventMaxParticipants";
                 }
                 else
                 {
                     $Having .= " AND NbRegistrations < e.EventMaxParticipants";
                 }
             }
             else
             {
                 // Events with enough registrations
                 if (empty($Having))
                 {
                     $Having = "HAVING NbRegistrations < e.EventMaxParticipants";
                 }
                 else
                 {
                     $Having .= " AND NbRegistrations < e.EventMaxParticipants";
                 }
             }
         }

         // <<< Option : get parent events >>>
         if (array_key_exists("ParentEvents", $ArrayParams))
         {
             if ($ArrayParams["ParentEvents"])
             {
                 // Parent events
                 $Where .= " AND e.ParentEventID IS NULL";
             }
             else
             {
                 // Not parent events (= child events)
                 $Where .= " AND e.ParentEventID IS NOT NULL";
             }
         }
     }

     if (empty($FromRegistrations))
     {
         // Registered families to events
         $FromRegistrations = " LEFT JOIN (SELECT tmpe.EventID, tmpe.ParentEventID, COUNT(tmper.EventRegistrationID) AS NbRegistrations
                                  FROM Events tmpe, EventRegistrations tmper
                                  WHERE tmpe.EventID = tmper.EventID GROUP BY tmpe.EventID) AS EvR";

         if ($bEventChildrenIncluded)
         {
             // Includes event children in the search of registered family
             $FromRegistrations .= " ON ((e.EventID = EvR.EventID) OR (EvR.ParentEventID = e.EventID))";
         }
         else
         {
             // Don't include event children in the search of registered family
             $FromRegistrations .= " ON (e.EventID = EvR.EventID)";
         }
     }

     // We take into account the page and the number of events per page
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
     $DbResult = $DbConnection->query("$Select $From $FromRegistrations $Where GROUP BY EventID $Having $StrOrderBy $Limit");

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "EventID" => array(),
                                   "EventDate" => array(),
                                   "EventTitle" => array(),
                                   "EventStartDate" => array(),
                                   "EventStartTime" => array(),
                                   "EventEndDate" => array(),
                                   "EventEndTime" => array(),
                                   "EventMaxParticipants" => array(),
                                   "EventRegistrationDelay" => array(),
                                   "EventClosingDate" => array(),
                                   "EventTypeID" => array(),
                                   "EventTypeName" => array(),
                                   "EventTypeCategory" => array(),
                                   "TownID" => array(),
                                   "TownName" => array(),
                                   "TownCode" => array(),
                                   "NbRegistrations" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["EventID"][] = $Record["EventID"];
                 $ArrayRecords["EventDate"][] = $Record["EventDate"];
                 $ArrayRecords["EventTitle"][] = $Record["EventTitle"];
                 $ArrayRecords["EventStartDate"][] = $Record["EventStartDate"];
                 $ArrayRecords["EventStartTime"][] = $Record["EventStartTime"];
                 $ArrayRecords["EventEndDate"][] = $Record["EventEndDate"];
                 $ArrayRecords["EventEndTime"][] = $Record["EventEndTime"];
                 $ArrayRecords["EventMaxParticipants"][] = $Record["EventMaxParticipants"];
                 $ArrayRecords["EventRegistrationDelay"][] = $Record["EventRegistrationDelay"];
                 $ArrayRecords["EventClosingDate"][] = $Record["EventClosingDate"];
                 $ArrayRecords["EventTypeID"][] = $Record["EventTypeID"];
                 $ArrayRecords["EventTypeName"][] = $Record["EventTypeName"];
                 $ArrayRecords["EventTypeCategory"][] = $Record["EventTypeCategory"];
                 $ArrayRecords["TownID"][] = $Record["TownID"];
                 $ArrayRecords["TownName"][] = $Record["TownName"];
                 $ArrayRecords["TownCode"][] = $Record["TownCode"];

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
 * Get the number of events filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-09-09 : EventID criteria can be an array
 *
 * @since 2013-04-10
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the events
 *
 * @return Integer              Number of the events found, 0 otherwise
 */
 function getNbdbSearchEvent($DbConnection, $ArrayParams)
 {
     // SQL request to find events
     $Select = "SELECT e.EventID, e.EventMaxParticipants, EvR.NbRegistrations";
     $From = "FROM EventTypes et, Towns t, Events e";
     $Where = " WHERE e.TownID = t.TownID AND e.EventTypeID = et.EventTypeID";
     $Having = "";

     $FromRegistrations = "";
     if (count($ArrayParams) >= 0)
     {
         $bEventChildrenIncluded = FALSE;
         if ((array_key_exists("EventChildrenIncluded", $ArrayParams)) && ($ArrayParams["EventChildrenIncluded"]))
         {
             // Includes event children in the search
             $bEventChildrenIncluded = TRUE;
         }

         // <<< EventID field >>>
         if ((array_key_exists("EventID", $ArrayParams)) && (!empty($ArrayParams["EventID"])))
         {
             if (is_array($ArrayParams["EventID"]))
             {
                 $Where .= " AND e.EventID IN ".constructSQLINString($ArrayParams["EventID"]);
             }
             else
             {
                 $Where .= " AND e.EventID = ".$ArrayParams["EventID"];
             }
         }

         // <<< EventTitle field >>>
         if ((array_key_exists("EventTitle", $ArrayParams)) && (!empty($ArrayParams["EventTitle"])))
         {
             $Where .= " AND e.EventTitle LIKE \"".$ArrayParams["EventTitle"]."\"";
         }

         // <<< Event still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // An activated event is an event with a start date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND e.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         if ((array_key_exists("EventStartDate", $ArrayParams)) && (count($ArrayParams["EventStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND e.EventStartDate ".$ArrayParams["EventStartDate"][1]." \"".$ArrayParams["EventStartDate"][0]."\"";
         }

         if ((array_key_exists("EventEndDate", $ArrayParams)) && (count($ArrayParams["EventEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND e.EventEndDate ".$ArrayParams["EventEndDate"][1]." \"".$ArrayParams["EventEndDate"][0]."\"";
         }

         if ((array_key_exists("EventClosingDate", $ArrayParams)) && (count($ArrayParams["EventClosingDate"]) == 2))
         {
             // [0] -> date, [1] -> operator
             $Where .= " AND e.EventClosingDate ".$ArrayParams["EventClosingDate"][1]." \"".$ArrayParams["EventClosingDate"][0]."\"";
         }

         // <<< EventTypeID field >>>
         if ((array_key_exists("EventTypeID", $ArrayParams)) && (count($ArrayParams["EventTypeID"]) > 0))
         {
             $Where .= " AND et.EventTypeID IN ".constructSQLINString($ArrayParams["EventTypeID"]);
         }

         // <<< EventTypeName field >>>
         if ((array_key_exists("EventTypeName", $ArrayParams)) && (!empty($ArrayParams["EventTypeName"])))
         {
             $Where .= " AND et.EventTypeName LIKE \"".$ArrayParams["EventTypeName"]."\"";
         }

         // <<< EventTypeCategory field >>>
         if ((array_key_exists("EventTypeCategory", $ArrayParams)) && (count($ArrayParams["EventTypeCategory"]) > 0))
         {
             $Where .= " AND et.EventTypeCategory IN ".constructSQLINString($ArrayParams["EventTypeCategory"]);
         }

         // <<< TownID >>>
         if ((array_key_exists("TownID", $ArrayParams)) && (count($ArrayParams["TownID"]) > 0))
         {
             $Where .= " AND t.TownID IN ".constructSQLINString($ArrayParams["TownID"]);
         }

         // <<< TownName field >>>
         if ((array_key_exists("TownName", $ArrayParams)) && (!empty($ArrayParams["TownName"])))
         {
             $Where .= " AND t.TownName LIKE \"".$ArrayParams["TownName"]."\"";
         }

         // <<< TownCode field >>>
         if ((array_key_exists("TownCode", $ArrayParams)) && (!empty($ArrayParams["TownCode"])))
         {
             $Where .= " AND t.TownCode LIKE \"".$ArrayParams["TownCode"]."\"";
         }

         // <<< FamilyID field >>>
         if ((array_key_exists("FamilyID", $ArrayParams)) && (count($ArrayParams["FamilyID"]) > 0))
         {
             // Registered families to events
             $FromRegistrations = " INNER JOIN (SELECT tmpe.EventID, tmpe.ParentEventID, COUNT(tmper.EventRegistrationID) AS NbRegistrations
                                  FROM Events tmpe, EventRegistrations tmper
                                  WHERE tmpe.EventID = tmper.EventID AND tmper.FamilyID IN "
                                  .constructSQLINString($ArrayParams["FamilyID"])." GROUP BY tmpe.EventID) AS EvR";

             if ($bEventChildrenIncluded)
             {
                 // Includes event children in the search of registered family
                 $FromRegistrations .= " ON ((e.EventID = EvR.EventID) OR (EvR.ParentEventID = e.EventID))";
             }
             else
             {
                 // Don't include event children in the search of registered family
                 $FromRegistrations .= " ON (e.EventID = EvR.EventID)";
             }
         }

         // <<< FamilyLastname field >>>
         if ((array_key_exists("FamilyLastname", $ArrayParams)) && (!empty($ArrayParams["FamilyLastname"])))
         {
             // Registered families to events
             $FromRegistrations = " INNER JOIN (SELECT tmpe.EventID, tmpe.ParentEventID, COUNT(tmper.EventRegistrationID) AS NbRegistrations
                                  FROM Events tmpe, EventRegistrations tmper, Families tmpf
                                  WHERE tmpe.EventID = tmper.EventID AND tmper.FamilyID = tmpf.FamilyID
                                  AND tmpf.FamilyLastname LIKE \"".$ArrayParams["FamilyLastname"]."\" GROUP BY tmpe.EventID) AS EvR";

             if ($bEventChildrenIncluded)
             {
                 // Includes event children in the search of registered family
                 $FromRegistrations .= " ON ((e.EventID = EvR.EventID) OR (EvR.ParentEventID = e.EventID))";
             }
             else
             {
                 // Don't include event children in the search of registered family
                 $FromRegistrations .= " ON (e.EventID = EvR.EventID)";
             }
         }

         // <<< Option : get activated events >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated events
                 $Where .= " AND e.EventClosingDate IS NULL";
             }
             else
             {
                 // Not activated events
                 $Where .= " AND e.EventClosingDate IS NOT NULL";
             }
         }

         // <<< Option : get opened events to registrations >>>
         if (array_key_exists("OpenedRegistrations", $ArrayParams))
         {
             if ($ArrayParams["OpenedRegistrations"])
             {
                 // Opened events
                 $Where .= " AND (TO_DAYS(e.EventStartDate) - TO_DAYS(NOW())) >= e.EventRegistrationDelay";
             }
             else
             {
                 // Not opened events
                 $Where .= " AND (TO_DAYS(e.EventStartDate) - TO_DAYS(NOW())) < e.EventRegistrationDelay";
             }
         }

         // <<< Option : get events with enough or not enough registered families >>>
         if (array_key_exists("PbNbRegistrations", $ArrayParams))
         {
             if ($ArrayParams["PbNbRegistrations"])
             {
                 // Events with not enough registrations
                 if (empty($Having))
                 {
                     $Having = "HAVING NbRegistrations < e.EventMaxParticipants";
                 }
                 else
                 {
                     $Having .= " AND NbRegistrations < e.EventMaxParticipants";
                 }
             }
             else
             {
                 // Events with enough registrations
                 if (empty($Having))
                 {
                     $Having = "HAVING NbRegistrations < e.EventMaxParticipants";
                 }
                 else
                 {
                     $Having .= " AND NbRegistrations < e.EventMaxParticipants";
                 }
             }
         }

         // <<< Option : get parent events >>>
         if (array_key_exists("ParentEvents", $ArrayParams))
         {
             if ($ArrayParams["ParentEvents"])
             {
                 // Parent events
                 $Where .= " AND e.ParentEventID IS NULL";
             }
             else
             {
                 // Not parent events (= child events)
                 $Where .= " AND e.ParentEventID IS NOT NULL";
             }
         }
     }

     if (empty($FromRegistrations))
     {
         // Registered families to events
         $FromRegistrations = " LEFT JOIN (SELECT tmpe.EventID, tmpe.ParentEventID, COUNT(tmper.EventRegistrationID) AS NbRegistrations
                                  FROM Events tmpe, EventRegistrations tmper
                                  WHERE tmpe.EventID = tmper.EventID GROUP BY tmpe.EventID) AS EvR";

         if ($bEventChildrenIncluded)
         {
             // Includes event children in the search of registered family
             $FromRegistrations .= " ON ((e.EventID = EvR.EventID) OR (EvR.ParentEventID = e.EventID))";
         }
         else
         {
             // Don't include event children in the search of registered family
             $FromRegistrations .= " ON (e.EventID = EvR.EventID)";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $FromRegistrations $Where GROUP BY EventID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Check if an event registration exists in the EventRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-19
 *
 * @param $DbConnection             DB object    Object of the opened database connection
 * @param $EventRegistrationID      Integer      ID of the event registration searched [1..n]
 *
 * @return Boolean                  TRUE if the event registration exists, FALSE otherwise
 */
 function isExistingEventRegistration($DbConnection, $EventRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT EventRegistrationID FROM EventRegistrations
                                       WHERE EventRegistrationID = $EventRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The event registration exists
             return TRUE;
         }
     }

     // The event registration doesn't exist
     return FALSE;
 }


/**
 * Add an event registration for a family in the EventRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-19
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $EventRegistrationDate             Date         Creation date of the event registration (yyyy-mm-dd hh:mm:ss)
 * @param $EventID                           Integer      ID of the event concerned by the registration [1..n]
 * @param $FamilyID                          Integer      ID of the family concerned by the registration [1..n]
 * @param $SupportMemberID                   Integer      ID of the supporter, author of the event registration [1..n]
 * @param $EventRegistrationValided          Integer      0 => registration not valided, 1 => registration valided [0..1]
 * @param $EventRegistrationComment          String       Comment about the event registration
 *
 * @return Integer                           The primary key of the event registration [1..n], 0 otherwise
 */
 function dbAddEventRegistration($DbConnection, $EventRegistrationDate, $EventID, $FamilyID, $SupportMemberID, $EventRegistrationValided = 1, $EventRegistrationComment = NULL)
 {
     if (($EventID > 0) && ($FamilyID > 0) && ($SupportMemberID > 0) && ($EventRegistrationValided >= 0))
     {
         // Check if the event registration is a new event registration for a family and the event
         $DbResult = $DbConnection->query("SELECT EventRegistrationID FROM EventRegistrations WHERE EventID = $EventID
                                          AND FamilyID = $FamilyID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the EventRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventRegistrationDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $EventRegistrationDate = ", EventRegistrationDate = \"$EventRegistrationDate\"";
                 }

                 if (empty($EventRegistrationComment))
                 {
                     $EventRegistrationComment = "";
                 }
                 else
                 {
                     $EventRegistrationComment = ", EventRegistrationComment = \"$EventRegistrationComment\"";
                 }

                 // It's a new event registration
                 $id = getNewPrimaryKey($DbConnection, "EventRegistrations", "EventRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO EventRegistrations SET EventRegistrationID = $id, EventID = $EventID,
                                                      SupportMemberID = $SupportMemberID, FamilyID = $FamilyID,
                                                      EventRegistrationValided = $EventRegistrationValided $EventRegistrationComment
                                                      $EventRegistrationDate");
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
 * Update an existing event registration in the EventRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-27
 *
 * @param $DbConnection                      DB object    Object of the opened database connection
 * @param $EventRegistrationID               Integer      ID of the event registration to update [1..n]
 * @param $EventRegistrationDate             Date         Creation date of the event registration (yyyy-mm-dd hh:mm:ss)
 * @param $EventID                           Integer      ID of the event concerned by the registration [1..n]
 * @param $FamilyID                          Integer      ID of the family concerned by the registration [1..n]
 * @param $SupportMemberID                   Integer      ID of the supporter, author of the event registration [1..n]
 * @param $EventRegistrationValided          Integer      0 => registration not valided, 1 => registration valided [0..1]
 * @param $EventRegistrationComment          String       Comment about the event registration
 *
 * @return Integer                           The primary key of the event registration [1..n], 0 otherwise
 */
 function dbUpdateEventRegistration($DbConnection, $EventRegistrationID, $EventRegistrationDate, $EventID, $FamilyID, $SupportMemberID, $EventRegistrationValided = 1, $EventRegistrationComment = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($EventRegistrationID < 1) || (!isInteger($EventRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the EventRegistrationDate is valide
     if (!is_null($EventRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The EventRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "EventRegistrationDate = \"$EventDate\"";
         }
     }

     if (!is_null($EventID))
     {
         if (($EventID < 1) || (!isInteger($EventID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "EventID = $EventID";
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

     if (!is_Null($EventRegistrationValided))
     {
         if (($EventRegistrationValided < 0) || (!isInteger($EventRegistrationValided)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The EventRegistrationValided field will be updated
             $ArrayParamsUpdate[] = "EventRegistrationValided = $EventRegistrationValided";
         }
     }

     if (!is_Null($EventRegistrationComment))
     {
         // The EventRegistrationComment field will be updated
         $ArrayParamsUpdate[] = "EventRegistrationComment = \"$EventRegistrationComment\"";
     }

     // Here, the parameters are correct, we check if the event registration exists
     if (isExistingEventRegistration($DbConnection, $EventRegistrationID))
     {
         // We check if the event registration is unique
         $DbResult = $DbConnection->query("SELECT EventRegistrationID FROM EventRegistrations WHERE EventID = $EventID
                                          AND FamilyID = $FamilyID AND EventRegistrationID <> $EventRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The event registration entry exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE EventRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE EventRegistrationID = $EventRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Event registration updated
                         return $EventRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $EventRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Check if a family respects the number of contributions for cooperation to events for a givent school year.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-18
 *
 * @param $DbConnection              DB object            Object of the opened database connection
 * @param $FamilyID                  Integer              ID of the concerned family [1..n]
 * @param $SchoolYear                Integer              Concerned school year
 *
 * @return Boolean                   TRUE if the the family respects contributions,
 *                                   FALSE otherwise
 */
 function dbFamilyCoopContribution($DbConnection, $FamilyID, $SchoolYear)
 {
     if (($FamilyID > 0) && ($SchoolYear >= 1980) && (isset($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$SchoolYear])))
     {
         // Concerned school year
         $SchoolYearStartDate = getSchoolYearStartDate($SchoolYear);
         $SchoolYearEndDate = getSchoolYearEndDate($SchoolYear);

         $From = '';
         $Where = '';
         foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS'] as $c => $NbMinCoop)
         {
             // We keep only valided registrations
             $From .= ", (SELECT er$c.FamilyID, COUNT(er$c.EventRegistrationID) AS NB$c
                       FROM Events e$c, EventTypes et$c, EventRegistrations er$c WHERE e$c.EventTypeID = et$c.EventTypeID
                       AND et$c.EventTypeCategory = $c AND e$c.EventID = er$c.EventID AND er$c.FamilyID = $FamilyID
                       AND e$c.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"
                       AND er$c.EventRegistrationValided = 1
                       GROUP BY er$c.FamilyID HAVING NB$c >= $NbMinCoop) AS Tev$c";
             $Where .= " AND f.FamilyID = Tev$c.FamilyID";
         }

         $DbResult = $DbConnection->query("SELECT f.FamilyID FROM Families f $From WHERE f.FamilyID = $FamilyID $Where");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 return TRUE;
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Set an event registration concerned a family to another event if the family isn't already registered for this event.
 * If a family is already registered for the "new" event, the registration for the event is deleted.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-15
 *
 * @param $DbConnection              DB object            Object of the opened database connection
 * @param $EventID                   Integer              ID of the event for which we want to change registrations [1..n]
 * @param $ToEventID                 Integer              ID of the concerned event [1..n]
 * @param $ArrayFamilyID             Array of Integers    Contains ID of families registered to the event
 *
 * @return Boolean                   TRUE if the registrations are postponed form the event to the "to event",
 *                                   FALSE otherwise
 */
 function dbPostponeEventRegistration($DbConnection, $EventID, $ToEventID, $ArrayFamilyID = array())
 {
     if (($EventID > 0) && ($ToEventID > 0))
     {
         if (empty($ArrayFamilyID))
         {
             // We must get registrations of the families for the concerned event
             $ArrayFamilyID = array();
             $ArrayRegistrations = dbSearchEventRegistration($DbConnection, array("EventID" => $EventID), "FamilyLastname", 1, 0);
             if ((isset($ArrayRegistrations['FamilyID'])) && (!empty($ArrayRegistrations['FamilyID'])))
             {
                 $ArrayFamilyID = $ArrayRegistrations['FamilyID'];
             }

             unset($ArrayRegistrations);
         }

         if (!empty($ArrayFamilyID))
         {
             // Get registration of the "to event"
             $ArrayRegistrations = dbSearchEventRegistration($DbConnection, array("EventID" => $ToEventID), "FamilyLastname", 1, 0);
             if ((isset($ArrayRegistrations['FamilyID'])) && (!empty($ArrayRegistrations['FamilyID'])))
             {
                 foreach($ArrayFamilyID as $f => $FamilyID)
                 {
                     if (in_array($FamilyID, $ArrayRegistrations['FamilyID']))
                     {
                         // The family is already registered for the "to event" : we delete the registration for the event
                         $DbResult = $DbConnection->query("DELETE FROM EventRegistrations WHERE EventID = $EventID
                                                          AND FamilyID = $FamilyID");
                     }
                     else
                     {
                         // We just change the event ID to register the family to the "to event"
                         $DbResult = $DbConnection->query("UPDATE EventRegistrations SET EventID = $ToEventID
                                                          WHERE EventID = $EventID AND FamilyID = $FamilyID");
                     }
                 }

                 // Registrations postponed
                 return TRUE;
             }
             else
             {
                 // No registration for the "To event" : we can set registration of the event" to the "to event"
                 $DbResult = $DbConnection->query("UPDATE EventRegistrations SET EventID = $ToEventID WHERE EventID = $EventID");
                 if (!DB::isError($DbResult))
                 {
                     // Registrations postponed
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Delete an event registration, thanks to its ID, or all registrations of a given event
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-15
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $EventRegistrationID       Integer      ID of the event registration to delete [1..n] (or NULL)
 * @param $EventID                   Integer      ID of the concerned event [1..n] (or NULL)
 *
 * @return Boolean                   TRUE if the event registration(s) is(are) deleted,
 *                                   FALSE otherwise
 */
 function dbDeleteEventRegistration($DbConnection, $EventRegistrationID, $EventID = NULL)
 {
     if ((empty($EventID)) && ($EventRegistrationID > 0))
     {
         // We delete one event registration
         $DbResult = $DbConnection->query("DELETE FROM EventRegistrations WHERE EventRegistrationID = $EventRegistrationID");
         if (!DB::isError($DbResult))
         {
             // Registration deleted
             return TRUE;
         }
     }
     elseif ($EventID > 0)
     {
         // We delete all registrations of an event
         $DbResult = $DbConnection->query("DELETE FROM EventRegistrations WHERE EventID = $EventID");
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
 * Get the number of registrations for the given event and its child-events + the number of selected families registered
 * to the given event and its child-events, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-19
 *
 * @param $DbConnection              DB object           Object of the opened database connection
 * @param $EventID                   Integer             ID of the event searched [1..n]
 * @param $EventActivated            Array of Integer    To filter activated or not events
 * @param $ArrayFamilies             Array of Integer    To filter families registered to the events
 *
 * @return Array of Integers         The number of registrations for the event and its child-events,
 *                                   the number of selected families registered for the event and its child-events,
 *                                   0/0 otherwise
 */
 function getNbEventRegistrationTree($DbConnection, $EventID, $EventActivated = array(), $ArrayFamilies = array())
 {
     $EventIDCondition = " AND e.ParentEventID IS NULL";
     if ((!is_null($EventID)) && ($EventID > 0))
     {
         $EventIDCondition = " AND e.EventID = $EventID";
     }

     $ActivatedCondition = '';
     if (count($EventActivated) > 0)
     {
         if ((in_array(0, $EventActivated)) && (in_array(1, $EventActivated)))
         {
             $ActivatedCondition = "";
         }
         elseif (in_array(0, $EventActivated))
         {
             $ActivatedCondition = " AND e.EventClosingDate IS NOT NULL";
         }
         elseif (in_array(1, $EventActivated))
         {
             $ActivatedCondition = " AND e.EventClosingDate IS NULL";
         }
     }

     // We get the events of the first tree level => ParentEventID = NULL
     $DbResult = $DbConnection->query("SELECT e.EventID FROM Events e, EventTypes et WHERE e.EventTypeID = et.EventTypeID
                                      $EventIDCondition $ActivatedCondition");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() > 0)
         {
             $ArrayEventsID = array();

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayEventsID[] = $Record["EventID"];
             }

             $CurrentIndexEvent = 0;  // Point to the current event, in the array $ArrayEvents
             $ArrayEventsSize = $DbResult->numRows();
             while ($CurrentIndexEvent < $ArrayEventsSize)
             {
                 // We get the sublevels events of the current event
                 $DbResult = $DbConnection->query("SELECT e.EventID FROM Events e, EventTypes et WHERE e.EventTypeID = et.EventTypeID
                                                   $ActivatedCondition AND ParentEventID = ".$ArrayEventsID[$CurrentIndexEvent]);
                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() > 0)
                     {
                         $CurrentIndexToInsert = $CurrentIndexEvent;
                         while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                         {
                             $ArrayEventsID[] = $Record["EventID"];

                             $CurrentIndexToInsert++;   // To insert the next sublevel event after this sublevel event
                             $ArrayEventsSize++;        // Because we added a event in the array
                         }

                         // We go to the next event in the array
                         $CurrentIndexEvent++;
                     }
                     else
                     {
                         // We go to the next event in the array
                         $CurrentIndexEvent++;
                     }
                 }
                 else
                 {
                     // We go to the next event in the array
                     $CurrentIndexEvent++;
                 }
             }

             // Get the number of registrations for each event and child-event found
             if (!empty($ArrayEventsID))
             {
                 $SelectFamilies = '';
                 $FromFamilies = '';
                 if (count($ArrayFamilies) > 0)
                 {
                     $SelectFamilies = ", COUNT(f.FamilyID) AS NbSelectedFamilies";
                     $FromFamilies = " LEFT JOIN Families f ON (f.FamilyID = er.FamilyID AND f.FamilyID IN ".constructSQLINString($ArrayFamilies).")";
                 }

                 $DbResult = $DbConnection->query("SELECT er.EventID, COUNT(er.EventRegistrationID) AS NbRegistrations $SelectFamilies
                                                   FROM EventRegistrations er $FromFamilies WHERE er.EventID IN ".constructSQLINString($ArrayEventsID)
                                                   ." GROUP BY EventID");
                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() > 0)
                     {
                         $iNbRegistrations = 0;
                         $iNbRegisteredSelectedFamilies = 0;
                         while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                         {
                             $iNbRegistrations += $Record['NbRegistrations'];
                             if (count($ArrayFamilies) > 0)
                             {
                                 $iNbRegisteredSelectedFamilies += $Record['NbSelectedFamilies'];
                             }
                         }

                         return array($iNbRegistrations, $iNbRegisteredSelectedFamilies);
                     }
                 }
             }
         }
     }

     // ERROR
     return array(0, 0);
 }


/**
 * Get event registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2019-06-17 : EventID and FamilyID can be an array
 *
 * @since 2013-04-08
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the event registrations
 * @param $OrderBy                  String                 Criteria used to sort the event registrations. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of even registrations per page to return [1..n]
 *
 * @return Array of String                                 List of event registrations filtered, an empty array otherwise
 */
 function dbSearchEventRegistration($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find event registrations
     $Select = "SELECT er.EventRegistrationID, er.EventRegistrationDate, er.EventRegistrationValided, er.EventRegistrationComment,
                er.EventID, e.EventTitle, er.SupportMemberID, f.FamilyID, f.FamilyLastname, t.TownID, t.TownName, t.TownCode";
     $From = "FROM EventRegistrations er, Events e, EventTypes et, Families f, Towns t";
     $Where = " WHERE er.FamilyID = f.FamilyID AND er.EventID = e.EventID AND e.EventTypeID = et.EventTypeID AND e.TownID = t.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< EventID field >>>
         if ((array_key_exists("EventID", $ArrayParams)) && (!empty($ArrayParams["EventID"])))
         {
             if (is_array($ArrayParams["EventID"]))
             {
                 $Where .= " AND er.EventID IN ".constructSQLINString($ArrayParams["EventID"]);
             }
             else
             {
                 $Where .= " AND er.EventID = ".$ArrayParams["EventID"];
             }
         }

         // <<< Event still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // An activated event is an event with a start date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND e.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< EventTypeID field >>>
         if ((array_key_exists("EventTypeID", $ArrayParams)) && (count($ArrayParams["EventTypeID"]) > 0))
         {
             $Where .= " AND et.EventTypeID IN ".constructSQLINString($ArrayParams["EventTypeID"]);
         }

         // <<< EventTypeName field >>>
         if ((array_key_exists("EventTypeName", $ArrayParams)) && (!empty($ArrayParams["EventTypeName"])))
         {
             $Where .= " AND et.EventTypeName LIKE \"".$ArrayParams["EventTypeName"]."\"";
         }

         // <<< EventTypeCategory field >>>
         if ((array_key_exists("EventTypeCategory", $ArrayParams)) && (count($ArrayParams["EventTypeCategory"]) > 0))
         {
             $Where .= " AND et.EventTypeCategory IN ".constructSQLINString($ArrayParams["EventTypeCategory"]);
         }

         // <<< EventRegistrationValided >>>
         if ((array_key_exists("EventRegistrationValided", $ArrayParams)) && (count($ArrayParams["EventRegistrationValided"]) > 0))
         {
             $Where .= " AND er.EventRegistrationValided IN ".constructSQLINString($ArrayParams["EventRegistrationValided"]);
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY EventRegistrationID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "EventRegistrationID" => array(),
                                   "EventRegistrationDate" => array(),
                                   "EventRegistrationValided" => array(),
                                   "EventRegistrationComment" => array(),
                                   "EventID" => array(),
                                   "EventTitle" => array(),
                                   "SupportMemberID" => array(),
                                   "FamilyID" => array(),
                                   "FamilyLastname" => array(),
                                   "TownID" => array(),
                                   "TownName" => array(),
                                   "TownCode" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["EventRegistrationID"][] = $Record["EventRegistrationID"];
                 $ArrayRecords["EventRegistrationDate"][] = $Record["EventRegistrationDate"];
                 $ArrayRecords["EventRegistrationValided"][] = $Record["EventRegistrationValided"];
                 $ArrayRecords["EventRegistrationComment"][] = $Record["EventRegistrationComment"];
                 $ArrayRecords["EventID"][] = $Record["EventID"];
                 $ArrayRecords["EventTitle"][] = $Record["EventTitle"];
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
                 $ArrayRecords["FamilyID"][] = $Record["FamilyID"];
                 $ArrayRecords["FamilyLastname"][] = $Record["FamilyLastname"];
                 $ArrayRecords["TownID"][] = $Record["TownID"];
                 $ArrayRecords["TownName"][] = $Record["TownName"];
                 $ArrayRecords["TownCode"][] = $Record["TownCode"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of event registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2019-06-17 : EventID and FamilyID can be an array
 *
 * @since 2013-04-08
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the event registrations
 *
 * @return Integer              Number of the event registrations found, 0 otherwise
 */
 function getNbdbSearchEventRegistration($DbConnection, $ArrayParams)
 {
     // SQL request to find event registrations
     $Select = "SELECT er.EventRegistrationID";
     $From = "FROM EventRegistrations er, Events e, EventTypes et, Families f, Towns t";
     $Where = " WHERE er.FamilyID = f.FamilyID AND er.EventID = e.EventID AND e.EventTypeID = et.EventTypeID AND e.TownID = t.TownID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< EventID field >>>
         if ((array_key_exists("EventID", $ArrayParams)) && (!empty($ArrayParams["EventID"])))
         {
             if (is_array($ArrayParams["EventID"]))
             {
                 $Where .= " AND er.EventID IN ".constructSQLINString($ArrayParams["EventID"]);
             }
             else
             {
                 $Where .= " AND er.EventID = ".$ArrayParams["EventID"];
             }
         }

         // <<< Event still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // An activated event is an event with a start date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND e.EventStartDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< EventTypeID field >>>
         if ((array_key_exists("EventTypeID", $ArrayParams)) && (count($ArrayParams["EventTypeID"]) > 0))
         {
             $Where .= " AND et.EventTypeID IN ".constructSQLINString($ArrayParams["EventTypeID"]);
         }

         // <<< EventTypeName field >>>
         if ((array_key_exists("EventTypeName", $ArrayParams)) && (!empty($ArrayParams["EventTypeName"])))
         {
             $Where .= " AND et.EventTypeName LIKE \"".$ArrayParams["EventTypeName"]."\"";
         }

         // <<< EventTypeCategory field >>>
         if ((array_key_exists("EventTypeCategory", $ArrayParams)) && (count($ArrayParams["EventTypeCategory"]) > 0))
         {
             $Where .= " AND et.EventTypeCategory IN ".constructSQLINString($ArrayParams["EventTypeCategory"]);
         }

         // <<< EventRegistrationValided >>>
         if ((array_key_exists("EventRegistrationValided", $ArrayParams)) && (count($ArrayParams["EventRegistrationValided"]) > 0))
         {
             $Where .= " AND er.EventRegistrationValided IN ".constructSQLINString($ArrayParams["EventRegistrationValided"]);
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
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY EventRegistrationID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Check if a swap of event registration exists in the EventSwappedRegistrations table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-14
 *
 * @param $DbConnection                  DB object    Object of the opened database connection
 * @param $EventSwappedRegistrationID    Integer      ID of the swap of event registration searched [1..n]
 *
 * @return Boolean                       TRUE if the swap of event registration exists, FALSE otherwise
 */
 function isExistingEventSwappedRegistration($DbConnection, $EventSwappedRegistrationID)
 {
     $DbResult = $DbConnection->query("SELECT EventSwappedRegistrationID FROM EventSwappedRegistrations
                                       WHERE EventSwappedRegistrationID = $EventSwappedRegistrationID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The swap of event registration exists
             return TRUE;
         }
     }

     // The swap of event registration doesn't exist
     return FALSE;
 }


/**
 * Add a swap of event registration for 2 families and 2 events in the EventSwappedRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-15
 *
 * @param $DbConnection                           DB object    Object of the opened database connection
 * @param $EventSwappedRegistrationDate           Date         Creation date of the event swapped registration (yyyy-mm-dd hh:mm:ss)
 * @param $SupportMemberID                        Integer      ID of the supporter, author of the event swapped registration [1..n]
 * @param $RequestorFamilyID                      Integer      ID of the family who requests the swap of event registration [1..n]
 * @param $RequestorEventID                       Integer      ID of the event of the requestor family [1..n]
 * @param $AcceptorFamilyID                       Integer      ID of the family who acceptes the swap of event registration [1..n]
 * @param $AcceptorEventID                        Integer      ID of the event of the acceptor family [1..n]
 * @param $EventSwappedRegistrationClosingDate    Date         Closing date of the event swapped registration (yyyy-mm-dd hh:mm:ss)
 *
 * @return Integer                                The primary key of the event swapped registration [1..n], 0 otherwise
 */
 function dbAddEventSwappedRegistration($DbConnection, $EventSwappedRegistrationDate, $SupportMemberID, $RequestorFamilyID, $RequestorEventID, $AcceptorFamilyID, $AcceptorEventID, $EventSwappedRegistrationClosingDate = NULL)
 {
     if (($SupportMemberID > 0) && ($RequestorFamilyID > 0) && ($RequestorEventID > 0) && ($AcceptorFamilyID > 0) && ($AcceptorEventID > 0))
     {
         // Check if the requestor family has no opened swap for the requestor event; same check for the acceptor family
         $DbResult = $DbConnection->query("SELECT EventSwappedRegistrationID FROM EventSwappedRegistrations
                                           WHERE (RequestorEventID = $RequestorEventID AND RequestorFamilyID = $RequestorFamilyID
                                           AND EventSwappedRegistrationClosingDate IS NULL) OR (RequestorEventID = $AcceptorEventID
                                           AND RequestorFamilyID = $AcceptorFamilyID AND EventSwappedRegistrationClosingDate IS NULL)");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // Check if the EventSwappedRegistrationDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventSwappedRegistrationDate) == 0)
                 {
                     return 0;
                 }
                 else
                 {
                     $EventSwappedRegistrationDate = ", EventSwappedRegistrationDate = \"$EventSwappedRegistrationDate\"";
                 }

                 // Check if the EventSwappedRegistrationClosingDate is valide
                 if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventSwappedRegistrationClosingDate) == 0)
                 {
                     $EventSwappedRegistrationClosingDate = ", EventSwappedRegistrationClosingDate = NULL";
                 }
                 else
                 {
                     $EventSwappedRegistrationClosingDate = ", EventSwappedRegistrationClosingDate = \"$EventSwappedRegistrationClosingDate\"";
                 }

                 // It's a new event swapped registration
                 $id = getNewPrimaryKey($DbConnection, "EventSwappedRegistrations", "EventSwappedRegistrationID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO EventSwappedRegistrations SET EventSwappedRegistrationID = $id,
                                                      SupportMemberID = $SupportMemberID, RequestorFamilyID = $RequestorFamilyID,
                                                      RequestorEventID = $RequestorEventID, AcceptorFamilyID = $AcceptorFamilyID,
                                                      AcceptorEventID = $AcceptorEventID $EventSwappedRegistrationDate
                                                      $EventSwappedRegistrationClosingDate");
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
 * Update an existing swap of event registration for 2 families and 2 events in the
 * EventSwappedRegistrations table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-24
 *
 * @param $DbConnection                           DB object    Object of the opened database connection
 * @param $EventSwappedRegistrationID             Integer      ID of the event swapped registrationto update [1..n]
 * @param $EventSwappedRegistrationDate           Date         Creation date of the event swapped registration (yyyy-mm-dd hh:mm:ss)
 * @param $SupportMemberID                        Integer      ID of the supporter, author of the event swapped registration [1..n]
 * @param $RequestorFamilyID                      Integer      ID of the family who requests the swap of event registration [1..n]
 * @param $RequestorEventID                       Integer      ID of the event of the requestor family [1..n]
 * @param $AcceptorFamilyID                       Integer      ID of the family who acceptes the swap of event registration [1..n]
 * @param $AcceptorEventID                        Integer      ID of the event of the acceptor family [1..n]
 * @param $EventSwappedRegistrationClosingDate    Date         Closing date of the event swapped registration (yyyy-mm-dd hh:mm:ss)
 *
 * @return Integer                                The primary key of the event swapped registration [1..n], 0 otherwise
 */
 function dbUpdateEventSwappedRegistration($DbConnection, $EventSwappedRegistrationID, $EventSwappedRegistrationDate, $SupportMemberID, $RequestorFamilyID, $RequestorEventID, $AcceptorFamilyID, $AcceptorEventID, $EventSwappedRegistrationClosingDate = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($EventSwappedRegistrationID < 1) || (!isInteger($EventSwappedRegistrationID)))
     {
         // ERROR
         return 0;
     }

     // Check if the EventSwappedRegistrationDate is valide
     if (!is_null($EventSwappedRegistrationDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventSwappedRegistrationDate) == 0)
         {
             return 0;
         }
         else
         {
             // The EventSwappedRegistrationDate field will be updated
             $ArrayParamsUpdate[] = "EventSwappedRegistrationDate = \"$EventSwappedRegistrationDate\"";
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

     if (!is_null($RequestorFamilyID))
     {
         if (($RequestorFamilyID < 1) || (!isInteger($RequestorFamilyID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "RequestorFamilyID = $RequestorFamilyID";
         }
     }

     if (!is_null($RequestorEventID))
     {
         if (($RequestorEventID < 1) || (!isInteger($RequestorEventID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "RequestorEventID = $RequestorEventID";
         }
     }

     if (!is_null($AcceptorFamilyID))
     {
         if (($AcceptorFamilyID < 1) || (!isInteger($AcceptorFamilyID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "AcceptorFamilyID = $AcceptorFamilyID";
         }
     }

     if (!is_null($AcceptorEventID))
     {
         if (($AcceptorEventID < 1) || (!isInteger($AcceptorEventID)))
         {
             // ERROR
             return 0;
         }
         else
         {
             $ArrayParamsUpdate[] = "AcceptorEventID = $AcceptorEventID";
         }
     }

     if (!is_null($EventSwappedRegistrationClosingDate))
     {
         if (empty($EventSwappedRegistrationClosingDate))
         {
             // The EventSwappedRegistrationClosingDate field will be updated
             $ArrayParamsUpdate[] = "EventSwappedRegistrationClosingDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $EventSwappedRegistrationClosingDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The EventSwappedRegistrationClosingDate field will be updated
                 $ArrayParamsUpdate[] = "EventSwappedRegistrationClosingDate = \"$EventSwappedRegistrationClosingDate\"";
             }
         }
     }

     // Here, the parameters are correct, we check if the event swapped registration exists
     if (isExistingEventSwappedRegistration($DbConnection, $EventSwappedRegistrationID))
     {
         // We check if the event swapped registration is unique
         $DbResult = $DbConnection->query("SELECT EventSwappedRegistrationID FROM EventSwappedRegistrations
                                           WHERE RequestorFamilyID = $RequestorFamilyID AND RequestorEventID = $RequestorEventID
                                           AND AcceptorFamilyID = $AcceptorFamilyID AND AcceptorEventID = $AcceptorEventID
                                           AND EventSwappedRegistrationID <> $EventSwappedRegistrationID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 // The event swapped registration entry exists and is unique : we can update if there is at least 1 parameter
                 if (count($ArrayParamsUpdate) > 0)
                 {
                     $DbResult = $DbConnection->query("UPDATE EventSwappedRegistrations SET ".implode(", ", $ArrayParamsUpdate)
                                                      ." WHERE EventSwappedRegistrationID = $EventSwappedRegistrationID");
                     if (!DB::isError($DbResult))
                     {
                         // Event swapped registration updated
                         return $EventSwappedRegistrationID;
                     }
                 }
                 else
                 {
                     // The update isn't usefull
                     return $EventSwappedRegistrationID;
                 }
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Get event swapped registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-22
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the event swapped registrations
 * @param $OrderBy                  String                 Criteria used to sort the event swapped registrations. If < 0, DESC is used,
 *                                                         otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of even swapped registrations per page to return [1..n]
 *
 * @return Array of String                                 List of event swapped registrations filtered, an empty array otherwise
 */
 function dbSearchEventSwappedRegistration($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find event swapped registrations
     $Select = "SELECT esr.EventSwappedRegistrationID, esr.RequestorEventID, ereq.EventTitle AS RequestorEventTitle,
                ereq.EventStartDate AS RequestorEventStartDate, esr.RequestorFamilyID, freq.FamilyLastname AS RequestorFamilyLastname,
                esr.AcceptorEventID, eacc.EventTitle AS AcceptorEventTitle, eacc.EventStartDate AS AcceptorEventStartDate,
                esr.AcceptorFamilyID, facc.FamilyLastname AS AcceptorFamilyLastname, esr.EventSwappedRegistrationDate,
                esr.EventSwappedRegistrationClosingDate, esr.SupportMemberID";
     $From = "FROM EventSwappedRegistrations esr, Events ereq, Events eacc, Families freq, Families facc";
     $Where = " WHERE esr.RequestorFamilyID = freq.FamilyID AND esr.RequestorEventID = ereq.EventID
               AND esr.AcceptorFamilyID = facc.FamilyID AND esr.AcceptorEventID = eacc.EventID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< RequestorEventID field >>>
         if ((array_key_exists("RequestorEventID", $ArrayParams)) && (!empty($ArrayParams["RequestorEventID"])))
         {
             $Where .= " AND esr.RequestorEventID = ".$ArrayParams["RequestorEventID"];
         }

         // <<< AcceptorEventID field >>>
         if ((array_key_exists("AcceptorEventID", $ArrayParams)) && (!empty($ArrayParams["AcceptorEventID"])))
         {
             $Where .= " AND esr.AcceptorEventID = ".$ArrayParams["AcceptorEventID"];
         }

         // <<< RequestorFamilyID field >>>
         if ((array_key_exists("RequestorFamilyID", $ArrayParams)) && (!empty($ArrayParams["RequestorFamilyID"])))
         {
             $Where .= " AND esr.RequestorFamilyID = ".$ArrayParams["RequestorFamilyID"];
         }

         // <<< AcceptorFamilyID field >>>
         if ((array_key_exists("AcceptorFamilyID", $ArrayParams)) && (!empty($ArrayParams["AcceptorFamilyID"])))
         {
             $Where .= " AND esr.AcceptorFamilyID = ".$ArrayParams["AcceptorFamilyID"];
         }

         // <<< Event still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // A swap of registration with a creation date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND esr.EventSwappedRegistrationDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< SupportMemberID SupportMemberID >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (count($ArrayParams["EventRegistrationValided"]) > 0))
         {
             $Where .= " AND esr.SupportMemberID IN ".constructSQLINString($ArrayParams["SupportMemberID"]);
         }

         // <<< Option : get activated swaps of registrations >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated swaps
                 $Where .= " AND esr.EventSwappedRegistrationClosingDate IS NULL";
             }
             else
             {
                 // Not activated swaps
                 $Where .= " AND esr.EventSwappedRegistrationClosingDate IS NOT NULL";
             }
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
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY EventSwappedRegistrationID $Having $StrOrderBy $Limit");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "EventSwappedRegistrationID" => array(),
                                   "EventSwappedRegistrationDate" => array(),
                                   "EventSwappedRegistrationClosingDate" => array(),
                                   "RequestorEventID" => array(),
                                   "RequestorEventTitle" => array(),
                                   "RequestorEventStartDate" => array(),
                                   "RequestorFamilyID" => array(),
                                   "RequestorFamilyLastname" => array(),
                                   "AcceptorEventID" => array(),
                                   "AcceptorEventTitle" => array(),
                                   "AcceptorEventStartDate" => array(),
                                   "AcceptorFamilyID" => array(),
                                   "AcceptorFamilyLastname" => array(),
                                   "SupportMemberID" => array()
                                   );

             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
             {
                 $ArrayRecords["EventSwappedRegistrationID"][] = $Record["EventSwappedRegistrationID"];
                 $ArrayRecords["EventSwappedRegistrationDate"][] = $Record["EventSwappedRegistrationDate"];
                 $ArrayRecords["EventSwappedRegistrationClosingDate"][] = $Record["EventSwappedRegistrationClosingDate"];
                 $ArrayRecords["RequestorEventID"][] = $Record["RequestorEventID"];
                 $ArrayRecords["RequestorEventTitle"][] = $Record["RequestorEventTitle"];
                 $ArrayRecords["RequestorEventStartDate"][] = $Record["RequestorEventStartDate"];
                 $ArrayRecords["RequestorFamilyID"][] = $Record["RequestorFamilyID"];
                 $ArrayRecords["RequestorFamilyLastname"][] = $Record["RequestorFamilyLastname"];
                 $ArrayRecords["AcceptorEventID"][] = $Record["AcceptorEventID"];
                 $ArrayRecords["AcceptorEventTitle"][] = $Record["AcceptorEventTitle"];
                 $ArrayRecords["AcceptorEventStartDate"][] = $Record["AcceptorEventStartDate"];
                 $ArrayRecords["AcceptorFamilyID"][] = $Record["AcceptorFamilyID"];
                 $ArrayRecords["AcceptorFamilyLastname"][] = $Record["AcceptorFamilyLastname"];
                 $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of event swapped registrations filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-22
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the event swapped registrations
 *
 * @return Integer              Number of the event swapped registrations found, 0 otherwise
 */
 function getNbdbSearchEventSwappedRegistration($DbConnection, $ArrayParams)
 {
     // SQL request to find event swapped registrations
     $Select = "SELECT esr.EventSwappedRegistrationID";
     $From = "FROM EventSwappedRegistrations esr, Events ereq, Events eacc, Families freq, Families facc";
     $Where = " WHERE esr.RequestorFamilyID = freq.FamilyID AND esr.RequestorEventID = ereq.EventID
               AND esr.AcceptorFamilyID = facc.FamilyID AND esr.AcceptorEventID = eacc.EventID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< RequestorEventID field >>>
         if ((array_key_exists("RequestorEventID", $ArrayParams)) && (!empty($ArrayParams["RequestorEventID"])))
         {
             $Where .= " AND esr.RequestorEventID = ".$ArrayParams["RequestorEventID"];
         }

         // <<< AcceptorEventID field >>>
         if ((array_key_exists("AcceptorEventID", $ArrayParams)) && (!empty($ArrayParams["AcceptorEventID"])))
         {
             $Where .= " AND esr.AcceptorEventID = ".$ArrayParams["AcceptorEventID"];
         }

         // <<< RequestorFamilyID field >>>
         if ((array_key_exists("RequestorFamilyID", $ArrayParams)) && (!empty($ArrayParams["RequestorFamilyID"])))
         {
             $Where .= " AND esr.RequestorFamilyID = ".$ArrayParams["RequestorFamilyID"];
         }

         // <<< AcceptorFamilyID field >>>
         if ((array_key_exists("AcceptorFamilyID", $ArrayParams)) && (!empty($ArrayParams["AcceptorFamilyID"])))
         {
             $Where .= " AND esr.AcceptorFamilyID = ".$ArrayParams["AcceptorFamilyID"];
         }

         // <<< Event still activated for some school years
         if ((array_key_exists("SchoolYear", $ArrayParams)) && (count($ArrayParams["SchoolYear"]) > 0))
         {
             // A swap of registration with a creation date between school start and end dates
             $SchoolYearStartDate = getSchoolYearStartDate($ArrayParams["SchoolYear"][0]);
             $SchoolYearEndDate = getSchoolYearEndDate($ArrayParams["SchoolYear"][0]);

             $Where .= " AND esr.EventSwappedRegistrationDate BETWEEN \"$SchoolYearStartDate\" AND \"$SchoolYearEndDate\"";
         }

         // <<< SupportMemberID SupportMemberID >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (count($ArrayParams["EventRegistrationValided"]) > 0))
         {
             $Where .= " AND esr.SupportMemberID IN ".constructSQLINString($ArrayParams["SupportMemberID"]);
         }

         // <<< Option : get activated swaps of registrations >>>
         if (array_key_exists("Activated", $ArrayParams))
         {
             if ($ArrayParams["Activated"])
             {
                 // Activated swaps
                 $Where .= " AND esr.EventSwappedRegistrationClosingDate IS NULL";
             }
             else
             {
                 // Not activated swaps
                 $Where .= " AND esr.EventSwappedRegistrationClosingDate IS NOT NULL";
             }
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY EventSwappedRegistrationID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Give recipients for an event an a type of recipient, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-08
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $EventID                   Integer      ID of the event concerned by the e-mail notification [1..n]
 * @param $RecipientsType            Enum         Type of recipient [1..n]
 * @param $FamilyID                  Integer      ID of the family if concerned by the notification [1..n]
 *
 * @return Array of Strings          E-mail adresses for the notification, FALSE otherwise
 */
 function getEmailRecipients($DbConnection, $EventID, $RecipientsType = TO_ALL_FAMILIES_EVENT, $FamilyID = NULL)
 {
     $ArrayRecipients = array();

     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

     if (($EventID > 0) && (isExistingEvent($DbConnection, $EventID)))
     {
         $RecordEvent = getTableRecordInfos($DbConnection, 'Events', $EventID);
         switch($RecipientsType)
         {
             case TO_AUTHOR_EVENT:
                 // The recipient is the author of the event
                 $RecordSupporter = getSupportMemberInfos($DbConnection, $RecordEvent["SupportMemberID"]);
                 $ArrayRecipients[] = $RecordSupporter['SupportMemberEmail'];
                 unset($RecordSupporter);
                 break;

             case TO_FAMILY_EVENT:
                 // The recipient is the family registered to the event
                 if (!empty($FamilyID))
                 {
                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $FamilyID);
                     if (isset($RecordFamily['FamilyID']))
                     {
                         $ArrayRecipients[] = $RecordFamily['FamilyMainEmail'];
                         if (!empty($RecordFamily['FamilySecondEmail']))
                         {
                             $ArrayRecipients[] = $RecordFamily['FamilySecondEmail'];
                         }
                     }

                     unset($RecordFamily);
                 }
                 break;

             case TO_ALL_REGISTRERED_FAMILIES_EVENT:
                 // The recipients are all families registered to the event
                 $ArrayRegisteredFamilies = dbSearchEventRegistration($DbConnection, array("EventID" => $EventID,
                                                                                           "Activated" => TRUE), "FamilyLastname",
                                                                                           1, 0);
                 if (isset($ArrayRegisteredFamilies['EventRegistrationID']))
                 {
                     // Get e-mails of registered families
                     $DbResult = $DbConnection->query("SELECT f.FamilyID, f.FamilyMainEmail, f.FamilySecondEmail
                                                       FROM Families f WHERE f.FamilyID IN ".constructSQLINString($ArrayRegisteredFamilies['FamilyID']));
                     if (!DB::isError($DbResult))
                     {
                         if ($DbResult->numRows() > 0)
                         {
                             while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                             {
                                 $ArrayRecipients[] = $Record['FamilyMainEmail'];
                                 if (!empty($Record['FamilySecondEmail']))
                                 {
                                    $ArrayRecipients[] = $Record['FamilySecondEmail'];
                                 }
                             }
                         }
                     }
                 }
                 break;

             case TO_ALL_UNREGISTRERED_FAMILIES_EVENT:
                 // The recipients are all families not registered to the event
                 // First, we get activated families
                 $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear),
                                                                      "Activated" => TRUE), "FamilyLastname", 1, 0);

                 // Next, we get families registered to the event
                 $ArrayRegisteredFamilies = dbSearchEventRegistration($DbConnection, array("EventID" => $EventID,
                                                                                           "Activated" => TRUE), "FamilyLastname",
                                                                                           1, 0);
                 if (isset($ArrayRegisteredFamilies['EventRegistrationID']))
                 {
                     if (isset($ArrayFamilies['FamilyID']))
                     {
                         foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                         {
                             // We check if the current family is registered to the event
                             if (!in_array($FamilyID, $ArrayRegisteredFamilies['FamilyID']))
                             {
                                 // No : we keep this family (e-mails)
                                 $ArrayRecipients[] = $ArrayFamilies['FamilyMainEmail'][$f];
                                 if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                 {
                                     $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                                 }
                             }
                         }
                     }
                 }
                 else
                 {
                     // No registered families for the event : we keep all families
                     $ArrayRecipients = $ArrayFamilies['FamilyMainEmail'];
                     foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                     {
                         if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                         {
                             $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                         }
                     }
                 }
                 break;

             case TO_NO_INDICOOP_FAMILIES_FIRST_TO_ALL_UNREGISTRERED_FAMILIES_AFTER_EVENT:
                 /* The recipients are, in priority, families which aren't INDICOOP. If no families in this case or
                    not enough, the recipients are no registered families for the event */
                 // Get families which aren't INDICOOP (cooperation not OK)
                 $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear),
                                                                      "FamilyPbCoopContribution" => $CurrentSchoolYear,
                                                                      "Activated" => TRUE), "FamilyLastname", 1, 0);

                 if (isset($ArrayFamilies['FamilyID']))
                 {
                     if (count($ArrayFamilies['FamilyID']) > $RecordEvent['EventMaxParticipants'])
                     {
                         // Enough families
                         $ArrayRecipients = $ArrayFamilies['FamilyMainEmail'];
                         foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                         {
                             if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                             {
                                 $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                             }
                         }
                     }
                     else
                     {
                         // Not enough families, we get not registered families to this event
                         // First, we get activated families
                         $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear),
                                                                              "Activated" => TRUE), "FamilyLastname", 1, 0);

                         // Next, we get families registered to the event
                         $ArrayRegisteredFamilies = dbSearchEventRegistration($DbConnection, array("EventID" => $EventID,
                                                                                                   "Activated" => TRUE), "FamilyLastname",
                                                                                                   1, 0);
                         if (isset($ArrayRegisteredFamilies['EventRegistrationID']))
                         {
                             if (isset($ArrayFamilies['FamilyID']))
                             {
                                 foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                                 {
                                     // We check if the current family is registered to the event
                                     if (!in_array($FamilyID, $ArrayRegisteredFamilies['FamilyID']))
                                     {
                                         // No : we keep this family (e-mails)
                                         $ArrayRecipients[] = $ArrayFamilies['FamilyMainEmail'][$f];
                                         if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                         {
                                             $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                                         }
                                     }
                                 }
                             }
                         }
                         else
                         {
                             // No registered families for the event : we keep all families
                             $ArrayRecipients = $ArrayFamilies['FamilyMainEmail'];
                             foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                             {
                                 if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                 {
                                     $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                                 }
                             }
                         }
                     }
                 }
                 break;

             case TO_ALL_FAMILIES_EVENT:
             default:
                 // The recipients are all activated families for the current school year
                 $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear),
                                                                      "Activated" => TRUE), "FamilyLastname", 1, 0);

                 if (isset($ArrayFamilies['FamilyID']))
                 {
                     $ArrayRecipients = $ArrayFamilies['FamilyMainEmail'];
                     foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                     {
                         if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                         {
                             $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                         }
                     }
                 }
                 break;
         }

         return $ArrayRecipients;
     }
     elseif ((empty($EventID)) && ($RecipientsType == TO_NO_INDICOOP_FAMILIES_EVENT))
     {
         // Get families which aren't INDICOOP (cooperation not OK)
         $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear),
                                                              "FamilyPbCoopContribution" => $CurrentSchoolYear,
                                                              "Activated" => TRUE), "FamilyLastname", 1, 0);
         if (isset($ArrayFamilies['FamilyID']))
         {
             $ArrayRecipients = $ArrayFamilies['FamilyMainEmail'];
             foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
             {
                 if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                 {
                     $ArrayRecipients[] = $ArrayFamilies['FamilySecondEmail'][$f];
                 }
             }
         }

         return $ArrayRecipients;
     }

     // ERROR
     return FALSE;
 }
?>