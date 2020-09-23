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
 * Interface module : XHTML Graphic primitives forms library used to create forms (input, select, textarea tags)
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-02-03
 */


/**
 * Process a XSLT transformation with a XML stream and a XSL stylesheet.
 * For Sablotron, Libexslt XSLT and PHP5 XSL processor only.
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2006-03-10 : define undeclared variables
 *     - 2007-01-25 : taken into account Libexslt XSLT and PHP5 XSL processors
 *
 * @since 2004-06-26
 *
 * @param $XmlData              String               XML stream (data or path filename)
 * @param $XslData              String               XSL stream (data or path stylesheet)
 * @param $TransformType        Const                Type of transformation
 * @param $DestFilename         String               Path filename to store the result
 * @param $Params               Array of Strings     XSL parameters
 *
 * @return Boolean              TRUE if the transformation worked, FALSE otherwise
 */
 function xmlXslProcess($XmlData, $XslData, $TransformType, $DestFilename = '', $Params = array())
 {
     $bResult = FALSE;

     if (($XmlData != '') && ($XslData != ''))
     {
         $TransformResult = '';

         // We get the loaded extensions
         $ArrayLoadedExtensions = get_loaded_extensions();

         // We detect the XSLT processor
         if (in_array('xslt', $ArrayLoadedExtensions))
         {
             // Sablotron processor detected
             $TransformResult = xmlXslSablotronGenerate($XmlData, $XslData, $TransformType, $Params);
         }
         elseif (in_array('xsl', $ArrayLoadedExtensions))
         {
             // PHP 5 XSL extension detected
             $TransformResult = xmlXslPHP5Generate($XmlData, $XslData, $TransformType, $Params);
         }
         elseif (in_array('domxml', $ArrayLoadedExtensions))
         {
             // Libexslt processor detected
             $TransformResult = xmlXslLibexsltGenerate($XmlData, $XslData, $TransformType, $Params);
         }

         // We check if there is a result
         if ($TransformResult != '')
         {
             // Yes, there is a result
             if ($DestFilename == '')
             {
                 // The result must be displayed
                 echo $TransformResult;
                 $bResult = TRUE;
             }
             else
             {
                 // The result must be stored in a file
                 $bResult = saveToFile($DestFilename, 'wt', array($TransformResult));
             }
         }
     }

     return $bResult;
 }


/**
 * Process a XSLT transformation with a XML stream and a XSL stylesheet
 * and return the generated string.
 * For Sablotron, Libexslt XSLT and PHP5 XSL processor only.
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2006-03-10 : define undeclared variables
 *     - 2007-01-25 : taken into account Libexslt XSLT and PHP5 XSL processors
 *
 * @since 2005-02-08
 *
 * @param $XmlData              String               XML stream (data or path filename)
 * @param $XslData              String               XSL stream (data or path stylesheet)
 * @param $TransformType        Const                Type of transformation
 * @param $Params               Array of Strings     XSL parameters
 *
 * @return String               The generated string if the transformation worked,
 *                              an empty otherwise
 */
 function xmlXslGenerate($XmlData, $XslData, $TransformType, $Params = array())
 {
     // We get the loaded extensions
     $ArrayLoadedExtensions = get_loaded_extensions();

     // We detect the XSLT processor
     if (in_array('xslt', $ArrayLoadedExtensions))
     {
         // Sablotron processor detected
         return xmlXslSablotronGenerate($XmlData, $XslData, $TransformType, $Params);
     }
     elseif (in_array('xsl', $ArrayLoadedExtensions))
     {
         // PHP 5 XSL extension detected
         return xmlXslPHP5Generate($XmlData, $XslData, $TransformType, $Params);
     }
     elseif (in_array('domxml', $ArrayLoadedExtensions))
     {
         // Libexslt processor detected
         return xmlXslLibexsltGenerate($XmlData, $XslData, $TransformType, $Params);
     }
     else
     {
         // Error : no xslt processor detected
         return '';
     }
 }


/**
 * Process a XSLT transformation with a XML stream and a XSL stylesheet
 * and return the generated string.
 * For Sablotron XSLT processor only.
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2016-06-20 : taken intao account $CONF_CHARSET
 *
 * @since 2007-01-25
 *
 * @param $XmlData              String               XML stream (data or path filename)
 * @param $XslData              String               XSL stream (data or path stylesheet)
 * @param $TransformType        Const                Type of transformation
 * @param $Params               Array of Strings     XSL parameters
 *
 * @return String               The generated string if the transformation worked,
 *                              an empty otherwise
 */
 function xmlXslSablotronGenerate($XmlData, $XslData, $TransformType, $Params = array())
 {
     if (($XmlData != '') && ($XslData != ''))
     {
         switch($TransformType)
         {
             case XMLFILE_XSLFILE:
                     $XmlParser = xslt_create();

                     // Check if the function xslt_set_encoding() exists
                     if (in_array('xslt_set_encoding', get_extension_funcs('xslt')))
                     {
                         // Yes
                         @xslt_set_encoding($XmlParser, $GLOBALS['CONF_CHARSET']);
                     }

                     $TransformResult = xslt_process($XmlParser, $XmlData, $XslData);
                     xslt_free($XmlParser);

                     // Return the generated string
                     return $TransformResult;
                     break;

             case XMLFILE_XSLSTREAM:
                     $Arguments = array("/_xsl" => $XslData);
                     $XmlParser = xslt_create();

                     // Check if the function xslt_set_encoding() exists
                     if (in_array('xslt_set_encoding', get_extension_funcs('xslt')))
                     {
                         // Yes
                         @xslt_set_encoding($XmlParser, $GLOBALS['CONF_CHARSET']);
                     }

                     // We display the result
                     $TransformResult = xslt_process($XmlParser, $XmlData, "arg:/_xsl", NULL, $Arguments);
                     xslt_free($XmlParser);

                     // Return the generated string
                     return $TransformResult;
                     break;

             case XMLSTREAM_XSLSTREAM:
                     $Arguments = array("/_xml" => $XmlData, "/_xsl" => $XslData);
                     $XmlParser = xslt_create();

                     // Check if the function xslt_set_encoding() exists
                     if (in_array('xslt_set_encoding', get_extension_funcs('xslt')))
                     {
                         // Yes
                         @xslt_set_encoding($XmlParser, $GLOBALS['CONF_CHARSET']);
                     }

                     // We display the result
                     $TransformResult = xslt_process($XmlParser, "arg:/_xml", "arg:/_xsl", NULL, $Arguments);
                     xslt_free($XmlParser);

                     // Return the generated string
                     return $TransformResult;
                     break;

             default:
             case XMLSTREAM_XSLFILE:
                     $Arguments = array("/_xml" => $XmlData);
                     $XmlParser = xslt_create();

                     // Check if the function xslt_set_encoding() exists
                     if (in_array('xslt_set_encoding', get_extension_funcs('xslt')))
                     {
                         // Yes
                         @xslt_set_encoding($XmlParser, $GLOBALS['CONF_CHARSET']);
                     }

                     // We display the result
                     $TransformResult = xslt_process($XmlParser, "arg:/_xml", $XslData, NULL, $Arguments);
                     xslt_free($XmlParser);

                     // Return the generated string
                     return $TransformResult;
                     break;
         }
     }

     // ERROR
     return '';
 }


/**
 * Process a XSLT transformation with a XML stream and a XSL stylesheet.
 * For Libexslt processor only.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-25
 *
 * @param $XmlData              String               XML stream (data or path filename)
 * @param $XslData              String               XSL stream (data or path stylesheet)
 * @param $TransformType        Const                Type of transformation
 * @param $Params               Array of Strings     XSL parameters
 *
 * @return String               The generated string if the transformation worked,
 *                              an empty otherwise
 */
 function xmlXslLibexsltGenerate($XmlData, $XslData, $TransformType, $Params = array())
 {
     if (($XmlData != '') && ($XslData != ''))
     {
         switch($TransformType)
         {
             case XMLFILE_XSLFILE:
                 break;

             case XMLFILE_XSLSTREAM:
                 break;

             case XMLSTREAM_XSLSTREAM:
                 // Parse the XML stream
                 $XmlParser = domxml_open_mem($XmlData);

                 // Create the XSL parser
                 $XslParser = domxml_xslt_stylesheet($XslData);

                 // We display the result
                 $TransformResult = $XslParser->process($XmlParser, $Params);

                 // Return the generated string
                 return $XslParser->result_dump_mem($TransformResult);
                 break;

             default:
             case XMLSTREAM_XSLFILE:
                 break;
         }
     }

     // ERROR
     return '';
 }


/**
 * Process a XSLT transformation with a XML stream and a XSL stylesheet.
 * For PHP 5 XSL processor only.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-25
 *
 * @param $XmlData              String               XML stream (data or path filename)
 * @param $XslData              String               XSL stream (data or path stylesheet)
 * @param $TransformType        Const                Type of transformation
 * @param $Params               Array of Strings     XSL parameters
 *
 * @return String               The generated string if the transformation worked,
 *                              an empty otherwise
 */
 function xmlXslPHP5Generate($XmlData, $XslData, $TransformType, $Params = array())
 {
     if (($XmlData != '') && ($XslData != ''))
     {
         switch($TransformType)
         {
             case XMLFILE_XSLFILE:
                 // Create a DOM document and load the XML file
                 $XmlParser = new DomDocument;
                 $XmlParser->load($XmlData);

                 // Create a DOM document and load the XSL stylesheet file
                 $XslParser = new DomDocument;
                 $XslParser->load($XslData);

                 // Create the XSL processor
                 $xp = new XsltProcessor();

                 // Import the XSL styelsheet into the XSLT process
                 $xp->importStylesheet($XslParser);

                 // We take into account the parameters
                 foreach($Params as $key => $CurrentValue)
                 {
                     $xp->setParameter('', $key, $CurrentValue);
                 }

                 // We display the result
                 $TransformResult = $xp->transformToXML($XmlParser);

                 // Free resources
                 unset($XmlParser);
                 unset($XslParser);
                 unset($xp);

                 // Return the generated string
                 return $TransformResult;
                 break;

             case XMLFILE_XSLSTREAM:
                 // Create a DOM document and load the XML file
                 $XmlParser = new DomDocument;
                 $XmlParser->load($XmlData);

                 // Create a DOM document and load the XSL stylesheet stream
                 $XslParser = new DomDocument;
                 $XslParser->loadXML($XslData);

                 // Create the XSL processor
                 $xp = new XsltProcessor();

                 // Import the XSL styelsheet into the XSLT process
                 $xp->importStylesheet($XslParser);

                 // We take into account the parameters
                 foreach($Params as $key => $CurrentValue)
                 {
                     $xp->setParameter('', $key, $CurrentValue);
                 }

                 // We display the result
                 $TransformResult = $xp->transformToXML($XmlParser);

                 // Free resources
                 unset($XmlParser);
                 unset($XslParser);
                 unset($xp);

                 // Return the generated string
                 return $TransformResult;
                 break;

             case XMLSTREAM_XSLSTREAM:
                 // Create a DOM document and load the XML stream
                 $XmlParser = new DomDocument;
                 $XmlParser->loadXML($XmlData);

                 // Create a DOM document and load the XSL stylesheet stream
                 $XslParser = new DomDocument;
                 $XslParser->loadXML($XslData);

                 // Create the XSL processor
                 $xp = new XsltProcessor();

                 // Import the XSL styelsheet into the XSLT process
                 $xp->importStylesheet($XslParser);

                 // We take into account the parameters
                 foreach($Params as $key => $CurrentValue)
                 {
                     $xp->setParameter('', $key, $CurrentValue);
                 }

                 // We display the result
                 $TransformResult = $xp->transformToXML($XmlParser);

                 // Free resources
                 unset($XmlParser);
                 unset($XslParser);
                 unset($xp);

                 // Return the generated string
                 return $TransformResult;
                 break;

             default:
             case XMLSTREAM_XSLFILE:
                 // Create a DOM document and load the XML stylesheet stream
                 $XmlParser = new DomDocument;
                 $XmlParser->loadXML($XmlData);

                 // Create a DOM document and load the XSL file
                 $XslParser = new DomDocument;
                 $XslParser->load($XslData);

                 // Create the XSL processor
                 $xp = new XsltProcessor();

                 // Import the XSL styelsheet into the XSLT process
                 $xp->importStylesheet($XslParser);

                 // We take into account the parameters
                 foreach($Params as $key => $CurrentValue)
                 {
                     $xp->setParameter('', $key, $CurrentValue);
                 }

                 // We display the result
                 $TransformResult = $xp->transformToXML($XmlParser);

                 // Free resources
                 unset($XmlParser);
                 unset($XslParser);
                 unset($xp);

                 // Return the generated string
                 return $TransformResult;
                 break;
         }
     }

     // ERROR
     return '';
 }


/**
 * Initialize a XML document
 *
 * @author STNA/7SQ
 * @version 2.1
 *     - 2004-07-26 : taken into account the content of a stylesheet
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *
 * @since 2004-06-26
 *
 * @return String                                 A <document> tag
 */
 function xmlOpenDocument($StyleSheetContent = "")
 {
     $tmp = "<?xml version=\"1.0\" encoding=\"".$GLOBALS['CONF_CHARSET']."\" ?>\n<document>\n";

     if ($StyleSheetContent != "")
     {
         $tmp .= "<stylesheet>$StyleSheetContent</stylesheet>\n";
     }

     return $tmp;
 }


/**
 * Define the given parameter as a title of a XML document
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-26
 *
 * @param $Title             String               Title to convert in XML format
 *
 * @return String                                 A title in XML format
 */
 function xmlTitle($Title)
 {
     if ($Title != "")
     {
         return "<title>$Title</title>\n";
     }

     // ERROR
     return "";
 }


/**
 * Define the given parameter as a simple text of a XML document
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-26
 *
 * @param $Text             String                Text to convert in XML format
 *
 * @return String                                 A text in XML format
 */
 function xmlText($Text)
 {
     if ($Text != "")
     {
         return "<text>$Text</text>\n";
     }

     // ERROR
     return "";
 }


/**
 * Define the given parameter as a hyperlink of a XML document
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-26
 *
 * @param $Text             String                Text of the hyperlink
 * @param $Link             String                Url of the hyperlink
 *
 * @return String                                 An hyperlink in XML format
 */
 function xmlHyperlink($Text, $Link)
 {
     if (($Text != "") && ($Link != ""))
     {
         return "<url>\n\t<link>$Link</link>\n\t<text>$Text</text>\n</url>\n";
     }

     // ERROR
     return "";
 }


/**
 * Define the given parameter as a picture of a XML document
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-26
 *
 * @param $Pic             String                Url of the picture
 * @param $Caption         String                Caption of the picture
 *
 * @return String                                 A picture in XML format
 */
 function xmlPicture($Pic, $Caption)
 {
     if (($Pic != "") && ($Caption != ""))
     {
         return "<pic>\n\t<src>$Pic</src>\n\t<caption>$Caption</caption>\n</pic>\n";
     }

     // ERROR
     return "";
 }


/**
 * Put a value and parameters in a XML tag, in a XML document
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-23
 *
 * @param $TagName         String                Tag use in the opened and ended tags of the table
 * @param $Value           String                Value to put in XML format
 * @param $Params          Array of Strings      The keys of the array are the xml tags and the values
 *                                               linked to the keys, the values of the parameters
 *
 * @return String                                A value and the parameters in XML format
 */
 function xmlTag($TagName, $Value, $Params = array())
 {
     if ($TagName != "")
     {
         // Initialize the xml tag
         $tmp = "<".strToLower($TagName);

         // Put the parameters
         foreach($Params as $i => $CurrentValue)
         {
             $tmp .= " ".strToLower($i)."=\"$CurrentValue\"";
         }

         return "$tmp>$Value</".strToLower($TagName).">\n";
     }

     // ERROR
     return "";
 }


/**
 * Put the content of a table in XML format
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-26
 *
 * @param $TableTag              String                   Tag use in the opened and ended tags of the table
 * @param $TabCaptions           Array of Strings         Captions of the table
 * @param $TabFieldnames         Array of Strings         Fieldnames of the data used as XML tags
 * @param $TabData               Array of Strings         Data to put in XML format
 *
 * @return String                                         A table in XML format
 */
 function xmlTable($TableTag, $TabCaptions, $TabFieldnames, $TabData)
 {
     if ((count($TabCaptions) > 0) && (count($TabCaptions) == count($TabFieldnames)))
     {
         // First element of the array TabFieldnames
         $tmp = "<".strToLower($TableTag).">\n";

         // Captions converted to XML
         $tmp .= "\t<captions>\n";
         foreach($TabCaptions as $CurrentValue)
         {
             $tmp .= "\t\t<item>$CurrentValue</item>\n";
         }
         $tmp .= "\t</captions>\n";

         // Data converted to XML
         $ArrayKeys = array_keys($TabData); // To manage all arrays types
         for($i = 0 ; $i < count($TabData[$ArrayKeys[0]]) ; $i++)
         {
             // New record
             $tmp .= "\t<record>\n";

             // Get elements of a record
             for($j = 0 ; $j < count($TabData) ; $j++)
             {
                 // Process the current value
                 $CurrentValue = $TabData[$ArrayKeys[$j]][$i];
                 if (is_Null($CurrentValue))
                 {
                     $CurrentValue = "NULL";
                 }
                 $CurrentValue = invFormatText($CurrentValue, "XML");

                 $tmp .= "\t\t<".strToLower($TabFieldnames[$ArrayKeys[$j]]).">$CurrentValue</".strToLower($TabFieldnames[$ArrayKeys[$j]]).">\n";
             }

             // Close record
             $tmp .= "\t</record>\n";
         }

         return $tmp."</".strToLower($TableTag).">\n";
     }

     // ERROR
     return "";
 }


/**
 * Convert in XML a bill
 *
 * @author Christophe Javouhey
 * @version 1.6
 *     - 2012-12-21 : patch a bug about the change of price in a current school year
 *     - 2014-02-03 : taken into account the BillNurseryNbDelays field
 *     - 2014-06-02 : replace an "integer" cast by a round() function and use the abs() function
 *     - 2015-01-16 : try to find the right price of canteen if not found in $CONF_CANTEEN_PRICES
 *                    Do the same treatment to find the right price of nursery
 *     - 2017-11-07 : taken into account BillWithoutMealAmount and the right price
 *     - 2019-07-16 : patch a div by 0 about $fWithoutMealPrice and round() sub-toal and total
 *                    of the bill
 *
 * @since 2012-02-23
 *
 * @param $DbConnection         DB object       Object of the opened database connection
 * @param $BillID               Integer         ID of the bill to convert to XML [1..n]
 *
 * @return String               The bill in XML format, empty string otherwise
 */
 function xmlBill($DbConnection, $BillID)
 {
     if (($BillID > 0) && (isExistingBill($DbConnection, $BillID)))
     {
         $Xml = '';

         // Get infos about the bill
         $RecordBill = getTableRecordInfos($DbConnection, "Bills", $BillID);
         $bSchoolYearPriceChanged = FALSE;
         if (!empty($RecordBill))
         {
             $Xml = "<bill>\n";
             $Xml .= "\t<detailsbill>\n";
             foreach($RecordBill as $Key => $Value)
             {
                 $Key = strtolower($Key);
                 switch($Key)
                 {
                     case 'billdate':
                     case 'billfordate':
                         if (empty($Value))
                         {
                             $Value = '';
                         }
                         else
                         {
                             $Value = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($Value));
                         }

                         $Xml .= "\t\t<$Key>$Value</$Key>\n";
                         break;

                     case 'familyid':
                         $RecordFamily = getTableRecordInfos($DbConnection, "Families", $Value);
                         if (!empty($RecordFamily))
                         {
                             $Xml .= "\t\t<$Key>$Value</$Key>\n";
                             $Xml .= "\t\t<familylastname>".invFormatText($RecordFamily['FamilyLastname'], 'XML')."</familylastname>\n";
                         }
                         else
                         {
                             $Xml .= "\t\t<$Key></$Key>\n";
                         }

                         unset($RecordFamily);
                         break;

                     default:
                         $Xml .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                         break;
                 }
             }

             // We add some other data
             $SchoolYear = getSchoolYear($RecordBill['BillForDate']);
             $SchoolYearPrice = $SchoolYear;

             // We get the max price of the selected school year, for nursery delay
             if (isset($GLOBALS['CONF_NURSERY_DELAYS_PRICES'][$SchoolYearPrice]))
             {
                 $ArrayPricesKeys = array_keys($GLOBALS['CONF_NURSERY_DELAYS_PRICES'][$SchoolYearPrice]);
                 $_NURSERY_DELAY_MAX_PRICE_ = 0.00;
                 if (!empty($ArrayPricesKeys))
                 {
                     $_NURSERY_DELAY_MAX_PRICE_ = $GLOBALS['CONF_NURSERY_DELAYS_PRICES'][$SchoolYearPrice][$ArrayPricesKeys[count($ArrayPricesKeys) - 1]];
                 }

                 unset($ArrayPricesKeys);
             }

             // Get the number of canteen registrations
             $iNbCanteenRegistrations = 0;
             $iNbWithoutMeals = 0;
             if (isset($GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice]))
             {
                 $fPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                 $MinPrice = $fPrice;
                 $MaxPrice = $fPrice;

                 $fWithoutMealPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                 $MinWithoutMealPrice = $fWithoutMealPrice;
                 $MaxWithoutMealPrice = $fWithoutMealPrice;

                 $iNbCanteenRegistrations = $RecordBill['BillCanteenAmount'] / $fPrice;

                 if ($fWithoutMealPrice > 0)
                 {
                     $iNbWithoutMeals = $RecordBill['BillWithoutMealAmount'] / $fWithoutMealPrice;
                 }

                 // We do this verification only once time
                 // We use the round(x, 3) because with float, some results have the form x.yE-15
                 if ((!$bSchoolYearPriceChanged) && (round(abs($iNbCanteenRegistrations - round($iNbCanteenRegistrations)), 3) > 0))
                 {
                     // We must use the canteen price of the previous school year
                     $SchoolYearPrice--;
                     if (isset($GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice]))
                     {
                         // We re-compute data for the bill
                         $fPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                         $MinPrice = $fPrice;
                         $iNbCanteenRegistrations = $RecordBill['BillCanteenAmount'] / $fPrice;

                         $fWithoutMealPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];

                         if ($fWithoutMealPrice > 0)
                         {
                             $iNbWithoutMeals = $RecordBill['BillWithoutMealAmount'] / $fWithoutMealPrice;
                         }
                     }

                     $bSchoolYearPriceChanged = TRUE;

                     if (round(abs($iNbCanteenRegistrations - round($iNbCanteenRegistrations)), 3) > 0)
                     {
                         // Try to find the right price
                         $fFoundPrice = findExactDivisor($RecordBill['BillCanteenAmount'], $MinPrice, $MaxPrice, 0.01);

                         if ($fFoundPrice > 0)
                         {
                             // Right price found
                             $bSchoolYearPriceChanged = FALSE;
                             $SchoolYearPrice++;
                             $fPrice = $fFoundPrice;

                             $iNbCanteenRegistrations = $RecordBill['BillCanteenAmount'] / $fPrice;
                         }

                         $fWithoutMealFoundPrice = findExactDivisor($RecordBill['BillWithoutMealAmount'], $MinWithoutMealPrice,
                                                                    $MaxWithoutMealPrice, 0.01);
                         if ($fWithoutMealFoundPrice > 0)
                         {
                             // Right price found
                             $fWithoutMealPrice = $fWithoutMealFoundPrice;
                             $iNbWithoutMeals = $RecordBill['BillWithoutMealAmount'] / $fWithoutMealPrice;
                         }
                     }
                 }
             }

             $Xml .= "\t\t<nbcanteenregistrations>$iNbCanteenRegistrations</nbcanteenregistrations>\n";
             $Xml .= "\t\t<nbwithoutmeals>$iNbWithoutMeals</nbwithoutmeals>\n";

             // Get the amount about nursery delays
             $fNurseryDelaysAmount = 0.00;
             if ((isset($GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice])) && ($RecordBill['BillNurseryNbDelays'] > 0))
             {
                 for($nd = 1; $nd <= $RecordBill['BillNurseryNbDelays']; $nd++)
                 {
                     if (isset($GLOBALS['CONF_NURSERY_DELAYS_PRICES'][$SchoolYearPrice][$nd]))
                     {
                         $fNurseryDelaysAmount += $GLOBALS['CONF_NURSERY_DELAYS_PRICES'][$SchoolYearPrice][$nd];
                     }
                     else
                     {
                         $fNurseryDelaysAmount += $_NURSERY_DELAY_MAX_PRICE_;
                     }
                 }
             }

             $Xml .= "\t\t<nbnurserydelays>".$RecordBill['BillNurseryNbDelays']."</nbnurserydelays>\n";

             // Get the number of nursery registrations
             $iNbNurseryRegistrations = 0;
             if (isset($GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice]))
             {
                 $fPrice = $GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice][1];
                 $iNbNurseryRegistrations = 2 * (($RecordBill['BillNurseryAmount'] - $fNurseryDelaysAmount) / $fPrice);

                 if (round(abs($iNbNurseryRegistrations - round($iNbNurseryRegistrations)), 3) > 0)
                 {
                     // Try to find the right price with the previous school year
                     $MaxPrice = $fPrice;

                     if (isset($GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice - 1]))
                     {
                         $fPrice = $GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice - 1][0] + $GLOBALS['CONF_NURSERY_PRICES'][$SchoolYearPrice - 1][1];
                         $MinPrice = $fPrice;

                         $iNbNurseryRegistrations = 2 * (($RecordBill['BillNurseryAmount'] - $fNurseryDelaysAmount) / $fPrice);
                     }

                     if (round(abs($iNbNurseryRegistrations - round($iNbNurseryRegistrations)), 3) > 0)
                     {
                         $fFoundPrice = findExactDivisor($RecordBill['BillNurseryAmount'] - $fNurseryDelaysAmount, $MinPrice, $MaxPrice, 0.01);
                         if ($fFoundPrice > 0)
                         {
                             $fPrice = $fFoundPrice;

                             $iNbNurseryRegistrations = 2 * (($RecordBill['BillNurseryAmount'] - $fNurseryDelaysAmount) / $fPrice);
                         }
                     }
                 }
             }
             $Xml .= "\t\t<nbnurseryregistrations>$iNbNurseryRegistrations</nbnurseryregistrations>\n";

             // Month and year
             $Month = date('n', strtotime($RecordBill['BillForDate']));
             $Month = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1];
             $Year = date('y', strtotime($RecordBill['BillForDate']));
             $Xml .= "\t\t<billmonthyear>$Month-$Year</billmonthyear>\n";

             // Total of the bill for the month
             $SubTotal = round($RecordBill['BillMonthlyContribution'] + $RecordBill['BillCanteenAmount'] + $RecordBill['BillWithoutMealAmount']
                         + $RecordBill['BillNurseryAmount'], 3);
             $Xml .= "\t\t<billsubtotalamount>$SubTotal</billsubtotalamount>\n";

             // Total of the bill
             $Total = $RecordBill['BillPreviousBalance'] + $RecordBill['BillMonthlyContribution'] + $RecordBill['BillCanteenAmount']
                      + $RecordBill['BillWithoutMealAmount'] + $RecordBill['BillNurseryAmount'] - $RecordBill['BillDeposit'];

             if (abs($Total) < 0.00001)
             {
                 $Total = 0.00;
             }
             else
             {
                 $Total = round($Total, 3);
             }

             $Xml .= "\t\t<billtotalamount>$Total</billtotalamount>\n";

             $Xml .= "\t</detailsbill>\n";
             $Xml .= "</bill>";
         }

         return $Xml;
     }

     // Error
     return '';
 }


/**
 * Convert in XML a bill
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-08
 *
 * @param $DbConnection         DB object       Object of the opened database connection
 * @param $DonationID           Integer         ID of the donation to convert to XML [1..n]
 *
 * @return String               The donatoin in XML format, empty string otherwise
 */
 function xmlDonation($DbConnection, $DonationID)
 {
     if (($DonationID > 0) && (isExistingDonation($DbConnection, $DonationID)))
     {
         $Xml = '';

         // Get infos about the donation
         $RecordDonation = getTableRecordInfos($DbConnection, "Donations", $DonationID);
         if (!empty($RecordDonation))
         {
             $Xml = "<donation>\n";
             $Xml .= "\t<detailsdonation>\n";
             foreach($RecordDonation as $Key => $Value)
             {
                 $Key = strtolower($Key);
                 switch($Key)
                 {
                     case 'donationreceptiondate':
                         $StampValue = strtotime($Value);
                         $Value = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], $StampValue);

                         // Day, month and year
                         $Day = date('d', $StampValue);
                         $Month = date('m', $StampValue);
                         $MonthName = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1];
                         $Year = date('Y', $StampValue);

                         $Xml .= "\t\t<$Key day=\"$Day\" month=\"$Month\" monthname=\"$MonthName\" year=\"$Year\">$Value</$Key>\n";
                         break;

                     case 'donationtype':
                         $Xml .= "\t\t<$Key>$Value</$Key>\n";
                         $Xml .= "\t\t<donationtypename>".invFormatText($GLOBALS['CONF_DONATION_TYPES'][$Value], 'XML')
                                 ."</donationtypename>\n";
                         break;

                     case 'donationentity':
                         $Xml .= "\t\t<$Key>$Value</$Key>\n";
                         $Xml .= "\t\t<donationentityname>".invFormatText($GLOBALS['CONF_DONATION_ENTITIES'][$Value], 'XML')
                                 ."</donationentityname>\n";
                         break;

                     case 'donationnature':
                         $Xml .= "\t\t<$Key>$Value</$Key>\n";
                         $Xml .= "\t\t<donationnaturename>".invFormatText($GLOBALS['CONF_DONATION_NATURES'][$Value], 'XML')
                                 ."</donationnaturename>\n";
                         break;

                     case 'familyid':
                         $RecordFamily = getTableRecordInfos($DbConnection, "Families", $Value);
                         if (!empty($RecordFamily))
                         {
                             $Xml .= "\t\t<$Key>$Value</$Key>\n";
                             $Xml .= "\t\t<familylastname>".invFormatText($RecordFamily['FamilyLastname'], 'XML')."</familylastname>\n";
                         }
                         else
                         {
                             $Xml .= "\t\t<$Key></$Key>\n";
                         }

                         unset($RecordFamily);
                         break;

                     case 'donationfamilyrelationship':
                         $Xml .= "\t\t<$Key>$Value</$Key>\n";
                         $Xml .= "\t\t<donationfamilyrelationshipname>"
                                 .invFormatText($GLOBALS['CONF_DONATION_FAMILY_RELATIONSHIP'][$Value], 'XML')
                                 ."</donationfamilyrelationshipname>\n";
                         break;

                     case 'townid':
                         $RecordTown = getTableRecordInfos($DbConnection, "Towns", $Value);
                         if (!empty($RecordTown))
                         {
                             $Xml .= "\t\t<$Key>$Value</$Key>\n";
                             $Xml .= "\t\t<townname>".invFormatText($RecordTown['TownName'], 'XML')."</townname>\n";
                             $Xml .= "\t\t<towncode>".invFormatText($RecordTown['TownCode'], 'XML')."</towncode>\n";
                         }
                         else
                         {
                             $Xml .= "\t\t<$Key></$Key>\n";
                         }

                         unset($RecordTown);
                         break;

                     case 'bankid':
                         $RecordBank = getTableRecordInfos($DbConnection, "Banks", $Value);
                         if (!empty($RecordBank))
                         {
                             $Xml .= "\t\t<$Key>$Value</$Key>\n";
                             $Xml .= "\t\t<bankname>".invFormatText($RecordBank['BankName'], 'XML')."</bankname>\n";
                         }
                         else
                         {
                             $Xml .= "\t\t<$Key></$Key>\n";
                         }

                         unset($RecordBank);
                         break;

                     case 'donationpaymentmode':
                         $Xml .= "\t\t<$Key>$Value</$Key>\n";
                         $Xml .= "\t\t<donationpaymentmodename>"
                                 .invFormatText($GLOBALS['CONF_DONATION_FAMILY_RELATIONSHIP'][$Value], 'XML')
                                 ."</donationpaymentmodename>\n";
                         break;

                     default:
                         $Xml .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                         break;
                 }
             }

             $Xml .= "\t</detailsdonation>\n";
             $Xml .= "</donation>";
         }

         return $Xml;
     }

     // Error
     return '';
 }


/**
 * Finalize a XML document
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-26
 *
 * @return String        A </document> tag
 */
 function xmlCloseDocument()
 {
     return "</document>\n";
 }


/**
 * Generate a RSS stream
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-02-17
 *
 * @param $TabChannelFields     Array of Strings        Fieldnames with value of the RSS
 *                                                      channel table
 * @param $TabItems             Array of Strings        Fieldnames with value of the Items of
 *                                                      the RSS channel
 * @param $Generator            String                  Name of the generator
 * @param $Link                 String                  Url
 *
 * @return String               A RSS stream
 */
 function xmlRSSGenerate($TabChannelFields, $TabItems, $Generator = '', $Link = '', $CSS = '')
 {
     // Check infos about the RSS channel
     if (!empty($TabChannelFields))
     {
         $tmp = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
         if (!empty($CSS))
         {
             $tmp .= "<?xml-stylesheet type=\"text/css\" href=\"$CSS\"?>\n";
         }

         $tmp .= "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n";
         $tmp .= "\t<channel>\n";
         $tmp .= "\t\t<title>".utf8_encode($TabChannelFields['RSSChannelTitle'])."</title>\n";
         $tmp .= "\t\t<link>".utf8_encode(str_replace(array('&'), array('&amp;'), $Link))."</link>\n";
         $tmp .= "\t\t<description>".utf8_encode($TabChannelFields['RSSChannelDescription'])."</description>\n";
         $tmp .= "\t\t<language>".utf8_encode($GLOBALS['CONF_LANG'])."</language>\n";
         $tmp .= "\t\t<generator>".utf8_encode($Generator)."</generator>\n";
         $tmp .= "\t\t<image>\n";
         $tmp .= "\t\t\t<title>".utf8_encode($Generator)."</title>\n";
         $tmp .= "\t\t\t<url>".utf8_encode($GLOBALS['CONF_ROOT_DIRECTORY']."GUI/Styles/LogoAstresRSS.jpg")."</url>\n";
         $tmp .= "\t\t\t<link>".utf8_encode($GLOBALS['CONF_ROOT_DIRECTORY'])."</link>\n";
         $tmp .= "\t\t</image>\n";
         $tmp .= "\t\t<lastBuildDate>".date('r')."</lastBuildDate>\n";

         if (!empty($TabItems))
         {
             $ArrayTags = array_keys($TabItems);
             $iNbItems = count($TabItems[$ArrayTags[0]]);
             for($i = 0; $i < $iNbItems; $i++)
             {
                 $tmp .= "\t\t<item>\n";
                 $tmp .= "\t\t\t<dc:format>text/html</dc:format>\n";
                 $tmp .= "\t\t\t<dc:language>".utf8_encode($GLOBALS['CONF_LANG'])."</dc:language>\n";
                 foreach($ArrayTags as $t => $Tag)
                 {
                     $CurrentValue = $TabItems[$ArrayTags[$t]][$i];
                     if (empty($CurrentValue))
                     {
                         $CurrentValue = '';
                     }
                     else
                     {
                         switch($ArrayTags[$t])
                         {
                             case 'title':
                             case 'description':
                                 $CurrentValue = formatText($CurrentValue, 'RSS');
                                 break;

                             default:
                                 $CurrentValue = str_replace(array("&"), array("&amp;"), $CurrentValue);
                                 break;
                         }
                     }

                     $tmp .= "\t\t\t<".$ArrayTags[$t].">".utf8_encode($CurrentValue)."</".$ArrayTags[$t].">\n";
                 }

                 $tmp .= "\t\t</item>\n";
             }
         }

         $tmp .= "\t</channel>\n";
         $tmp .= "</rss>\n";

         return $tmp;
     }

     return '';
 }
?>