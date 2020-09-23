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
 * Common module : library of functions used to manage sessions in database (Sessions table)
 * or in files
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : DB.php is loaded in DbLibrary.php
 *
 * @since 2009-03-19
 */


/**
 * Open the session (initialization). Create the connection to the database.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-03-19
 *
 * @param $path        String    Path where sessions must be saved
 * @param $name        String    Name of the session
 *
 * @return Boolean     TRUE if the session is opened, FALSE otherwise
 */
 function openDBSession($path, $name)
 {
     global $DbCon;

     // Create the connection if it isn't set
     if ((!isset($DbCon)) || (empty($DbCon)))
     {
         $DbCon = dbConnection();
     }

     return TRUE;
 }


/**
 * Close the session.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-03-19
 *
 * @return Boolean     TRUE if the session is closed, FALSE otherwise
 */
 function closeDBSession()
 {
     return TRUE;
 }


/**
 * Get the content of the session. Read the session in the database.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-03-19
 *
 * @param $sid         String    SID of the session (32 characters)
 *
 * @return String      Content of the session, empty string otherwise
 */
 function readDBSession($sid)
 {
     global $DbCon;

     // Create the connection if it isn't set
     if ((!isset($DbCon)) || (empty($DbCon)))
     {
         $DbCon = dbConnection();
     }

     $sData = "";
     if (preg_match('/^[0-9a-z]{32}$/i', $sid))
     {
         // We search the data of the session
         $DbResult = $DbCon->query("SELECT SessionData FROM Sessions WHERE SessionID = \"$sid\" AND SessionExpirationDate > NOW()");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 1)
             {
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 $sData = $Record["SessionData"];
             }
         }
     }

     return $sData;
 }


/**
 * Save the content of the session. Write the session in the database.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-03-19
 *
 * @param $sid          String    SID of the session (32 characters)
 * @param $sdata        String    Content of the session, serialized
 *
 * @return Boolean      TRUE if the session is saved in database,
 *                      FALSE otherwise
 */
 function writeDBSession($sid, $sdata)
 {
     global $DbCon;

     // Create the connection if it isn't set
     if ((!isset($DbCon)) || (empty($DbCon)))
     {
         $DbCon = dbConnection();
     }

     if (preg_match('/^[0-9a-z]{32}$/i', $sid))
     {
         // We chek if the session already exists
         $DbResult = $DbCon->query("SELECT SessionID FROM Sessions WHERE SessionID = \"$sid\"");

         if (!DB::isError($DbResult))
         {
             $sdata = addslashes($sdata);
             $CurrentDateTime = date('Y-m-d H:i:s');
             $ExpirationDateTime = date('Y-m-d H:i:s', strtotime($CurrentDateTime) + ($GLOBALS["CONF_SESSIONS_LIFETIME"] * 3600));

             if ($DbResult->numRows() == 0)
             {
                 // We create the session
                 $DbResult = $DbCon->query("INSERT INTO Sessions SET SessionID = \"$sid\", SessionData = \"$sdata\",
                                           SessionCreationDate = \"$CurrentDateTime\", SessionUpdateDate = \"$CurrentDateTime\",
                                           SessionExpirationDate = \"$ExpirationDateTime\"");
             }
             else
             {
                 // We update the session
                 $DbResult = $DbCon->query("UPDATE Sessions SET SessionData = \"$sdata\", SessionUpdateDate = \"$CurrentDateTime\",
                                           SessionExpirationDate = \"$ExpirationDateTime\" WHERE SessionID = \"$sid\"");
             }

             if (!DB::isError($DbResult))
             {
                 return TRUE;
             }
         }
     }

     // Error
     return FALSE;
 }


/**
 * Destroy the session. Delete the session in the database.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-03-19
 *
 * @param $sid          String    SID of the session (32 characters)
 *                                to delete
 *
 * @return Boolean      TRUE if the session is deleted in database,
 *                      FALSE otherwise
 */
 function destroyDBSession($sid)
 {
     global $DbCon;

     // Create the connection if it isn't set
     if ((!isset($DbCon)) || (empty($DbCon)))
     {
         $DbCon = dbConnection();
     }

     if (preg_match('/^[0-9a-z]{32}$/i', $sid))
     {
         $DbResult = $DbCon->query("DELETE FROM Sessions WHERE SessionID = \"$sid\"");
         if (!DB::isError($DbResult))
         {
             return TRUE;
         }
     }

     // Error
     return FALSE;
 }


/**
 * Destroy all expired sessions. Delete all expired sessions in the database.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-03-19
 *
 * @param $smaxtime        Integer    Maxtime of the lifetime of the sessions
 *
 * @return Boolean         TRUE if the expired sessions are deleted in database,
 *                         FALSE otherwise
 */
 function gcDBSession($smaxtime)
 {
     global $DbCon;

     // Create the connection if it isn't set
     if ((!isset($DbCon)) || (empty($DbCon)))
     {
         $DbCon = dbConnection();
     }

     $DbResult = $DbCon->query("DELETE FROM Sessions WHERE SessionExpirationDate <= NOW()");
     if (!DB::isError($DbResult))
     {
         return TRUE;
     }

     // Error
     return FALSE;
 }


/**
 * Open the session (initialization).
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-23
 *
 * @param $path        String    Path where sessions must be saved
 * @param $name        String    Name of the session
 *
 * @return Boolean     TRUE if the session is opened, FALSE otherwise
 */
 function openFileSession($path, $name)
 {
     global $sess_save_path;

     $sess_save_path = $path;

     return TRUE;
 }


/**
 * Close the session.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-23
 *
 * @return Boolean     TRUE if the session is closed, FALSE otherwise
 */
 function closeFileSession()
 {
     return TRUE;
 }


/**
 * Get the content of the session. Read the session in the file.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-23
 *
 * @param $sid         String    SID of the session (32 characters)
 *
 * @return String      Content of the session, empty string otherwise
 */
 function readFileSession($sid)
 {
     global $sess_save_path;

     $sData = "";
     if (preg_match('/^[0-9a-z]{32}$/i', $sid))
     {
         $sess_file =  "$sess_save_path/".session_name()."_$sid.sess";
         return (string) @file_get_contents($sess_file);
     }

     return $sData;
 }


/**
 * Save the content of the session. Write the session in the file.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-23
 *
 * @param $sid          String    SID of the session (32 characters)
 * @param $sdata        String    Content of the session, serialized
 *
 * @return Boolean      TRUE if the session is saved in file,
 *                      FALSE otherwise
 */
 function writeFileSession($sid, $sdata)
 {
     global $sess_save_path;

     if (preg_match('/^[0-9a-z]{32}$/i', $sid))
     {
         $sess_file =  "$sess_save_path/".session_name()."_$sid.sess";
         if ($fp = @fopen($sess_file, "w"))
         {
             $return = fwrite($fp, $sdata);
             fclose($fp);
             return $return;
         }
     }

     // Error
     return FALSE;
 }


/**
 * Destroy the session. Delete the session file.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-23
 *
 * @param $sid          String    SID of the session (32 characters)
 *                                to delete
 *
 * @return Boolean      TRUE if the session file is deleted,
 *                      FALSE otherwise
 */
 function destroyFileSession($sid)
 {
     global $sess_save_path;

     if (preg_match('/^[0-9a-z]{32}$/i', $sid))
     {
         $sess_file =  "$sess_save_path/".session_name()."_$sid.sess";
         return @unlink($sess_file);
     }

     // Error
     return FALSE;
 }


/**
 * Destroy all expired sessions. Delete all expired session files.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-23
 *
 * @param $smaxtime        Integer    Maxtime of the lifetime of the sessions
 *
 * @return Boolean         TRUE if the expired session files are deleted,
 *                         FALSE otherwise
 */
 function gcFileSession($smaxtime)
 {
     global $sess_save_path;

     foreach(glob("$sess_save_path/".session_name()."_*") as $filename)
     {
         if (filemtime($filename) + $maxlifetime < time())
         {
             @unlink($filename);
         }
     }

     return TRUE;
 }
?>
