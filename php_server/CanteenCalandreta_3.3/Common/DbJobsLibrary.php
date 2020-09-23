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
 * Common module : library of database functions used for the jobs and job parameters tables
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2016-06-28
 */


/**
 * Check if a job exists in the Jobs table, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-28
 *
 * @param $DbConnection         DB object    Object of the opened database connection
 * @param $JobID                Integer      ID of the job searched [1..n]
 *
 * @return Boolean              TRUE if the job exists, FALSE otherwise
 */
 function isExistingJob($DbConnection, $JobID)
 {
     $DbResult = $DbConnection->query("SELECT JobID FROM Jobs WHERE JobID = $JobID");
     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() == 1)
         {
             // The job exists
             return TRUE;
         }
     }

     // The job doesn't exist
     return FALSE;
 }


/**
 * Add a job with its parameters in the Jobs table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-28
 *
 * @param $DbConnection              DB object        Object of the opened database connection
 * @param $SupportMemberID           Integer          ID of the supporter, author of the job [1..n]
 * @param $JobType                   Integer          Type of the job [1..n]
 * @param $JobPlannedDate            Date             Planned date and time to execute (yyyy-mm-dd hh:mm:ss)
 * @param $JobExecutionDate          Date             Execution date and time of the job (yyyy-mm-dd hh:mm:ss)
 * @param $JobNbTries                Integer          Number of tries to execute the job [0..n]
 * @param $JobResult                 String           Result of the execution of the job
 * @param $ArrayParams               Mixed array      Contains the parameters of the job to execute
 *
 * @return Integer                   The primary key of the job [1..n], 0 otherwise
 */
 function dbAddJob($DbConnection, $SupportMemberID, $JobType, $JobPlannedDate, $JobExecutionDate = NULL, $JobNbTries = 0, $JobResult = NULL, $ArrayParams = array())
 {
     if ((!empty($JobPlannedDate)) && ($SupportMemberID > 0) && ($JobType > 0) && ($JobNbTries >= 0) && (!empty($ArrayParams)))
     {
         // Check if the JobPlannedDate is valide
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $JobPlannedDate) == 0)
         {
             return 0;
         }
         else
         {
             $JobPlannedDate = ", JobPlannedDate = \"$JobPlannedDate\"";
         }

         if (empty($JobExecutionDate))
         {
             $JobExecutionDate = ", JobExecutionDate = NULL";
         }
         else
         {
             $JobExecutionDate = ", JobExecutionDate = \"$JobExecutionDate\"";
         }

         if (empty($JobResult))
         {
             $JobResult = ", JobResult = NULL";
         }
         else
         {
             $JobResult = ", JobResult = \"$JobResult\"";
         }

         // It's a new job
         $id = getNewPrimaryKey($DbConnection, "Jobs", "JobID");
         if ($id != 0)
         {
             $DbResult = $DbConnection->query("INSERT INTO Jobs SET JobID = $id, SupportMemberID = $SupportMemberID,
                                               JobType = $JobType, JobNbTries = $JobNbTries $JobPlannedDate $JobExecutionDate
                                               $JobResult");

             if (!DB::isError($DbResult))
             {
                 // Now, we create the parameters of the job
                 foreach($ArrayParams as $p => $CurrentParams)
                 {
                     if ((isset($CurrentParams['JobParameterName'])) && (isset($CurrentParams['JobParameterValue'])))
                     {
                         dbAddJobParameter($DbConnection, $id, $CurrentParams['JobParameterName'], $CurrentParams['JobParameterValue']);
                     }
                 }

                 return $id;
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Update an existing job in the Jobs table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-28
 *
 * @param $DbConnection              DB object        Object of the opened database connection
 * @param $JobID                     Integer          ID of the job to update [1..n]
 * @param $SupportMemberID           Integer          ID of the supporter, author of the job [1..n]
 * @param $JobType                   Integer          Type of the job [1..n]
 * @param $JobPlannedDate            Date             Planned date and time to execute (yyyy-mm-dd hh:mm:ss)
 * @param $JobExecutionDate          Date             Execution date and time of the job (yyyy-mm-dd hh:mm:ss)
 * @param $JobNbTries                Integer          Number of tries to execute the job [0..n]
 * @param $JobResult                 String           Result of the execution of the job
 *
 * @return Integer                   The primary key of the job [1..n], 0 otherwise
 */
 function dbUpdateJob($DbConnection, $JobID, $SupportMemberID = NULL, $JobType = NULL, $JobPlannedDate = NULL, $JobExecutionDate = NULL, $JobNbTries = 0, $JobResult = NULL)
 {
     // The parameters which are NULL will be ignored for the update
     $ArrayParamsUpdate = array();

     // Verification of the parameters
     if (($JobID < 1) || (!isInteger($JobID)))
     {
         // ERROR
         return 0;
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

     if (!is_Null($JobType))
     {
         if (($JobType < 1) || (!isInteger($JobType)))
         {
             // ERROR
             return 0;
         }
         else
         {
             // The JobType field will be updated
             $ArrayParamsUpdate[] = "JobType = $JobType";
         }
     }

     // Check if the JobPlannedDate is valide
     if (!is_null($JobPlannedDate))
     {
         if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $JobPlannedDate) == 0)
         {
             return 0;
         }
         else
         {
             // The JobPlannedDate field will be updated
             $ArrayParamsUpdate[] = "JobPlannedDate = \"$JobPlannedDate\"";
         }
     }

     // Check if the JobExecutionDate is valide
     if (!is_null($JobExecutionDate))
     {
         if (empty($JobExecutionDate))
         {
             // The JobExecutionDate field will be updated
             $ArrayParamsUpdate[] = "JobExecutionDate = NULL";
         }
         else
         {
             if (preg_match("[\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d]", $JobExecutionDate) == 0)
             {
                 return 0;
             }
             else
             {
                 // The JobExecutionDate field will be updated
                 $ArrayParamsUpdate[] = "JobExecutionDate = \"$JobExecutionDate\"";
             }
         }
     }

     if (!is_null($JobNbTries))
     {
         if ($JobNbTries < 0)
         {
             // ERROR
             return 0;
         }
         else
         {
             // The JobResult field will be updated
             $ArrayParamsUpdate[] = "JobNbTries = $JobNbTries";
         }
     }

     if (!is_null($JobResult))
     {
         if (empty($JobResult))
         {
             // The JobResult field will be updated
             $ArrayParamsUpdate[] = "JobResult = NULL";
         }
         else
         {
             // The JobResult field will be updated
             $ArrayParamsUpdate[] = "JobResult = \"$JobResult\"";
         }
     }

     // Here, the parameters are correct, we check if the job exists
     if (isExistingJob($DbConnection, $JobID))
     {
         // The job exists : we can update if there is at least 1 parameter
         if (count($ArrayParamsUpdate) > 0)
         {
             $DbResult = $DbConnection->query("UPDATE Jobs SET ".implode(", ", $ArrayParamsUpdate)." WHERE JobID = $JobID");
             if (!DB::isError($DbResult))
             {
                 // Job updated
                 return $JobID;
             }
         }
         else
         {
             // The update isn't usefull
             return $JobID;
         }
     }

     // ERROR
     return 0;
 }


/**
 * Add a job parameter in the JobParameters table
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-28
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $JobID                     Integer      ID of the job to update [1..n]
 * @param $JobParameterName          String       Name of the parameter of the job
 * @param $JobParameterValue         String       Value of the parameter of the job
 *
 * @return Integer                   The primary key of the job parameter [1..n], 0 otherwise
 */
 function dbAddJobParameter($DbConnection, $JobID, $JobParameterName, $JobParameterValue)
 {
     if (($JobID > 0) && (!empty($JobParameterName)))
     {
         // Check if the job parameter is a new job parameter
         $DbResult = $DbConnection->query("SELECT JobParameterID FROM JobParameters WHERE JobParameterName = \"$JobParameterName\"
                                           AND JobID = $JobID");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() == 0)
             {
                 if (empty($JobParameterValue))
                 {
                     $JobParameterValue = "";
                 }
                 else
                 {
                     $JobParameterValue = ", JobParameterValue = \"$JobParameterValue\"";
                 }

                 // It's a new job parameter
                 $id = getNewPrimaryKey($DbConnection, "JobParameters", "JobParameterID");
                 if ($id != 0)
                 {
                     $DbResult = $DbConnection->query("INSERT INTO JobParameters SET JobParameterID = $id, JobID = $JobID,
                                                       JobParameterName = \"$JobParameterName\" $JobParameterValue");

                     if (!DB::isError($DbResult))
                     {
                         return $id;
                     }
                 }
             }
             else
             {
                 // The job parameter already exists
                 $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);
                 return $Record['JobParameterID'];
             }
         }
     }

     // ERROR
     return 0;
 }


/**
 * Get jobs with or without thier parameters, filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2017-09-22 : taken into account "JobWithoutParameters" parameter
 *
 * @since 2016-06-28
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains the criterion used to filter the jobs
 * @param $OrderBy                  String                 Criteria used to sort the jobs. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                     Integer                Number of the page to return [1..n]
 * @param $RecordsPerPage           Integer                Number of jobs per page to return [1..n]
 *
 * @return Array of String                                 List of jobs filtered, an empty array otherwise
 */
 function dbSearchJobs($DbConnection, $ArrayParams, $OrderBy = "", $Page = 1, $RecordsPerPage = 10)
 {
     // SQL request to find jobs
     $bWithoutParameters = FALSE;
     if ((array_key_exists("JobWithoutParameters", $ArrayParams)) && ($ArrayParams["JobWithoutParameters"]))
     {
         // To get only jobs, without their parameters
         $bWithoutParameters = TRUE;
     }

     if ($bWithoutParameters)
     {
         $Select = "SELECT j.JobID, j.JobPlannedDate, j.JobExecutionDate, j.JobType, j.JobNbTries, j.JobResult, j.SupportMemberID,
                    sm.SupportMemberLastname, sm.SupportMemberFirstname";
     }
     else
     {
         $Select = "SELECT j.JobID, j.JobPlannedDate, j.JobExecutionDate, j.JobType, j.JobNbTries, j.JobResult, j.SupportMemberID,
                    sm.SupportMemberLastname, sm.SupportMemberFirstname, jp.JobParameterName, jp.JobParameterValue";
     }

     $From = "FROM SupportMembers sm, Jobs j LEFT JOIN JobParameters jp ON (j.JobID = jp.JobID)";
     $Where = "WHERE JobNbTries >= 0 AND j.SupportMemberID = sm.SupportMemberID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< JobID field >>>
         if ((array_key_exists("JobID", $ArrayParams)) && (!empty($ArrayParams["JobID"])))
         {
             if (is_array($ArrayParams["JobID"]))
             {
                 $Where .= " AND j.JobID IN ".constructSQLINString($ArrayParams["JobID"]);
             }
             else
             {
                 $Where .= " AND j.JobID = ".$ArrayParams["JobID"];
             }
         }

         // <<< JobPlannedDate between 2 given dates >>>
         if ((array_key_exists("JobPlannedStartDate", $ArrayParams)) && (count($ArrayParams["JobPlannedStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobPlannedDate ".$ArrayParams["JobPlannedStartDate"][1]." \"".$ArrayParams["JobPlannedStartDate"][0]."\"";
         }

         if ((array_key_exists("JobPlannedEndDate", $ArrayParams)) && (count($ArrayParams["JobPlannedEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobPlannedDate ".$ArrayParams["JobPlannedEndDate"][1]." \"".$ArrayParams["JobPlannedEndDate"][0]."\"";
         }

         // <<< JobExecutionDate between 2 given dates >>>
         if ((array_key_exists("JobExecutionDate", $ArrayParams)) && (count($ArrayParams["JobExecutionDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobExecutionDate ".$ArrayParams["JobExecutionDate"][1]." \"".$ArrayParams["JobExecutionDate"][0]."\"";
         }

         if ((array_key_exists("JobExecutionEndDate", $ArrayParams)) && (count($ArrayParams["JobExecutionEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobExecutionDate ".$ArrayParams["JobExecutionEndDate"][1]." \"".$ArrayParams["JobExecutionEndDate"][0]."\"";
         }

         // <<< Option : get executed jobs >>>
         if (array_key_exists("Executed", $ArrayParams))
         {
             if ($ArrayParams["Executed"])
             {
                 //  Executed jobs
                 $Where .= " AND j.JobExecutionDate IS NOT NULL";
             }
             else
             {
                 // Not executed jobs
                 $Where .= " AND j.JobExecutionDate IS NULL";
             }
         }

         // <<< JobType field >>>
         if ((array_key_exists("JobType", $ArrayParams)) && (count($ArrayParams["JobType"]) > 0))
         {
             $Where .= " AND j.JobType IN ".constructSQLINString($ArrayParams["JobType"]);
         }

         // <<< JobResult field >>>
         if ((array_key_exists("JobResult", $ArrayParams)) && (count($ArrayParams["JobResult"]) > 0))
         {
             if (count($ArrayParams["JobResult"]) == 1)
             {
                 if (empty($ArrayParams["JobResult"][0]))
                 {
                     $Where .= " AND j.JobResult IS NULL";
                 }
                 else
                 {
                     $Where .= " AND j.JobResult = ".$ArrayParams["JobResult"][0];
                 }
             }
             else
             {
                 $Where .= " AND j.JobResult IN ".constructSQLINString($ArrayParams["JobResult"]);
             }
         }

         // <<< SupportMemberID >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (count($ArrayParams["SupportMemberID"]) > 0))
         {
             $Where .= " AND j.SupportMemberID IN ".constructSQLINString($ArrayParams["SupportMemberID"]);
         }

         // <<< SupportMemberLastname field >>>
         if ((array_key_exists("SupportMemberLastname", $ArrayParams)) && (!empty($ArrayParams["SupportMemberLastname"])))
         {
             $Where .= " AND sm.SupportMemberLastname LIKE \"".$ArrayParams["SupportMemberLastname"]."\"";
         }

         // <<< JobParameterName field >>>
         if ((array_key_exists("JobParameterName", $ArrayParams)) && (!empty($ArrayParams["JobParameterName"])))
         {
             $Where .= " AND jp.JobParameterName LIKE \"".$ArrayParams["JobParameterName"]."\"";
         }
     }

     // We take into account the page and the number of jobs per page
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


     if ($bWithoutParameters)
     {
         $DbResult = $DbConnection->query("$Select $From $Where GROUP BY j.JobID $Having $StrOrderBy $Limit");
     }
     else
     {
         $DbResult = $DbConnection->query("$Select $From $Where $Having $StrOrderBy $Limit");
     }

     if (!DB::isError($DbResult))
     {
         if ($DbResult->numRows() != 0)
         {
             // Creation of the result array
             $ArrayRecords = array(
                                   "JobID" => array(),
                                   "JobPlannedDate" => array(),
                                   "JobExecutionDate" => array(),
                                   "JobType" => array(),
                                   "JobNbTries" => array(),
                                   "JobResult" => array(),
                                   "SupportMemberID" => array(),
                                   "SupportMemberLastname" => array(),
                                   "SupportMemberFirstname" => array()
                                  );

             if ($bWithoutParameters)
             {
                 while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     $ArrayRecords["JobID"][] = $Record["JobID"];
                     $ArrayRecords["JobPlannedDate"][] = $Record["JobPlannedDate"];
                     $ArrayRecords["JobExecutionDate"][] = $Record["JobExecutionDate"];
                     $ArrayRecords["JobType"][] = $Record["JobType"];
                     $ArrayRecords["JobNbTries"][] = $Record["JobNbTries"];
                     $ArrayRecords["JobResult"][] = $Record["JobResult"];
                     $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
                     $ArrayRecords["SupportMemberLastname"][] = $Record["SupportMemberLastname"];
                     $ArrayRecords["SupportMemberFirstname"][] = $Record["SupportMemberFirstname"];
                 }
             }
             else
             {
                 $ArrayRecords["JobParameters"] = array();

                 $PreviousJobID = NULL;
                 $iCurrJobIndex = -1;
                 while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     if ($PreviousJobID != $Record["JobID"])
                     {
                         // New job
                         $iCurrJobIndex++;
                         $ArrayRecords["JobID"][] = $Record["JobID"];
                         $ArrayRecords["JobPlannedDate"][] = $Record["JobPlannedDate"];
                         $ArrayRecords["JobExecutionDate"][] = $Record["JobExecutionDate"];
                         $ArrayRecords["JobType"][] = $Record["JobType"];
                         $ArrayRecords["JobNbTries"][] = $Record["JobNbTries"];
                         $ArrayRecords["JobResult"][] = $Record["JobResult"];
                         $ArrayRecords["SupportMemberID"][] = $Record["SupportMemberID"];
                         $ArrayRecords["SupportMemberLastname"][] = $Record["SupportMemberLastname"];
                         $ArrayRecords["SupportMemberFirstname"][] = $Record["SupportMemberFirstname"];

                         $PreviousJobID = $Record["JobID"];
                     }

                     $ArrayRecords["JobParameters"][$iCurrJobIndex][] = array(
                                                                              "JobParameterName" => $Record["JobParameterName"],
                                                                              "JobParameterValue" => $Record["JobParameterValue"]
                                                                             );
                 }
             }

             // Return result
             return $ArrayRecords;
         }
     }

     // ERROR
     return array();
 }


/**
 * Get the number of jobs filtered by some criterion
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-22
 *
 * @param $DbConnection         DB object              Object of the opened database connection
 * @param $ArrayParams          Mixed array            Contains the criterion used to filter the jobs
 *
 * @return Integer              Number of the jobs found, 0 otherwise
 */
 function getNbdbSearchJobs($DbConnection, $ArrayParams)
 {
     // SQL request to find jobs
     $Select = "SELECT j.JobID";
     $From = "FROM SupportMembers sm, Jobs j LEFT JOIN JobParameters jp ON (j.JobID = jp.JobID)";
     $Where = "WHERE j.SupportMemberID = sm.SupportMemberID";
     $Having = "";

     if (count($ArrayParams) >= 0)
     {
         // <<< JobID field >>>
         if ((array_key_exists("JobID", $ArrayParams)) && (!empty($ArrayParams["JobID"])))
         {
             if (is_array($ArrayParams["JobID"]))
             {
                 $Where .= " AND j.JobID IN ".constructSQLINString($ArrayParams["JobID"]);
             }
             else
             {
                 $Where .= " AND j.JobID = ".$ArrayParams["JobID"];
             }
         }

         // <<< JobPlannedDate between 2 given dates >>>
         if ((array_key_exists("JobPlannedStartDate", $ArrayParams)) && (count($ArrayParams["JobPlannedStartDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobPlannedDate ".$ArrayParams["JobPlannedStartDate"][1]." \"".$ArrayParams["JobPlannedStartDate"][0]."\"";
         }

         if ((array_key_exists("JobPlannedEndDate", $ArrayParams)) && (count($ArrayParams["JobPlannedEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobPlannedDate ".$ArrayParams["JobPlannedEndDate"][1]." \"".$ArrayParams["JobPlannedEndDate"][0]."\"";
         }

         // <<< JobExecutionDate between 2 given dates >>>
         if ((array_key_exists("JobExecutionDate", $ArrayParams)) && (count($ArrayParams["JobExecutionDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobExecutionDate ".$ArrayParams["JobExecutionDate"][1]." \"".$ArrayParams["JobExecutionDate"][0]."\"";
         }

         if ((array_key_exists("JobExecutionEndDate", $ArrayParams)) && (count($ArrayParams["JobExecutionEndDate"]) == 2))
         {
             // [0] -> date, [1] -> operator (>, <, >=...)
             $Where .= " AND j.JobExecutionDate ".$ArrayParams["JobExecutionEndDate"][1]." \"".$ArrayParams["JobExecutionEndDate"][0]."\"";
         }

         // <<< Option : get executed jobs >>>
         if (array_key_exists("Executed", $ArrayParams))
         {
             if ($ArrayParams["Executed"])
             {
                 //  Executed jobs
                 $Where .= " AND j.JobExecutionDate IS NOT NULL";
             }
             else
             {
                 // Not executed jobs
                 $Where .= " AND j.JobExecutionDate IS NULL";
             }
         }

         // <<< JobType field >>>
         if ((array_key_exists("JobType", $ArrayParams)) && (count($ArrayParams["JobType"]) > 0))
         {
             $Where .= " AND j.JobType IN ".constructSQLINString($ArrayParams["JobType"]);
         }

         if ((array_key_exists("JobResult", $ArrayParams)) && (count($ArrayParams["JobResult"]) > 0))
         {
             if (count($ArrayParams["JobResult"]) == 1)
             {
                 if (empty($ArrayParams["JobResult"][0]))
                 {
                     $Where .= " AND j.JobResult IS NULL";
                 }
                 else
                 {
                     $Where .= " AND j.JobResult = ".$ArrayParams["JobResult"][0];
                 }
             }
             else
             {
                 $Where .= " AND j.JobResult IN ".constructSQLINString($ArrayParams["JobResult"]);
             }
         }

         // <<< SupportMemberID >>>
         if ((array_key_exists("SupportMemberID", $ArrayParams)) && (count($ArrayParams["SupportMemberID"]) > 0))
         {
             $Where .= " AND j.SupportMemberID IN ".constructSQLINString($ArrayParams["SupportMemberID"]);
         }

         // <<< SupportMemberLastname field >>>
         if ((array_key_exists("SupportMemberLastname", $ArrayParams)) && (!empty($ArrayParams["SupportMemberLastname"])))
         {
             $Where .= " AND sm.SupportMemberLastname LIKE \"".$ArrayParams["SupportMemberLastname"]."\"";
         }

         // <<< JobParameterName field >>>
         if ((array_key_exists("JobParameterName", $ArrayParams)) && (!empty($ArrayParams["JobParameterName"])))
         {
             $Where .= " AND jp.JobParameterName LIKE \"".$ArrayParams["JobParameterName"]."\"";
         }
     }

     // We can launch the SQL request
     $DbResult = $DbConnection->query("$Select $From $Where GROUP BY j.JobID $Having");
     if (!DB::isError($DbResult))
     {
         return $DbResult->numRows();
     }

     // ERROR
     return 0;
 }


/**
 * Delete a job with its parameters, thanks to its ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-28
 *
 * @param $DbConnection              DB object    Object of the opened database connection
 * @param $JobID                     Integer      ID of the job to delete [1..n]
 *
 * @return Boolean                   TRUE if the job and its parameters are deleted, FALSE otherwise
 */
 function dbDeleteJob($DbConnection, $JobID)
 {
     // The parameters are correct?
     if ($JobID > 0)
     {
         // Delete the parameters of the job
         $DbResult = $DbConnection->query("DELETE FROM JobParameters WHERE JobID = $JobID");
         if (!DB::isError($DbResult))
         {
             // Delete the job
             $DbResult2 = $DbConnection->query("DELETE FROM Jobs WHERE JobID = $JobID");
             if (!DB::isError($DbResult2))
             {
                 // Job and parameters deleted
                 return TRUE;
             }
         }
     }

     // ERROR
     return FALSE;
 }
?>