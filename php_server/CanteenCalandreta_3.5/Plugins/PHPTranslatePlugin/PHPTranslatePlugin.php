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
 * PHP plugin planning nursery auto save module : when the user check/uncheck a checkbox
 * in the planning, the nursery registration is auto save/deleted in the database
 *
 * @author Christophe Javouhey
 * @version 1.4
 *     - 2014-01-02 : taken into account english language
 *     - 2014-01-10 : for the "delete" action, we add some "if" to check the content of variables
 *                    (must match with content database)
 *     - 2014-03-31 : taken into account Occitan language
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *
 * @since 2013-09-12
 */


 $CONF_LANG_OF_TRANSLATION_TRANSLATE_PLUGIN = 'fr';
 $CONF_MIN_LENGTH_TRANSLATE_PLUGIN          = 10;


 // Include Config.php because of the name of the session
 require '../../GUI/GraphicInterface.php';

 if (array_key_exists('MsgToTranslate', $_GET))
 {
     // Gte the text to translate
     $sMsgToTranslate = strTolower(trim(strip_tags(stripslashes(rawurldecode($_GET['MsgToTranslate'])))));
     if (ord(substr($sMsgToTranslate, -1)) == 160)
     {
         // Remove this character
         $sMsgToTranslate = trim(substr($sMsgToTranslate, 0, -1));
     }

     if (substr($sMsgToTranslate, -1) == '*')
     {
         // Remove this character
         $sMsgToTranslate = trim(substr($sMsgToTranslate, 0, -1));
     }

     if (substr($sMsgToTranslate, -1) == ':')
     {
         // Remove this character
         $sMsgToTranslate = trim(substr($sMsgToTranslate, 0, -1));
     }

     if (!empty($sMsgToTranslate))
     {
         // Get the translation file of the original language
         switch($CONF_LANG)
         {
             case 'fr':
                 $ArrayOriginalLangFile = file("../../Languages/Francais.lang.php");
                 break;

             case 'oc':
                 $ArrayOriginalLangFile = file("../../Languages/Occitan.lang.php");
                 break;

             default:
                 $ArrayOriginalLangFile = file("../../Languages/English.lang.php");
                 break;
         }

         // We detect the charset : it must be ISO-8859-1
         /*if ((strToUpper($CONF_CHARSET) == 'ISO-8859-1') && (mb_detect_encoding($sMsgToTranslate, 'UTF-8') == 'UTF-8'))
         {
             $sMsgToTranslate = utf8_decode($sMsgToTranslate);
         }*/

         // Search the translation of the text
         $Index = null;
         $ArrayPossibleIndexes = array();
         foreach($ArrayOriginalLangFile as $i => $Message)
         {
             // $LANG_xxxxx = "xxxxxx xx xxxxxx";
             $iPosEqual = strpos($Message, '=');
             if ($iPosEqual !== FALSE)
             {
                 // Keep only the message, without $variable and = and ";
                 $Message = strip_tags(stripslashes(substr($Message, $iPosEqual + 3, -4)));
                 if (stripos($Message, $sMsgToTranslate) !== FALSE)
                 {
                     $ArrayPossibleIndexes[$i] = abs(strlen($Message) - strlen($sMsgToTranslate));
                 }
                 elseif ((stripos($sMsgToTranslate, $Message) !== FALSE) && (strlen($Message) >= $CONF_MIN_LENGTH_TRANSLATE_PLUGIN))
                 {
                     // In the case of the displayed message contents computed values (ex : W45-2014)
                     $ArrayPossibleIndexes[$i] = abs(strlen($Message) - strlen($sMsgToTranslate));
                 }
             }
         }

         // If several possibilities, find the nearst
         if (!empty($ArrayPossibleIndexes))
         {
             $iBestDiff = 100000;
             foreach($ArrayPossibleIndexes as $i => $Diff)
             {
                 if ($Diff < $iBestDiff)
                 {
                     $iBestDiff = $Diff;
                     $Index = $i;
                     if ($iBestDiff == 0)
                     {
                         // Stop the search
                         break;
                     }
                 }
             }
         }

         if ($Index > 0)
         {
             switch($CONF_LANG_OF_TRANSLATION_TRANSLATE_PLUGIN)
             {
                 case 'fr':
                     $ArrayTranslationLangFile = file("../../Languages/Francais.lang.php");
                     break;

                 case 'oc':
                     $ArrayTranslationLangFile = file("../../Languages/Occitan.lang.php");
                     break;

                 default:
                     $ArrayTranslationLangFile = file("../../Languages/English.lang.php");
                     break;
             }

             $iPosEqual = strpos($ArrayTranslationLangFile[$Index], '=');
             $TranslatedMessage = substr($ArrayTranslationLangFile[$Index], $iPosEqual + 3, -4);

             // Send the translation
             header("Content-type: text/html; charset=".strtolower($CONF_CHARSET));
             echo $TranslatedMessage;

             unset($ArrayOriginalLangFile, $ArrayTranslationLangFile, $ArrayPossibleIndexes);
         }
         else
         {
             header("Content-type: text/html; charset=".strtolower($CONF_CHARSET));
             echo '503';
         }
     }
     else
     {
         header("Content-type: text/html; charset=".strtolower($CONF_CHARSET));
         echo '503';
     }
 }
 else
 {
     header("Content-type: text/html; charset=".strtolower($CONF_CHARSET));
     echo '503';
 }
?>
