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
 * Interface module : XHTML Graphic primitives library : allow to seperate processes and graphic interface.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-01-12
 */

 // Include the config file of the Intranet tool and the library of functions
 require dirname(__FILE__).'/../Common/DefinedConst.php';
 require_once dirname(__FILE__).'/../Common/Config.php';
 require dirname(__FILE__).'/../Common/FctLibrary.php';
 require dirname(__FILE__).'/../Support/ContextualMenusAccess.php';

 // Include the right language file
 require dirname(__FILE__).'/../Languages/SetLanguage.php';

 // Include graphic primitives library
 require 'GiMenusLibrary.php';                                // Graphic primitives library used to create menus
 require 'GiHyperlinksLibrary.php';                           // Graphic primitives library used to create hyperlinks
 require 'GiFormsLibrary.php';                                // Graphic primitives library used to create forms
 require 'GiDisplayComponentsLibrary.php';                    // Graphic components library used to display high levels informations
 require 'GiHighLevelsLoginFormsLibrary.php';                 // Graphic high level login/logout forms library
 require 'GiHighLevelsProfilsFormsLibrary.php';               // Graphic high level forms library used to display the profil of a customer or a supporter
 require 'GiXMLLibrary.php';                                  // Primitives library used to export data in XML format
 require 'GiPrintComponentsLibrary.php';                      // Primitives library used to print some web pages
 require 'GiExportComponentsLibrary.php';                     // Primitives library used to export data of some web pages
 require 'GiHighLevelsFormsFamiliesLibrary.php';              // Primitives library used to display forms to manage families and children
 require 'GiHighLevelsFormsBillsPaymentsLibrary.php';         // Primitives library used to display forms to manage bills and payments
 require 'GiHighLevelsFormsCanteenRegistratrionsLibrary.php'; // Primitives library used to display forms to manage the canteen registrations
 require 'GiHighLevelsFormsNurseryRegistratrionsLibrary.php'; // Primitives library used to display forms to manage the nursery registrations
 require 'GiHighLevelsFormsSnackRegistratrionsLibrary.php';   // Primitives library used to display forms to manage the snack registrations
 require 'GiHighLevelsFormsLaundryRegistratrionsLibrary.php'; // Primitives library used to display forms to manage the laundry registrations
 require 'GiHighLevelsFormsExitPermissionsLibrary.php';       // Primitives library used to display forms to manage the exit permissions
 require 'GiHighLevelsFormsDocumentsApprovalsLibrary.php';    // Primitives library used to display forms to manage documents approvals and families' approvals
 require 'GiHighLevelsFormsEventsLibrary.php';                // Primitives library used to display forms to manage events and registrations to events
 require 'GiHighLevelsFormsWorkGroupsLibrary.php';            // Primitives library used to display forms to manage workgroups and registrations to workgroups
 require 'GiHighLevelsFormsAliasMessagesLibrary.php';         // Primitives library used to display forms to manage alias and send messages
 require 'GiHighLevelsFormsDonationsLibrary.php';             // Primitives library used to display forms to manage donations
 require 'GiHighLevelsFormsHolidaysLibrary.php';              // Primitives library used to display forms to manage holidays and opend special days
 require 'GiHighLevelsFormsConfigParametersLibrary.php';      // Primitives library used to display forms to manage config parameters
 require 'GiHighLevelsFormsJobsLibrary.php';                  // Primitives library used to display forms to manage jobs


/**
 * Initialization of the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.3
 *     - 2016-03-04 : change keywords in meta
 *     - 2016-06-03 : a same CSS of a plugin is included only one time, taken into account $CONF_CHARSET
 *
 * @since 2012-01-12
 *
 * @param $Title                String                Title of the web page
 * @param $TabStylesheets       Array of Strings      List of the paths of the different stylesheets of the web page
 * @param $TabJavascripts       Array of Strings      List of the paths of the different javascripts to include in the web page
 * @param $Style                String                The style of the body page
 * @param $OnLoadFct            String                Name of a javascript function with its parameters
 */
 function initGraphicInterface($Title, $TabStylesheets, $TabJavascripts, $Style = '', $OnLoadFct = '')
 {
     // Create a XHTML document
     echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
     echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".$GLOBALS['CONF_LANG']."\" lang=\"".$GLOBALS['CONF_LANG']."\">\n";
     echo "<head>\n";
     echo "\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".strtolower($GLOBALS['CONF_CHARSET'])."\" />\n";
     echo "\t<meta name=\"keywords\" content=\"Canteen Calandreta, intranet, Muret\" />\n";
     echo "\t<title>$Title</title>\n";

     // Insert the links of each stylesheet
     foreach($TabStylesheets as $CSS => $Media)
     {
         echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"$CSS\" media=\"$Media\" />\n";
     }

     // Insert each link on a javascript
     echo "\n\t<!-- JavaScripts -->\n";
     foreach($TabJavascripts as $CurrentValue)
     {
         echo "\t<script src=\"$CurrentValue\" type=\"text/JavaScript\"></script>\n";
     }

     // Insert javascript plugins and their stylesheets if necessary and if the current web page needs JS plugins
     $sCurrentWebPage = substr($_SERVER["PHP_SELF"], strpos($_SERVER["PHP_SELF"], "/", 1));

     $sCurrentPluginPath = $sCurrentWebPage;
     $ArrayPluginsToUse = array();
     while(($sCurrentPluginPath != '/') && ($sCurrentPluginPath != '\\'))
     {
         // Check if there are some plugin to use
         if (((isSet($GLOBALS["CONF_JS_PLUGINS_TO_USE"][$sCurrentPluginPath])) && (count($GLOBALS["CONF_JS_PLUGINS_TO_USE"][$sCurrentPluginPath]) > 0)))
         {
             // Add the plugins found to list of plugins to use
             $ArrayPluginsToUse = array_merge($ArrayPluginsToUse, $GLOBALS["CONF_JS_PLUGINS_TO_USE"][$sCurrentPluginPath]);
         }

         $sCurrentPluginPath = dirname($sCurrentPluginPath);
     }

     if (((isSet($GLOBALS["CONF_JS_PLUGINS_TO_USE"]['/'])) && (count($GLOBALS["CONF_JS_PLUGINS_TO_USE"]['/']) > 0)))
     {
         // Add the plugins found to list of plugins to use
         $ArrayPluginsToUse = array_merge($ArrayPluginsToUse, $GLOBALS["CONF_JS_PLUGINS_TO_USE"]['/']);
     }

     $sJSPluginsToLoad = "";
     if (count($ArrayPluginsToUse) > 0)
     {
         // We insert the Javascript used to init plugins
         echo "\t<script src=\"".$GLOBALS["CONF_ROOT_DIRECTORY"]."Plugins/JSInitPlugins.js\" type=\"text/JavaScript\"></script>\n";

         $sJSPluginsToLoad = "initPlugins(";

         // We must load Javascript plugins
         $ArrayPluginsToLoad = array();
         $ArrayJSIncluded = array();
         $ArrayCSSIncluded = array();
         foreach($ArrayPluginsToUse as $PluginName => $PluginParams)
         {
             // First, we insert JS of the plugin
             foreach($PluginParams["Scripts"] as $s => $CurrentScript)
             {
                 // We check if the JS script isn't already included
                 if (!in_array($CurrentScript, $ArrayJSIncluded))
                 {
                     echo "\t<script src=\"$CurrentScript\" type=\"text/JavaScript\"></script>\n";
                     $ArrayJSIncluded[] = $CurrentScript;
                 }
             }

             // Next, we add the init function of the plugin in the onLoad event of the web page
             $ArrayParameters = array();
             foreach($PluginParams["Params"] as $p => $CurrentParam)
             {
                 $ArrayParameters[] = "'$CurrentParam'";
             }

             // For some reasons, a same plugin can be used several times in the same web page
             // we add, at the end of its name, the suffix "__x" were x is a number
             $PluginTrueName = $PluginName;
             $iSuffixPluginNamePos = strpos($PluginName, '__');
             if ($iSuffixPluginNamePos !== FALSE)
             {
                 // Remove the suffix and keep the true name of the function to init the plugin
                 $PluginTrueName = substr($PluginName, 0, $iSuffixPluginNamePos);
             }

             $ArrayPluginsToLoad[] = "'".addslashes($PluginTrueName."(".implode(", ", $ArrayParameters).")")."'";

             // And we insert their stylesheets
             foreach($PluginParams["Css"] as $Media => $CurrentArrayCss)
             {
                 foreach($CurrentArrayCss as $c => $CSS)
                 {
                     // We check if the CSS isn't already included
                     if (((isset($ArrayCSSIncluded[$Media])) && (!in_array($CSS, $ArrayCSSIncluded[$Media])))
                         || (!isset($ArrayCSSIncluded[$Media])))
                     {
                         echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"$CSS\" media=\"$Media\" />\n";
                         $ArrayCSSIncluded[$Media][] = $CSS;
                     }
                 }
             }
         }

         unset($ArrayJSIncluded, $ArrayCSSIncluded);

         $sJSPluginsToLoad .= implode(", ", $ArrayPluginsToLoad).");";
     }

     // Display a favicon
     echo "<link rel=\"icon\" type=\"image/jpg\" href=\"".$GLOBALS["CONF_ROOT_DIRECTORY"]."GUI/Styles/LogoAstresIVD.jpg\" />\n";

     // Close the header and open the body of the XHTML document
     echo "</head>\n<body";

     if ($Style != '') {
         echo " class=\"$Style\"";
     }

     // Add a javascript function on the onLoad event
     if (($OnLoadFct != '') || ($sJSPluginsToLoad != ''))
     {
         echo " onLoad=\"$OnLoadFct;$sJSPluginsToLoad\"";
     }

     // Close the BODY tag
     echo ">\n";
 }


/**
 * Open the content of the web page
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-11 : new interface
 *
 * @since 2003-12-20
 */
 function openWebPage()
 {
     // Open the table which will contain the content of the web page (main menu, logo, forms,...)
     echo "<div id=\"webpage\">\n";
 }


/**
 * Display header of the application in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-12
 *
 * @param $Title    String    Title of the header
 */
 function displayHeader($Title)
 {
     echo "\n<!-- Header -->\n";
     echo "<div id=\"header\">\n";
     echo "\t<h1>$Title</h1>\n";
     echo "\t<div id=\"logo\">&nbsp;</div>\n";
     echo "</div>\n";
 }


/**
 * Display information about the logged user of the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-12
 *
 * @param $Session       Array of Strings      Session of the logged user with information about him.
 */
 function displayLoggedUser($Session)
 {
     if (isSet($Session["SupportMemberID"]))
     {
         // The logged user is a supporter
         echo "<p class=\"UserInfos\">".$Session["SupportMemberLastname"]." ".$Session["SupportMemberFirstname"]
              ." (".$Session["SupportMemberStateName"].")</p>";
     }
 }


/**
 * Add a <div> tag, with or without style, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-11
 *
 * @param $Style    String      CSS style
 */
 function openArea($Style = '')
 {
     if ($Style == '')
     {
         echo "<div>\n";
     }
     else
     {
         echo "<div $Style>\n";
     }
 }


/**
 * Generate a string containing a title (<h1> - <h6> tag), with or without style,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-12
 *
 * @param $Title    String    Title of the header
 * @param $Level    Integer   Level of the <hx> tag [1..6]
 * @param $Style    String    CSS style
 *
 * @return String    The title of the page
 */
 function generateTitlePage($Title, $Level = 2, $Style = '') {
     // The level is [1..6]
     if (($Level < 1) || ($Level > 6)) {
         $Level = 2;
     }

     if ($Style == '')
     {
         return "<h$Level>$Title</h$Level>\n";
     }
     else
     {
         return "<h$Level $Style>$Title</h$Level>\n";
     }
 }


/**
 * Display a title (<h1> - <h6> tag), with or without style, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-12
 *
 * @param $Title    String    Title of the header
 * @param $Level    Integer   Level of the <hx> tag [1..6]
 * @param $Style    String    CSS style
 */
 function displayTitlePage($Title, $Level = 2, $Style = '') {
     echo generateTitlePage($Title, $Level, $Style);
 }


/**
 * Open a block "paragraph"
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-20
 *
 * @param $Style    String      Name of the style which will be use in the paragraph
 */
 function openParagraph($Style = '')
 {
     if ($Style == '')
     {
         echo "<p>\n";
     }
     else
     {
         echo "<p class=\"$Style\">\n";
     }
 }


/**
 * Display a text with a style, in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2016-10-21 : Title parameter added
 *
 * @since 2003-12-20
 *
 * @param $Text     String      Text to display
 * @param $Style    String      Name of the style which will be use to display the text
 */
 function displayStyledText($Text, $Style = '', $Title = '')
 {
     echo generateStyledText($Text, $Style, $Title);
 }


/**
 * Generate a text with a style, in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2016-10-21 : Title parameter added
 *
 * @since 2004-04-29
 *
 * @param $Text     String      Text to display
 * @param $Style    String      Name of the style which will be use to display the text
 *
 * @return String               The styled text generated
 */
 function generateStyledText($Text, $Style = '', $Title = '')
 {
     $tmp = '';
     if (empty($Style))
     {
         if (empty($Title))
         {
             $tmp = $Text;
         }
         else
         {
             $tmp = "<span title=\"$Title\">$Text</span>";
         }
     }
     else
     {
         $tmp = "<span class=\"$Style\"";
         if (!empty($Title))
         {
             $tmp .= " title=\"$Title\"";
         }

         $tmp .= ">$Text</span>";
     }

     return $tmp;
 }


/**
 * Insert a number of <br />
 *
 * @author STNA/7SQ
 * @version 2.0
 * @since 2003-12-20
 *
 * @param $NumBR     Integer      Number of <br /> to insert [1..n]
 */
 function displayBR($NumBR = 1)
 {
     // $NumBR must be > 0
     if ($NumBR > 0)
     {
         switch($NumBR)
         {
             case 1: echo "<br />";
                     break;
             case 2: echo "<br /><br />";
                     break;
             case 3: echo "<br /><br /><br />";
                     break;
             case 4: echo "<br /><br /><br /><br />";
                     break;
             case 5: echo "<br /><br /><br /><br /><br />";
                     break;
             default:
                     for($i = 0 ; $i < $NumBR ; $i++)
                     {
                         echo "<br />";
                     }
                     break;
         }
         echo "\n";
     }
 }


/**
 * generate a number of <br />
 *
 * @author STNA/7SQ
 * @version 2.0
 * @since 2004-04-28
 *
 * @param $NumBR     String      Number of <br /> to insert [1..n]
 *
 * @return String                String with a number of <br /> tags
 */
 function generateBR($NumBR = 1)
 {
     // $NumBR must be > 0
     $tmp = "";

     if ($NumBR > 0)
     {
         switch($NumBR)
         {
             case 1: $tmp =  "<br />";
                     break;
             case 2: $tmp = "<br /><br />";
                     break;
             case 3: $tmp =  "<br /><br /><br />";
                     break;
             case 4: $tmp =  "<br /><br /><br /><br />";
                     break;
             case 5: $tmp =  "<br /><br /><br /><br /><br />";
                     break;
             default:
                     for($i = 0 ; $i < $NumBR ; $i++)
                     {
                         $tmp .= "<br />";
                     }
                     break;
         }
         $tmp .= "\n";
     }

     return $tmp;
 }


/**
 * Display a picture with a style, in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-21
 *
 * @param $Pic      String      Path of the picture
 * @param $Caption  String      Caption of the picture (ALT property)
 * @param $Style    String      Name of the style which will be use to display the picture
 */
 function displayStyledPicture($Pic, $Caption = '', $Style = '')
 {
     if ($Pic != '')
     {
         if ($Style == '')
         {
             echo "<img src=\"$Pic\" alt=\"$Caption\" title=\"$Caption\" />\n";
         }
         else
         {
             echo "<img src=\"$Pic\" class=\"$Style\" alt=\"$Caption\" title=\"$Caption\" />\n";
         }
     }
 }


/**
 * Generate a picture with a style in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-17
 *
 * @param $Pic      String      Path of the picture
 * @param $Caption  String      Caption of the picture (ALT property)
 * @param $Style    String      Name of the style which will be use to display the picture
 *
 * @return String               XHTML img tag
 */
 function generateStyledPicture($Pic, $Caption = '', $Style = '')
 {
     if ($Pic != '')
     {
         if ($Style == '')
         {
             return "<img src=\"$Pic\" alt=\"$Caption\" title=\"$Caption\" />\n";
         }
         else
         {
             return "<img src=\"$Pic\" class=\"$Style\" alt=\"$Caption\" title=\"$Caption\" />\n";
         }
     }

     // ERROR
     return '';
 }


/**
 * Display a separator in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-26
 *
 * $Text  String  Text to display inside of the left side of the separator
 */
 function displaySeparator($Text)
 {
     echo "<table class=\"separator\" cellpadding=\"0\" cellspacing=\"0\">\n<tr>\n\t";
     echo "<td class=\"LeftSeparator\">$Text</td>";
     echo "<td class=\"MiddleSeparator\">&nbsp;</td>";
     echo "<td class=\"RightSeparator\">&nbsp;</td>\n";
     echo "</tr>\n</table>\n";
 }


/**
 * Generate an opening graphic frame in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-03-21
 *
 * @param $Caption    String    Caption of the frame
 *
 * @return String               XHTML table tag
 */
 function genarateOpenFrame($Caption)
 {
     $tmp = "<table class=\"Frame\" cellspacing=\"0\">\n";
     $tmp .= "<thead>\n";
     $tmp .= "<tr>\n";
     $tmp .= "\t<th class=\"Frame\">$Caption</th>\n";
     $tmp .= "</tr>\n";
     $tmp .= "</thead>\n";
     $tmp .= "<tbody>\n";
     $tmp .= "<tr>\n";
     $tmp .= "\t<td class=\"Frame\">\n";

     return $tmp;
 }


/**
 * Open a graphic frame in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2008-03-21 : use genarateOpenFrame to display the opening frame
 *
 * @since 2003-12-27
 *
 * @param $Caption    String           Caption of the frame
 */
 function openFrame($Caption)
 {
     echo genarateOpenFrame($Caption);
 }


/**
 * Generate a closing graphic frame in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-03-21
 *
 * @return String               XHTML table tag
 */
 function generateCloseFrame()
 {
     $tmp = "\t</td>\n";
     $tmp .= "</tr>\n";
     $tmp .= "</tbody>\n";
     $tmp .= "</table>\n";

     return $tmp;
 }


/**
 * Close the graphic frame in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2008-03-21 : use generateCloseFrame to display the closing frame
 *
 * @since 2003-12-27
 */
 function closeFrame()
 {
     echo generateCloseFrame();
 }


/**
 * Generate an opening a graphic styled frame in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-03-21
 *
 * @param $Caption         String           Caption of the frame
 * @param $StyleTable      String           Style used for the table
 * @param $StyleHead       String           Style used for the table header
 * @param $StyleBody       String           Style used for the body table
 *
 * @return String          XHTML table tag
 */
 function generateOpenStyledFrame($Caption, $StyleTable = "", $StyleHead = "", $StyleBody = "")
 {
     return "<table class=\"$StyleTable\" cellspacing=\"0\">\n<tbody>\n<tr>\n\t<th class=\"$StyleHead\">$Caption</th>\n</tr>\n<tr>\n\t<td class=\"$StyleBody\">\n";
 }


/**
 * Open a graphic styled frame in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2008-03-21 : use generateOpenStyledFrame to display the opening styled frame
 *
 * @since 2004-01-25
 *
 * @param $Caption         String           Caption of the frame
 * @param $StyleTable      String           Style used for the table
 * @param $StyleHead       String           Style used for the table header
 * @param $StyleBody       String           Style used for the body table
 *
 */
 function openStyledFrame($Caption, $StyleTable = "", $StyleHead = "", $StyleBody = "")
 {
     echo generateOpenStyledFrame($Caption, $StyleTable, $StyleHead, $StyleBody);
 }


/**
 * Generate a closing the graphic styled frame in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-03-21
 *
 * @return String          XHTML table tag
 */
 function generateCloseStyledFrame()
 {
     return "\t</td>\n</tr>\n</tbody>\n</table>\n";
 }


/**
 * Close the graphic styled frame in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2008-03-21 : use generateCloseStyledFrame to display the closing styled frame
 *
 * @since 2003-01-25
 */
 function closeStyledFrame()
 {
     echo generateCloseStyledFrame();
 }


/**
 * Close a block "paragraph"
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-20
 */
 function closeParagraph()
 {
     echo "</p>\n";
 }


/**
 * Close an opened area (<div> tag), in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-11
 */
 function closeArea()
 {
     echo "</div>\n";
 }


 /**
 * Display footer of the application in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-01-12
 *
 * @param $Title    String    Title of the footer
 */
 function displayFooter($Title)
 {
     echo "\n<!-- Footer -->\n";
     echo "<div id=\"footer\">\n";
     echo "\t<p>&nbsp;$Title&nbsp;\n";
     echo "\t\t<!-- Validation pictures -->\n";
     echo "\t\t<a class=\"w3c\" href=\"http://validator.w3.org/check/referer\"><img src=\"".$GLOBALS['CONF_ROOT_DIRECTORY']."GUI/Styles/valid-xhtml.png\" alt=\"Valid XHTML 1.0!\" title=\"Valid XHTML 1.0!\" /></a>\n";
     echo "\t\t<a class=\"w3c\" href=\"http://jigsaw.w3.org/css-validator/check/referer\"><img src=\"".$GLOBALS['CONF_ROOT_DIRECTORY']."GUI/Styles/valid-css.png\" alt=\"Valid CSS!\" title=\"Valid CSS!\" /></a>&nbsp;\n";
     echo "\t</p>\n";
     echo "</div>\n";
 }


/**
 * Close the content of the web page
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2007-01-11 : new interface
 *
 * @since 2003-12-20
 */
 function closeWebPage()
 {
     echo "</div>\n";
 }


/**
 * Finalization of the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-12
 */
 function closeGraphicInterface()
 {
     /*if (!$GLOBALS['CONF_MODE_DEBUG'])
     {
         echo <<<END
         <!-- Piwik -->
         <script type="text/javascript">
         var pkBaseURL = (("https:" == document.location.protocol) ? "https://imperia5.dsna-dti.aviation/piwik/" : "http://imperia5.dsna-dti.aviation/piwik/");
         document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
         </script><script type="text/javascript">
         piwik_action_name = '';
         piwik_idsite = 4;
         piwik_url = pkBaseURL + "piwik.php";
         piwik_log(piwik_action_name, piwik_idsite, piwik_url);
         </script>
         <object><noscript><p>Web analytics <img src="http://imperia5.dsna-dti.aviation/piwik/piwik.php?idsite=4" style="border:0" alt=""/></p></noscript></object></a>
         <!-- End Piwik Tag -->
END;
    }  */

     echo "</body>\n</html>";
 }
?>