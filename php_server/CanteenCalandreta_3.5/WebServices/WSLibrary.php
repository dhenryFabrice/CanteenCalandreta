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
 * Web services module : library of functions usefull for web services and mail services
 *
 * @author STNA/7SQ
 * @version 3.6
 * @since 2011-03-14
 */


 // Constantes
 define('URL_MAX_SIZE', 2000);  // Max length of an url : used to send data by the method GET (< URL_MAX_SIZE) or POST (> URL_MAX_SIZE)

 // Includes
 require_once dirname(__FILE__).'/../Common/Snoopy/Snoopy.class.php';


/**
 * Send data thanks to the method GET or POST, in relation with the length of the data
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-03-22
 *
 * @param $Url      String               Url to send data
 * @param $Data     Array of Strings     Associative array with names of aprameters and values
 */
 function sendData($Url, $Data = array())
 {
     if (empty($Data))
     {
         // We use the method GET
         header("Location: $Url");
     }
     else
     {
         // Serialize data to check the length
         $sGetParams = http_build_query($Data);
         if (strlen($Url) + strlen($sGetParams) + 1 < URL_MAX_SIZE)
         {
             // We use the method GET
             header("Location: $Url?$sGetParams");
         }
         else
         {
             // We use the method POST (to large data to send)
             $Snoopy = new Snoopy();
             $Snoopy->httpmethod = 'POST';
             $Snoopy->submit($Url, $Data);
             echo $Snoopy->results;
         }
     }
 }


/**
 * Get the value coming from a parameter in an url (POST or GET) and format it
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-03-21
 *
 * @param $ParamValue      String           Value of the parameter to extract
 * @param $Mode            String           Mode to format extracted data (NONE, LOWER, UPPER)
 *
 * @return String         Formatted value of the extracted parameter
 */
 function getUrlParam($ParamValue, $Mode = 'NONE')
 {
     // Extract the value of the parameter
     $ParamValue = strip_tags(trim($ParamValue));
     switch (strtoupper($Mode))
     {
         case 'LOWER':
             $ParamValue = strtolower($ParamValue);
             break;

         case 'UPPER':
             $ParamValue = strtoupper($ParamValue);
             break;
     }

     return $ParamValue;
 }


/**
 * Encode XML data to be put in a parameter of an url
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-03-21
 *
 * @param $Xml            String           XML data to encode
 *
 * @return String         XML encoded to be put in a parameter of an url
 */
 function xmlUrlEncode($Xml)
 {
     return rawurlencode(base64_encode($Xml));
 }


/**
 * Decode XML data set in a parameter of an url
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-03-21
 *
 * @param $Xml            String           XML data to decode
 *
 * @return String         XML decoded from a parameter of an url
 */
 function xmlUrlDecode($Xml)
 {
     if (empty($Xml))
     {
         return '';
     }
     else
     {
         $Xml = getUrlParam($Xml);
         $Xml = html_entity_decode(base64_decode(rawurldecode($Xml)));

         return $Xml;
     }
 }


/**
 * Format XML data to be used by a parser
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-03-21
 *
 * @param $Xml            String           XML data to decode
 *
 * @return String         XML prepared to be used by a parser
 */
 function xmlPrepareToBeParsed($Xml)
 {
     if (empty($Xml))
     {
         return '';
     }
     else
     {
         $Xml = str_replace(array("<br />", "&nbsp;"), array("\n", " "), $Xml);

         return $Xml;
     }
 }


/**
 * Generate the XML of an object stored in the datatabase
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-02-28
 *
 * @param $DbConnection         DB object        Object of the opened database connection
 * @param $TableName            String           Name of the table where the object is
 * @param $ObjectID             Integer          ID of the object stored in the database [1..n]
 * @param $ArrayForeignKeys     Mixed array      Parameters to get infos thanks to foreign keys
 * @param $DatabaseName         String           Name of the database (if different from the current)
 *
 * @return String               The object in XML format, FALSE otherwise
 */
 function streamToXML($DbConnection, $TableName, $ObjectID, $ArrayForeignKeys = array(), $DatabaseName = '')
 {
     if ((!empty($TableName)) && ($ObjectID > 0))
     {
         // Tag for each table
         $ArrayXmsTags = array(
                               'Aow' => 'aow',
                               'Constructors' => 'constructor',
                               'ConstructosCalls' => 'constructorcall',
                               'Customers' => 'customer',
                               'Sites' => 'site',
                               'Subdivisions' => 'subdivision',
                               'SupportMembers' => 'supportmember'
                              );

         // Get details of the object
         $RecordObject = getTableRecordInfos($DbConnection, $TableName, $ObjectID, array(), $DatabaseName);

         if (isset($ArrayXmsTags[$TableName]))
         {
             $StartTag = strtolower($ArrayXmsTags[$TableName]);
         }
         else
         {
             $StartTag = strtolower($TableName);
         }

         $ArrayForeignKeysNames = array_keys($ArrayForeignKeys);

         $Xml = "<$StartTag>";
         switch($TableName)
         {
             default:
             case 'Aow':
                 foreach($RecordObject as $FieldName => $FieldValue)
                 {
                     if ((!empty($FieldValue)) && (in_array($FieldName, $ArrayForeignKeysNames)))
                     {
                         // Foreign key : get infos about this ID
                         $RecordForeignKey = getTableRecordInfos($DbConnection, $ArrayForeignKeys[$FieldName]['Table'], $FieldValue,
                                                                 array(), $DatabaseName);
                         if ($RecordForeignKey === FALSE)
                         {
                             // Infos not found
                             $TagName = strtolower($FieldName);
                             $Xml .= "<$TagName>";
                             $Xml .= htmlentities($FieldValue);
                             $Xml .= "</$TagName>";
                         }
                         else
                         {
                             if (isset($ArrayXmsTags[$ArrayForeignKeys[$FieldName]['Table']]))
                             {
                                 $FKStartTag = strtolower($ArrayXmsTags[$ArrayForeignKeys[$FieldName]['Table']]);
                             }
                             else
                             {
                                 $FKStartTag = strtolower($ArrayForeignKeys[$FieldName]['Table']);
                             }

                             $Xml .= "<$FKStartTag"."details>";
                             foreach($RecordForeignKey as $FKName => $FKValue)
                             {
                                 $TagName = strtolower($FKName);
                                 $Xml .= "<$TagName>";
                                 if (!empty($FKValue))
                                 {
                                     $FKValue = htmlentities($FKValue);
                                 }
                                 $Xml .= $FKValue;
                                 $Xml .= "</$TagName>";
                             }

                             $Xml .= "</$FKStartTag"."details>";
                         }
                     }
                     else
                     {
                         // Simple field
                         $TagName = strtolower($FieldName);
                         $Xml .= "<$TagName>";
                         if (!empty($FieldValue))
                         {
                             $FieldValue = htmlentities($FieldValue);
                         }
                         $Xml .= $FieldValue;
                         $Xml .= "</$TagName>";
                     }
                 }
                 break;
         }

         $Xml .= "</$StartTag>";

         return $Xml;
     }

     // Error
     return FALSE;
 }


 class ArrayToXML
 {
        /**
         * The main function for converting to an XML document.
         * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
         *
         * @param array $data
         * @param string $rootNodeName - what you want the root node to be - defaultsto data.
         * @param string $encoding - charset to use
         * @param SimpleXMLElement $xml - should only be used recursively
         *
         * @return string XML
         */
        public static function toXml($data, $rootNodeName = 'root', $encoding = 'iso-8859-1', $xml=null)
        {
            // turn off compatibility mode as simple xml throws a wobbly if you don't.
            if (ini_get('zend.ze1_compatibility_mode') == 1)
            {
                ini_set ('zend.ze1_compatibility_mode', 0);
            }

            if ($xml == null)
            {
                $xml = simplexml_load_string("<?xml version='1.0' encoding='$encoding'?><".strtolower($rootNodeName)." />");
            }

            // loop through the data passed in.
            foreach($data as $key => $value)
            {
                // no numeric keys in our xml please!
                if (is_numeric($key))
                {
                    // make string key...
                    $key = "unknownNode_". (string) $key;
                }

                // replace anything not alpha numeric
                $key = strtolower(preg_replace('/[^a-z]/i', '', $key));

                // if there is another array found recrusively call this function
                if (is_array($value))
                {
                    $node = $xml->addChild($key);
                    // recrusive call.
                    ArrayToXML::toXml($value, $rootNodeName, $encoding, $node);
                }
                else
                {
                    // add single node.
                    $value = htmlentities($value);
                    $xml->addChild($key, $value);
                }

            }

            // pass back as string. or simple xml object if you want!
            return $xml->asXML();
        }
 }


/**
 * Send a e-mail for a new ask of work
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-03-25
 *
 * @param $DbConnection         DB object        Object of the opened database connection
 * @param $AowID                Integer          ID of the ask of work [1..n])
 *
 * @return Boolean              TRUE if the e-mail is sent, FALSE otherwise
 */
 function sendWSNewAowNotification($DbConnection, $AowID)
 {
     $bIsEmailSent = FALSE;
     if ($AowID > 0)
     {
         // Get infos about the ask of work
         $RecordAow = getAowDetails($DbConnection, $AowID);

         // Get it's current status
         $RecordCurrentAowStatus = getCurrentStatusNameOfAow($DbConnection, $AowID);
         if ($GLOBALS['CONF_AOW_NOTIFICATIONS'][$RecordCurrentAowStatus['AowStatusID']][$RecordCurrentAowStatus['AowStatusID']][$RecordAow['AowTreeLevel']][$RecordAow['AowTypeID']][Template] != '')
         {
             // Send e-mail of notification
             $Subject = stripslashes($RecordAow['AowSubject']);
             $Ref = $RecordAow['AowRef'];
             if (!$GLOBALS['CONF_USE_AOW_REFERENCE'])
             {
                 $Ref = $AowID;
             }

             $sProjectName = getProjectName($DbConnection, $RecordAow['ProjectID']);

             $EmailSubject = $GLOBALS['CONF_EMAIL_OBJECTS_SUBJECT_PREFIX'][OBJ_AOW]."[$sProjectName] ".$GLOBALS['CONF_CRITICITIES'][$RecordAow['AowCriticity']]." : $Ref - $Subject";
             $ReplaceInTemplate = array(
                                        array(
                                              "{LANG_SUBJECT}", "{AowSubject}", "{LANG_AOW_TYPE}", "{AowTypeName}", "{LANG_REFERENCE}",
                                              "{AowRef}", "{LANG_CRITICITY}", "{AowCriticity}", "{LANG_DEADLINE}", "{AowDeadline}",
                                              "{LANG_STATUS}", "{AowStatusName}", "{url}", "{title}", "{link}"
                                             ),
                                        array(
                                              $GLOBALS['LANG_SUBJECT'], $Subject, $GLOBALS['LANG_AOW_TYPE'],
                                              getAowTypeName($DbConnection, $RecordAow['AowTypeID']), $GLOBALS['LANG_REFERENCE'], $Ref,
                                              $GLOBALS['LANG_CRITICITY'], $GLOBALS['CONF_CRITICITIES'][$RecordAow['AowCriticity']],
                                              $GLOBALS['LANG_WISHED_DEADLINE'], date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                              strtotime($RecordAow['AowWishedDeadline'])), $GLOBALS['LANG_STATUS'],
                                              $RecordCurrentAowStatus['AowStatusName'],
                                              $GLOBALS['CONF_URL_SUPPORT']."AowFollow/DetailsAow.php?Cr=".md5($Ref)."&Id=$Ref",
                                              $GLOBALS['LANG_VIEW_DETAILS_AOW_INSTRUCTIONS'],
                                              $GLOBALS['LANG_VIEW_DETAILS_AOW_INSTRUCTIONS']
                                             )
                                       );

             // We get the support members states who can create a customer ask of work, to create the mailing list
             $ArraySupportMembersStates = array();
             foreach($GLOBALS['CREATE_RULES'] as $i => $CurrentValue)
             {
                 if ($GLOBALS['CREATE_RULES'][$i][0] == 'y')
                 {
                     $ArraySupportMembersStates[] = $i;
                 }
             }

             // Now, we get the e-mail adresses of the supporters whom have a support member state in the previous array
             $SqlInCondution = constructSQLINString($ArraySupportMembersStates);
             if (!empty($SqlInCondution))
             {
                 // We get the e-mail adresses
                 $DbResult = $DbConnection->query("SELECT SupportMemberEmail FROM SupportMembers WHERE SupportMemberActivated = 1
                                                  AND SupportMemberStateID IN $SqlInCondution");
                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() > 0)
                     {
                         $MailingList = array('to' => array());

                         while ($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                         {
                             $MailingList['to'][] = $Record['SupportMemberEmail'];
                         }

                         // Take into account support members in copy of the e-mail
                         $MailingList["cc"] = array();
                         foreach($GLOBALS['CONF_AOW_NOTIFICATIONS'][$RecordCurrentAowStatus['AowStatusID']][$RecordCurrentAowStatus['AowStatusID']][$RecordAow['AowTreeLevel']][$RecordAow['AowTypeID']][Cc] as $i => $CurrentValue)
                         {
                             $RecordSupportMemberInfos = getSupportMemberInfos($DbConnection, $CurrentValue);
                             $MailingList['cc'][] = $RecordSupportMemberInfos['SupportMemberEmail'];
                         }

                         // DEBUG MODE
                         if ($GLOBALS["CONF_MODE_DEBUG"])
                         {
                             $MailingList['to'] = array_merge(array($GLOBALS['CONF_EMAIL_INTRANET_EMAIL_ADDRESS']), $MailingList['to']);
                         }

                         // We can send the e-mail
                         $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject,
                                                   $GLOBALS['CONF_AOW_NOTIFICATIONS'][$RecordCurrentAowStatus['AowStatusID']][$RecordCurrentAowStatus['AowStatusID']][$RecordAow['AowTreeLevel']][$RecordAow['AowTypeID']][Template],
                                                   $ReplaceInTemplate);

                         // Other e-mail send to the users put in copy (because of the hyperlink)
                         if (!empty($RecordAow['AowCustomersInCopy']))
                         {
                             $MailingList['to'] = array($GLOBALS['CONF_EMAIL_INTRANET_EMAIL_ADDRESS']);

                             // Users in copy
                             $MailingList['cc'] = array_merge($MailingList['cc'], explode(',', $RecordAow['AowCustomersInCopy']));

                             $ReplaceInTemplate = array(
                                                        array(
                                                              "{LANG_SUBJECT}", "{AowSubject}", "{LANG_AOW_TYPE}", "{AowTypeName}",
                                                              "{LANG_REFERENCE}", "{AowRef}", "{LANG_CRITICITY}", "{AowCriticity}",
                                                              "{LANG_DEADLINE}", "{AowDeadline}", "{LANG_STATUS}", "{AowStatusName}",
                                                              "{url}", "{title}", "{link}"
                                                             ),
                                                        array(
                                                              $GLOBALS['LANG_SUBJECT'], $Subject, $GLOBALS['LANG_AOW_TYPE'],
                                                              getAowTypeName($DbConnection, $RecordAow['AowTypeID']), $GLOBALS['LANG_REFERENCE'], $Ref,
                                                              $GLOBALS['LANG_CRITICITY'], $GLOBALS['CONF_CRITICITIES'][$RecordAow['AowCriticity']],
                                                              $GLOBALS['LANG_WISHED_DEADLINE'], date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                              strtotime($RecordAow['AowWishedDeadline'])), $GLOBALS['LANG_STATUS'],
                                                              $RecordCurrentAowStatus['AowStatusName'],
                                                              $GLOBALS['CONF_URL_AOW1']."AowFollow/DetailsAow.php?Cr=".md5($Ref)."&Id=$Ref",
                                                              $GLOBALS['LANG_VIEW_DETAILS_AOW_INSTRUCTIONS'],
                                                              $GLOBALS['LANG_VIEW_DETAILS_AOW_INSTRUCTIONS']
                                                             )

                                                       );

                             // We can send the e-mail
                             $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject,
                                                       $GLOBALS['CONF_AOW_NOTIFICATIONS'][$RecordCurrentAowStatus['AowStatusID']][$RecordCurrentAowStatus['AowStatusID']][$RecordAow['AowTreeLevel']][$RecordAow['AowTypeID']][Template],
                                                       $ReplaceInTemplate);
                         }
                     }
                 }
             }
         }
     }

     return $bIsEmailSent;
 }
?>
