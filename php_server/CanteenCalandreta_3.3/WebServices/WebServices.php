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
 * Web services module : access point to all web services (REST)
 *
 * @author STNA/7SQ
 * @version 3.6
 * @since 2010-02-08
 */


 require_once dirname(__FILE__).'/../Common/DefinedConst.php';
 require_once dirname(__FILE__).'/../Common/Config.php';
 require_once dirname(__FILE__).'/../Common/FctLibrary.php';
 require_once dirname(__FILE__).'/../GUI/GiXMLLibrary.php';
 require_once dirname(__FILE__).'/../Languages/SetLanguage.php';
 require_once dirname(__FILE__).'/../Workflows/AowWorkflow.php';
 require_once dirname(__FILE__).'/WSLibrary.php';
 require_once("DB.php");

 // We detect the source of the call to a web service
 $GETPOSTParams = array();
 $SOURCEParams = '';
 if (!empty($_GET))
 {
     $GETPOSTParams = $_GET;
     $SOURCEParams = 'GET';
 }
 elseif (!empty($_POST))
 {
     $GETPOSTParams = $_POST;
     $SOURCEParams = 'POST';
 }

 if (!empty($GETPOSTParams))
 {
     // We get the requested web service
     if (isset($GETPOSTParams['Service']))
     {
         $WebService = getUrlParam($GETPOSTParams['Service'], 'LOWER');

         // We check if its service exists
         if (array_key_exists($WebService, $CONF_WEB_SERVICES))
         {
             // Get the request sub web service
             if (isset($GETPOSTParams['SubService']))
             {
                 $SubService = getUrlParam($GETPOSTParams['SubService'], 'LOWER');

                 // We check if its sub service exists
                 if (array_key_exists($SubService, $CONF_WEB_SERVICES[$WebService]))
                 {
                     // We check all required parameters are set
                     $ServiceParams = array();
                     $bContinue = TRUE;
                     if (!empty($CONF_WEB_SERVICES[$WebService][$SubService][WEB_SERVICE_PARAMS]))
                     {
                         foreach($CONF_WEB_SERVICES[$WebService][$SubService][WEB_SERVICE_PARAMS] as $s => $Param)
                         {
                             $Param = strtolower($Param);
                             if (isset($GETPOSTParams[$Param]))
                             {
                                 // Get the value of the parameter
                                 $ServiceParams[$Param] = getUrlParam($GETPOSTParams[$Param]);
                             }
                             else
                             {
                                 // Error : parameter not found
                                 $bContinue = FALSE;
                             }
                         }
                     }

                     if ($bContinue)
                     {
                         // Open the database connection
                         $DbCon = dbConnection();

                         // Authentification requested?
                         switch($CONF_WEB_SERVICES[$WebService][$SubService][WEB_SERVICE_AUTH_TYPE])
                         {
                             case WS_AUTH_NONE:
                                 // No authentification need
                                 break;

                             case WS_AUTH_CUSTOMER:
                                 // Customer authentification need
                                 if (isset($GETPOSTParams['Key']))
                                 {
                                     $WebServiceKey = getUrlParam($GETPOSTParams['Key']);
                                     $CustomerID = getCustomerByWebServiceKey($DbCon, $WebServiceKey);
                                     if ($CustomerID > 0)
                                     {
                                         $ServiceParams['session_customerid'] = $CustomerID;
                                     }
                                     else
                                     {
                                         // Error : wrong key
                                         $bContinue = FALSE;
                                     }
                                 }
                                 else
                                 {
                                     // Error : no key
                                     $bContinue = FALSE;
                                 }
                                 break;

                             case WS_AUTH_SUPPORT:
                                 // Supporter authentification need
                                 if (isset($GETPOSTParams['Key']))
                                 {
                                     $WebServiceKey = getUrlParam($GETPOSTParams['Key']);
                                     $SupportMemberID = getSupportMemberByWebServiceKey($DbCon, $WebServiceKey);
                                     if ($SupportMemberID > 0)
                                     {
                                         $ServiceParams['session_supportmemberid'] = $SupportMemberID;
                                     }
                                     else
                                     {
                                         // Error : wrong key
                                         $bContinue = FALSE;
                                     }
                                 }
                                 else
                                 {
                                     // Error : no key
                                     $bContinue = FALSE;
                                 }
                                 break;
                         }

                         // We can launch the web service
                         if (($bContinue) && (file_exists(dirname(__FILE__)."/$WebService/Service.php")))
                         {
                             require_once dirname(__FILE__)."/$WebService/Service.php";
                             $ServiceToLaunch = 'Run'.ucfirst($WebService).ucfirst($SubService);
                             if (function_exists($ServiceToLaunch))
                             {
                                 // Add GET/POST content in parameters of the service
                                 $ServiceParams['GETPOST'] = $GETPOSTParams;
                                 $ServiceParams['SOURCE'] = $SOURCEParams;

                                 call_user_func(
                                                $ServiceToLaunch,
                                                $DbCon,
                                                $CONF_WEB_SERVICES[$WebService][$SubService][WEB_SERVICE_CONFIG],
                                                $ServiceParams
                                               );
                             }
                         }

                         // Release the connection to the database
                         dbDisconnection($DbCon);
                     }
                 }
             }
         }
     }
 }
 else
 {
     session_start();
     
     // Open the database connection
     $DbCon = dbConnection();

     // Get the key of the customer or supporter
     $Key = "<em>UserKey</em>";
     if (isset($_SESSION['SupportMemberID']))
     {
         $Key = getTableFieldValue($DbCon, 'SupportMembers', $_SESSION['SupportMemberID'], 'SupportMemberWebServiceKey');
         if (($Key == -1) || (empty($Key)))
         {
             // The user hasn't key
             $Key = "<em>UserKey</em>";
         }
     }
     elseif (isset($_SESSION['CustomerID']))
     {
         $Key = getTableFieldValue($DbCon, 'Customers', $_SESSION['CustomerID'], 'CustomerWebServiceKey');
         if (($Key == -1) || (empty($Key)))
         {
             // The user hasn't key
             $Key = "<em>UserKey</em>";
         }
     }

     // Release the connection to the database
     dbDisconnection($DbCon);

     // Display the pattern of an url aout a web service
     echo $CONF_ROOT_DIRECTORY."WebServices/WebServices.php?Service=<em>ServiceName</em>&amp;SubService=<em>SubServiceName</em>&amp;Key=$Key";
 }
?>
