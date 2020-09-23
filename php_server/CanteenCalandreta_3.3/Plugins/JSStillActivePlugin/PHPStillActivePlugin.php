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
 * JS plugin session still active module : check if the session of the logged
 * user is still active
 *
 * @author STNA/7SQ
 * @version 3.4
 *    - 2010-03-22 : taken into account the customer module and patch the bug
 *                   on login pages
 *
 * @since 2008-02-15
 */


 // Include Config.php because of the name of the session
 require_once dirname(__FILE__).'/../../Common/DefinedConst.php';
 require_once dirname(__FILE__).'/../../Common/Config.php';
 require_once dirname(__FILE__).'/../../Common/DbLibrary.php';

 session_start();

 // Get urls of login pages
 $ArrayLoginUrls = array(
                         $CONF_URL_SUPPORT,
                         $CONF_URL_SUPPORT.'index.php'
                        );

 // Support module
 foreach($CONF_REWRITING_URL_SUPPORT as $u => $Url)
 {
     $ArrayLoginUrls[] = $Url.'index.php';
 }

 // Add the server too
 $ServerName = 'localhost';
 if ((isset($_SERVER['SERVER_NAME'])) && (!empty($_SERVER['SERVER_NAME'])))
 {
     $ServerName = $_SERVER['SERVER_NAME'];
 }
 elseif ((isset($_SERVER['HOSTNAME'])) && (!empty($_SERVER['HOSTNAME'])))
 {
     $ServerName = $_SERVER['HOSTNAME'];
 }
 elseif ((isset($_SERVER['COMPUTERNAME'])) && (!empty($_SERVER['COMPUTERNAME'])))
 {
     $ServerName = $_SERVER['COMPUTERNAME'];
 }
 elseif ((isset($_SERVER['HTTP_HOST'])) && (!empty($_SERVER['HTTP_HOST'])))
 {
     $ServerName = $_SERVER['HTTP_HOST'];
 }

 $ArrayLoginUrls[] = "http://$ServerName/";

 if ((in_array($_SERVER['HTTP_REFERER'], $ArrayLoginUrls)) || (isset($_SESSION["CustomerTmp"])) || (isset($_SESSION["SupportMemberID"])) || (isset($_SESSION["CustomerID"])))
 {
     // OK, the session is still active
     echo '200';
 }
 else
 {
     // Error, the session is destroyed
     echo '503';
 }
?>

