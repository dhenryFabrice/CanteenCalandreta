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
 * Common module : library of functions used for create configuration variables from database,
 * before coming from Config.php
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-11-02
 */


/**
 * Convert an associative array in a string with a usable PHP syntax (for instance, to use the array
 * in the Config.php file)
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-10
 *
 * @param $ArrayTree        Mixed array       Associative array to convert in PHP syntax
 * @param $Level            Integer           Current level in the tree of the associative array
 * @param $NbSpaces         Integer           Number of white spaces to display in front of an element
 *                                            of the array [0..n]
 * @param $Prefix           String            Prefix to fisplay in front of each element of the array
 *
 * @return String           The associative array in a usable PHP syntax
 */
 function generateArrayTree($ArrayTree, $Level = 1, $NbSpaces = 8, $Prefix = '')
 {
     $sOutput = '';
     $ArrayTreeKeys = array_keys($ArrayTree);
     $iArrayTreeSize = count($ArrayTreeKeys);
     foreach($ArrayTreeKeys as $k => $Key)
     {
         $Element = $ArrayTree[$Key];

         if (is_array($Element))
         {
             $sOutput .= $Prefix.str_repeat("&nbsp;", ($Level - 1) * $NbSpaces);

             if (isInteger($Key))
             {
                 $sOutput .= $Key;
             }
             else
             {
                 $sOutput .= "\"$Key\"";
             }

             $sResult = generateArrayTree($Element, $Level + 1, $NbSpaces, $Prefix);
             if (empty($sResult))
             {
                 $sOutput .= " => Array()";
             }
             else
             {
                 $sOutput .= " => Array(<br />\n";
                 $sOutput .= $sResult;
                 $sOutput .= $Prefix.str_repeat("&nbsp;", (($Level - 1) * $NbSpaces) + strlen("$Key => Array(")).")";
             }

             if ($k < $iArrayTreeSize - 1)
             {
                 // It's not the last element
                 $sOutput .= ",";
             }

             $sOutput .= "<br />\n";
         }
         else
         {
             $sOutput .= $Prefix.str_repeat("&nbsp;", ($Level - 1) * $NbSpaces);

             if (isInteger($Key))
             {
                 $sOutput .= $Key;
             }
             else
             {
                 $sOutput .= "\"$Key\"";
             }

             $sOutput .= " => ";

             // Check the type of value
             if ((isInteger($Element)) || (isFloat($Element)))
             {
                 $sOutput .= $Element;
             }
             elseif (is_string($Element))
             {
                 $sOutput .= "\"$Element\"";
             }
             else
             {
                 $sOutput .= $Element;
             }

             if ($k < $iArrayTreeSize - 1)
             {
                 // It's not the last element
                 $sOutput .= ",";
             }

             $sOutput .= "<br />\n";
         }
     }

     return $sOutput;
 }


/**
 * Give the value well formated and with the right charset
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-08
 *
 * @param $Value            String       Value to format
 *
 * @return String           The value formatted
 */
 function getXmlNodeValue($Value)
 {
     if ((strToUpper($GLOBALS['CONF_CHARSET']) == 'ISO-8859-1') && (mb_detect_encoding($Value, 'UTF-8') == 'UTF-8'))
     {
         return trim(utf8_decode($Value));
     }
     else
     {
         return trim($Value);
     }
 }


/**
 * Give real child nodes of a given node
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-08
 *
 * @param $Node                 DOMNode       Node for which we want child nodes
 *
 * @return Array of DOMNode     List of real child nodes
 */
 function getXmlChildren($Node)
 {
     $Children = array();
     if ($Node->hasChildNodes())
     {
         foreach ($Node->childNodes as $childNode)
         {
             // We keep only DOMElement nodes
             if ($childNode->nodeType == XML_ELEMENT_NODE)
             {
                 $Children[] = $childNode;
             }
         }
     }

     return $Children;
 }


/**
 * Give keys for an associative array in relation with the full path of a node
 * (to store the value of the node in the associative array)
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-08
 *
 * @param $Node                 DOMNode       Node for which we want keys
 * @param $FullPath             Boolean       True to get the complete path of the node,
 *                                            False to keep only some parent nodes
 *
 * @return Array of Strings     List of keys for an associative array
 */
 function getXmlArrayKeys($Node, $FullPath = TRUE)
 {
     $ArrayNodePath = array();
     $bKeepNode = TRUE;

     do
     {
         if ($Node->nodeType == XML_ELEMENT_NODE)
         {
             $bKeepNode = TRUE;
             if (!$FullPath)
             {
                 // We keep only nodes without the "keep" attribute or with "keep" attribute != "0"
                 $KeepAttrValue = $Node->getAttribute('keep');
                 if ($KeepAttrValue === "0")
                 {
                     $bKeepNode = FALSE;
                 }
             }

             if ($bKeepNode)
             {
                 array_unshift($ArrayNodePath, $Node);
             }
         }

         $Node = $Node->parentNode;
     } while (!is_null($Node));

     $ArrayKeys = array();
     $sPath = '';
     foreach($ArrayNodePath as $n => $ParentNode)
     {
         if ($n > 0)
         {
             if (!empty($sPath))
             {
                 $sPath .= "/";
             }

             $sPath .= $ParentNode->nodeName;

             // The key will be the tag name or the value of the "id" attribute if exists
             $AttrIDValue = $ParentNode->getAttribute('id');
             if (empty($AttrIDValue))
             {
                 // No ID : we use the tag name as key
                 $FinalKey = getXmlNodeValue($ParentNode->nodeName);
             }
             else
             {
                 // There is an ID to use as key
                 $FinalKey = getXmlNodeValue($AttrIDValue);
             }

             // Now, we check the type of the key
             $AttrIDTypeValue = $ParentNode->getAttribute('idtype');
             if (empty($AttrIDTypeValue))
             {
                 // No ID type : so, the key is a string or ingeter
                 $ArrayKeys[] = $FinalKey;
             }
             else
             {
                 // The key has a ID type
                 switch(strToLower($AttrIDTypeValue))
                 {
                     case 'const':
                         // The key is a PHP constant declared in the application
                         $iConst = eval("return ".$FinalKey.";");
                         $ArrayKeys[] = $iConst;
                         break;

                     default:
                         // The key is a string or ingeter
                         $ArrayKeys[] = $FinalKey;
                         break;
                 }
             }
         }
     }

     return $ArrayKeys;
 }


/**
 * Set the value of a node in the right keys of an associative array in relation with
 * the given path (list of keys)
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-09
 *
 * @param $ArrayResult          Mixed array            Contains the stored values of nodes
 * @param $ArrayPath            Array of Strings       List of keys (path in the associative
 *                                                     array to store the given value),
 * @param $Value                String                 Value to store in the associative array
 */
 function setXMLValueFromPath(&$ArrayResult, $ArrayPath, $Value, $Type = NULL)
 {
     $Dest = &$ArrayResult;
     $FinalKey = array_pop($ArrayPath);
     foreach($ArrayPath as $Key)
     {
         $Dest = &$Dest[$Key];
     }

     if (isset($Dest[$FinalKey]))
     {
         if (is_array($Dest[$FinalKey]))
         {
             // The value is already an array : we add the new value
             $Dest[$FinalKey][] = $Value;
         }
         else
         {
             // The value is a single value : we convert the key in array
             $Dest[$FinalKey] = array($Dest[$FinalKey], $Value);
         }
     }
     else
     {
         // Single value
         switch(strToLower($Type))
         {
             case 'array':
                 if (is_array($Value))
                 {
                     $Dest[$FinalKey] = $Value;
                 }
                 else
                 {
                     $Dest[$FinalKey] = array($Value);
                 }
                 break;

             default:
                 $Dest[$FinalKey] = $Value;
                 break;
         }
     }
 }


/**
 * Convert a node to an associative array (to store the value of the node in
 * the associative array)
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-08
 *
 * @param $Node                 DOMNode       Node for which we want keys
 *
 * @return Mixed Array          Node and child nodes converted in an associative array
 *                              in relation with tag names and id
 */
 function getXmlToArray($Node)
 {
     $ArrayXML = getXmlChildren($Node);
     $CurrentIndexXML = 0;
     $ArrayXMLSize = count($ArrayXML);

     while ($CurrentIndexXML < $ArrayXMLSize)
     {
         // Get children of the current node
         $Children = getXmlChildren($ArrayXML[$CurrentIndexXML]);
         if (empty($Children))
         {
             // It's a leaf
             $ArrayXML[$CurrentIndexXML]->setAttribute('isLeaf', TRUE);
         }
         else
         {
             $CurrentIndexToInsert = $CurrentIndexXML;
             $ArrayXML[$CurrentIndexXML]->setAttribute('isLeaf', FALSE);

             foreach($Children as $ChildNode)
             {
                 $ArrayXML = array_insertElement($ArrayXML, $ChildNode, $CurrentIndexToInsert);

                 $CurrentIndexToInsert++;
                 $ArrayXMLSize++;
             }
         }

         $CurrentIndexXML++;
     }

     $ArrayResult = array();
     foreach($ArrayXML as $n => $CurrentNode)
     {
         // We store value of each leaf node in the right keys of the associative array
         if ($CurrentNode->getAttribute('isLeaf'))
         {
             // Get keys in relation with the path of the node in the XML tree
             $ArrayPath = getXmlArrayKeys($CurrentNode, FALSE);

             $NodeValue = getXmlNodeValue($CurrentNode->nodeValue);

             $ValueType = NULL;
             if (!is_null($CurrentNode->parentNode))
             {
                 // Get the type of value (specified on the parent node)
                 $ValueType = getXmlNodeValue($CurrentNode->parentNode->getAttribute('type'));
                 if (empty($ValueType))
                 {
                     // In the case of a parent node with a type of value but without child nodes
                     $ValueType = getXmlNodeValue($CurrentNode->getAttribute('type'));

                     // To specify a default value in relation with the type of value
                     // if the node has an empty value
                     switch(strToLower($ValueType))
                     {
                         case 'array':
                             $NodeValue = array();
                             break;
                     }
                 }
             }

             // Store its value in the keyrs of the associative array
             setXMLValueFromPath($ArrayResult, $ArrayPath, $NodeValue, $ValueType);
         }
     }

     return $ArrayResult;
 }


/**
 * Load config parameters from database in config variables coming from Config.php
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-02
 *
 * @param $DbConnection             DB object              Object of the opened database connection
 * @param $ArrayParams              Mixed array            Contains names of config parameters to load.
 *                                                         If empty, load all config variables
 */
 function loadDbConfigParameters($DbConnection, $ArrayParams = array())
 {
     // These config variables come from Config.php
     global $CONF_SCHOOL_YEAR_START_DATES, $CONF_GRADES, $CONF_CLASSROOMS, $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS,
            $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS, $CONF_CANTEEN_PRICES, $CONF_NURSERY_PRICES,
            $CONF_NURSERY_DELAYS_PRICES, $CONF_DONATION_TAX_RECEIPT_PARAMETERS;

     if (empty($ArrayParams))
     {
         // We load all config variables
         $ArrayParams = array(
                              'CONF_SCHOOL_YEAR_START_DATES',
                              'CONF_CLASSROOMS',
                              'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                              'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                              'CONF_CANTEEN_PRICES',
                              'CONF_NURSERY_PRICES',
                              'CONF_NURSERY_DELAYS_PRICES',
                              'CONF_DONATION_TAX_RECEIPT_PARAMETERS'
                             );
     }

     foreach($ArrayParams as $p => $ParamName)
     {
         $RecordParamValue = getConfigParameterInfos($DbConnection, $ParamName);
         if (isset($RecordParamValue['ConfigParameterID']))
         {
             switch(strToUpper($ParamName))
             {
                 case 'CONF_SCHOOL_YEAR_START_DATES':
                     // Reinit
                     $CONF_SCHOOL_YEAR_START_DATES = array();

                     // Load start date of each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $CONF_SCHOOL_YEAR_START_DATES[$SchoolYear->getAttribute('id')] = getXmlNodeValue($SchoolYear->nodeValue);
                             }

                             unset($XmlParser, $SchoolYears);
                             break;
                     }
                     break;

                 case 'CONF_CLASSROOMS':
                     // Reinit
                     $CONF_CLASSROOMS = array();

                     // Load classrooms for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $Classrooms = $SchoolYear->getElementsByTagName('classroom');
                                 foreach($Classrooms as $Classroom)
                                 {
                                     $CONF_CLASSROOMS[$SchoolYear->getAttribute('id')][] = getXmlNodeValue($Classroom->nodeValue);
                                 }
                             }

                             unset($XmlParser, $SchoolYears, $Classrooms);
                             break;
                     }
                     break;

                 case 'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS':
                     // Reinit
                     $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS = array();

                     // Load annual contributions amounts for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $SY_ID = $SchoolYear->getAttribute('id');
                                 $Amounts = $SchoolYear->getElementsByTagName('amount');
                                 foreach($Amounts as $Amount)
                                 {
                                     $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SY_ID][$Amount->getAttribute('nbvotes')] = getXmlNodeValue($Amount->nodeValue);
                                 }
                             }

                             unset($XmlParser, $SchoolYears, $Amounts);
                             break;
                     }
                     break;

                 case 'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS':
                     // Reinit
                     $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS = array();

                     // Load monthly contributions amounts for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $SY_ID = $SchoolYear->getAttribute('id');
                                 if ($SchoolYear->hasChildNodes())
                                 {
                                     // Get contribution modes for this school year
                                     foreach($SchoolYear->childNodes as $ChildNode)
                                     {
                                         if ($ChildNode->nodeType != XML_TEXT_NODE)
                                         {
                                             if ($ChildNode->attributes->length > 0)
                                             {
                                                 // This attribute is a constant : contribution modes !!!
                                                 $iMode = eval("return ".getXmlNodeValue($ChildNode->attributes->item(0)->nodeValue).";");
                                                 if ($ChildNode->hasChildNodes())
                                                 {
                                                     // Get amounts in relation with nb of children for this contribution mode
                                                     foreach($ChildNode->childNodes as $SubChildNode)
                                                     {
                                                         if ($SubChildNode->nodeType != XML_TEXT_NODE)
                                                         {
                                                             if ($SubChildNode->attributes->length > 0)
                                                             {
                                                                 $iNbChildren = getXmlNodeValue($SubChildNode->attributes->item(0)->nodeValue);
                                                                 $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SY_ID][$iMode][$iNbChildren] = getXmlNodeValue($SubChildNode->nodeValue);
                                                             }
                                                             else
                                                             {
                                                                 $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SY_ID][$iMode][] = getXmlNodeValue($SubChildNode->nodeValue);
                                                             }
                                                         }
                                                     }
                                                 }
                                                 else
                                                 {
                                                     if ($ChildNode->nodeType != XML_TEXT_NODE)
                                                     {
                                                         $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SY_ID][$iMode][] = getXmlNodeValue($ChildNode->nodeValue);
                                                     }
                                                 }
                                             }
                                             else
                                             {
                                                 $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SchoolYear->getAttribute('id')][] = getXmlNodeValue($ChildNode->nodeValue);
                                             }
                                         }
                                     }
                                 }
                                 else
                                 {
                                     // No value
                                     $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS[$SchoolYear->getAttribute('id')] = array();
                                 }
                             }

                             unset($XmlParser, $SchoolYears);
                             break;
                     }
                     break;

                 case 'CONF_CANTEEN_PRICES':
                     // Reinit
                     $CONF_CANTEEN_PRICES = array();

                     // Load canteen prices for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $SY_ID = $SchoolYear->getAttribute('id');
                                 if ($SchoolYear->hasChildNodes())
                                 {
                                     foreach($SchoolYear->childNodes as $ChildNode)
                                     {
                                         if ($ChildNode->nodeType != XML_TEXT_NODE)
                                         {
                                             $CONF_CANTEEN_PRICES[$SY_ID][] = getXmlNodeValue($ChildNode->nodeValue);
                                         }
                                     }
                                 }
                                 else
                                 {
                                     // No value
                                     $CONF_CANTEEN_PRICES[$SchoolYear->getAttribute('id')] = array();
                                 }
                             }

                             unset($XmlParser, $SchoolYears);
                             break;
                     }
                     break;

                 case 'CONF_NURSERY_PRICES':
                     // Reinit
                     $CONF_NURSERY_PRICES = array();

                     // Load nursery prices for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $SY_ID = $SchoolYear->getAttribute('id');
                                 if ($SchoolYear->hasChildNodes())
                                 {
                                     foreach($SchoolYear->childNodes as $ChildNode)
                                     {
                                         if ($ChildNode->nodeType != XML_TEXT_NODE)
                                         {
                                             $CONF_NURSERY_PRICES[$SY_ID][] = getXmlNodeValue($ChildNode->nodeValue);
                                         }
                                     }
                                 }
                                 else
                                 {
                                     // No value
                                     $CONF_NURSERY_PRICES[$SY_ID] = array();
                                 }
                             }

                             unset($XmlParser, $SchoolYears);
                             break;
                     }
                     break;

                 case 'CONF_NURSERY_DELAYS_PRICES':
                     // Reinit
                     $CONF_NURSERY_DELAYS_PRICES = array();

                     // Load nursery delays prices for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument;
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $SchoolYears = $XmlParser->getElementsByTagName('school-year');
                             foreach($SchoolYears as $SchoolYear)
                             {
                                 $SY_ID = $SchoolYear->getAttribute('id');
                                 if ($SchoolYear->hasChildNodes())
                                 {
                                     foreach($SchoolYear->childNodes as $ChildNode)
                                     {
                                         if ($ChildNode->nodeType != XML_TEXT_NODE)
                                         {
                                             if ($ChildNode->attributes->length > 0)
                                             {
                                                 $CONF_NURSERY_DELAYS_PRICES[$SY_ID][getXmlNodeValue($ChildNode->attributes->item(0)->nodeValue)] = getXmlNodeValue($ChildNode->nodeValue);
                                             }
                                             else
                                             {
                                                 $CONF_NURSERY_DELAYS_PRICES[$SY_ID][] = getXmlNodeValue($ChildNode->nodeValue);
                                             }
                                         }
                                     }
                                 }
                                 else
                                 {
                                     // No value
                                     $CONF_NURSERY_DELAYS_PRICES[$SY_ID] = array();
                                 }
                             }

                             unset($XmlParser, $SchoolYears);
                             break;
                     }
                     break;

                 case 'CONF_DONATION_TAX_RECEIPT_PARAMETERS':
                     // Reinit
                     $CONF_DONATION_TAX_RECEIPT_PARAMETERS = array();

                     // Load donation tax receipt parameters for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument();
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);

                             $Years = $XmlParser->getElementsByTagName('year');
                             foreach($Years as $Year)
                             {
                                 $Y_ID = $Year->getAttribute('id');
                                 if ($Year->hasChildNodes())
                                 {
                                     foreach($Year->childNodes as $ChildNode)
                                     {
                                         if ($ChildNode->nodeType == XML_ELEMENT_NODE)
                                         {
                                             if (in_array(getXmlNodeValue($ChildNode->nodeName), array('Template', 'Language', 'Unit')))
                                             {
                                                 // Single parameters for the year
                                                 $iParam = eval("return ".getXmlNodeValue($ChildNode->nodeName).";");
                                                 $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][$iParam] = getXmlNodeValue($ChildNode->nodeValue);
                                             }
                                             elseif ($ChildNode->nodeName == 'pages')
                                             {
                                                 // Parameters for pages of the tax receipt of the year
                                                 if ($ChildNode->hasChildNodes())
                                                 {
                                                     foreach($ChildNode->childNodes as $PageNode)
                                                     {
                                                         if ($PageNode->nodeType == XML_ELEMENT_NODE)
                                                         {
                                                             // Id of the page
                                                             $PageID = $PageNode->getAttribute('id');
                                                             $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID] = array();

                                                             // We get parameters of the page
                                                             $PageParts = $PageNode->getElementsByTagName('part');
                                                             foreach($PageParts as $PartNode)
                                                             {
                                                                 // Parts are Recipient, Donator... constants
                                                                 $iPart = eval("return ".$PartNode->getAttribute('id').";");
                                                                 $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID][$iPart] = array();

                                                                 // We get parameters of each field
                                                                 $PartFields = $PartNode->getElementsByTagName('field');
                                                                 foreach($PartFields as $FieldNode)
                                                                 {
                                                                     $FieldID = getXmlNodeValue($FieldNode->getAttribute('id'));
                                                                     if ($FieldNode->hasChildNodes())
                                                                     {
                                                                         foreach($FieldNode->childNodes as $ParamFieldNode)
                                                                         {
                                                                             if ($ParamFieldNode->nodeType == XML_ELEMENT_NODE)
                                                                             {
                                                                                 // We check if the field is a list
                                                                                 if (getXmlNodeValue($ParamFieldNode->nodeName) == 'list')
                                                                                 {
                                                                                     // Get items of the list
                                                                                     $FieldItems = $ParamFieldNode->getElementsByTagName('item');
                                                                                     foreach($FieldItems as $ItemNode)
                                                                                     {
                                                                                         if ($ItemNode->nodeType == XML_ELEMENT_NODE)
                                                                                         {
                                                                                             $ItemID = getXmlNodeValue($ItemNode->getAttribute('id'));
                                                                                             $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID][$iPart][$FieldID][$ItemID] = array();

                                                                                             foreach($ItemNode->childNodes as $ParamItemNode)
                                                                                             {
                                                                                                 if ($ParamItemNode->nodeType == XML_ELEMENT_NODE)
                                                                                                 {
                                                                                                     // Parameters are Text, PosX, PosY... constants
                                                                                                     $iParamField = eval("return ".getXmlNodeValue($ParamItemNode->nodeName).";");

                                                                                                     if (getXmlNodeValue($ParamItemNode->nodeName) == 'Items')
                                                                                                     {
                                                                                                         // Several values for the parameter
                                                                                                         $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID][$iPart][$FieldID][$ItemID][$iParamField] = array();

                                                                                                         // Get values
                                                                                                         $ItemValues = $ParamItemNode->getElementsByTagName('value');
                                                                                                         foreach($ItemValues as $ItemValue)
                                                                                                         {
                                                                                                             $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID][$iPart][$FieldID][$ItemID][$iParamField][] = getXmlNodeValue($ItemValue->nodeValue);
                                                                                                         }
                                                                                                     }
                                                                                                     else
                                                                                                     {
                                                                                                         // Single parameter
                                                                                                         $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID][$iPart][$FieldID][$ItemID][$iParamField] = getXmlNodeValue($ParamItemNode->nodeValue);
                                                                                                     }
                                                                                                 }
                                                                                             }
                                                                                         }
                                                                                     }
                                                                                 }
                                                                                 else
                                                                                 {
                                                                                     // Parameters are Text, PosX, PosY... constants
                                                                                     $iParamField = eval("return ".getXmlNodeValue($ParamFieldNode->nodeName).";");
                                                                                     $CONF_DONATION_TAX_RECEIPT_PARAMETERS[$Y_ID][Page][$PageID][$iPart][$FieldID][$iParamField] = getXmlNodeValue($ParamFieldNode->nodeValue);
                                                                                 }
                                                                             }
                                                                         }
                                                                     }
                                                                 }
                                                             }
                                                         }
                                                     }
                                                 }
                                             }
                                         }
                                     }
                                 }
                             }

                             unset($XmlParser, $Years);
                             break;
                     }
                     break;

                 default:
                     // For a default treatment
                     $ParamName = strToUpper($ParamName);

                     // Reinit
                     $GLOBALS[$ParamName] = null;

                     // Load donation tax receipt parameters for each school year
                     switch(strToLower($RecordParamValue['ConfigParameterType']))
                     {
                         case CONF_PARAM_TYPE_XML:
                             // Add header for the charset
                             $RecordParamValue['ConfigParameterValue'] = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>"
                                                                         .$RecordParamValue['ConfigParameterValue'];

                             // Load XML
                             $XmlParser = new DomDocument();
                             $XmlParser->loadXML($RecordParamValue['ConfigParameterValue']);
                             $GLOBALS[$ParamName] = getXmlToArray($XmlParser->firstChild);
                             break;
                     }
                     break;
             }
         }
     }
 }
?>