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
 * Support module : process the creation of a new event. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2012-01-10
 */


/**
 * Check if the value of the parameter is a valide e-mail address
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-12
 *
 * @param $Email          String     Value to ckeck if it's a valide e-mail address
 *
 * @return Boolean                   TRUE if the value is a valide e-mail address, FALSE otherwise
 */
 function isValideEmailAddress($Email)
 {
     $ExpReg = "/[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,3}/" ;
     if (preg_match_all($ExpReg, $Email, $Resultat) == 0)
     {
         // It isn't a valide e-mail address
         return FALSE;
     }
     else
     {
         // It's a valide e-mail address
         return TRUE;
     }
 }


/**
 * Get the MIME content-type of an extension file
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-13
 *
 * @param $ExtensionFile          String     Extension of the file for which we want to get the MIME content-type
 *
 * @return String                            The MIME content-type of the given extension
 */
 function getContentTypeName($ExtensionFile)
 {
     switch(strToLower($ExtensionFile))
     {
         // Text files and files in text/plain format
         case "txt":
         case "zip":
                 $ContentTypeName = "text/plain";
                 break;

         case "rtf":
                 $ContentTypeName = "text/richtext";
                 break;

         case "htm":
         case "html":
                 $ContentTypeName = "text/html";
                 break;

         // Office files
         case "doc":
                 $ContentTypeName = "application/msword";
                 break;

         case "xls":
                 $ContentTypeName = "application/vnd.ms-excel";
                 break;

         case "ppt":
                 $ContentTypeName = "application/vnd.ms-powerpoint";
                 break;

         // Picture files
         case "jpg":
         case "jpeg":
                 $ContentTypeName = "image/jpg";
                 break;

         case "bmp":
                 $ContentTypeName = "image/bmp";
                 break;

         // Video files
         case "avi":
                 $ContentTypeName = "video/x-msvideo";
                 break;

         case "mpe":
         case "mpg":
         case "mpeg":
                 $ContentTypeName = "video/mpeg";
                 break;

         case "qt":
         case "mov":
                 $ContentTypeName = "video/quicktime";
                 break;

         // Executable and other files
         case "bin":
         case "exe":
         case "pdf":
         default:
                 $ContentTypeName = "application/octet-stream";
                 break;
     }

     return $ContentTypeName;
 }


/**
 * Send a e-mail to a mailing list with or without attachment
 *
 * @author STNA/7SQ, Christophe Javouhey
 * @version 3.0
 *     - 2004-10-14 : v1.0. Take into account the global CONF_EMAIL_ANONYMOUS_SENDER variable to send anonymous
 *                    e-mails
 *     - 2006-07-26 : Patch the way to take into account the "cc" and the "bcc"
 *     - 2007-10-10 : remove \n from e-mail adresses if it exists
 *     - 2009-10-15 : allow to send an e-mail with an empty "to"
 *     - 2010-09-24 : if e-mail send only with CC and TO is the application's e-mail and not in debug mode
 *                    set TO to null value
 *     - 2010-12-09 : keep each e-mail in To once (same thing for CC and BCC) and uses mb_encode_mimeheader()
 *                    for the subject
 *     - 2011-09-20 : allow to use another template directory path and another reply-to address
 *     - 2016-04-12 : patch a pb of characters in the subject of the mail
 *     - 2016-06-20 : taken into account $CONF_CHARSET and add a Message-ID
 *     - 2017-01-19 : v2.9. taken into account Content-Type
 *     - 2019-11-18 : v3.0. use PHPMailer lib and taken into account $CONF_EMAIL_SMTP_SERVERS
 *
 * @since 2004-06-12
 *
 * @param $Session                Array of Strings    Session of the logged user
 * @param $MailingList            String              List of e-mail addresses
 * @param $Subject                String              Subject of the e-mail
 * @param $TemplateName           String              Name of the tempate to use for the content of the e-mail
 * @param $ReplaceInTemplate      Array of Strings    Values to use in the given template
 * @param $Attachment             Array of Strings    List of files paths to send with the e-mail
 * @param $SpecialTemplatePath    String              Path of the templates directory if different from
 *                                                    $CONF_EMAIL_TEMPLATES_DIRECTORY_HDD
 * @param $SpecialReplyTo         String              Other Replay-to address to use if different from
 *                                                    $CONF_EMAIL_REPLY_EMAIL_ADDRESS
 * @param $ContentType            String              Content-type of the e-mail (text/plain, text/html)
 *
 * @return Boolean                TRUE if the e-mail is sent, FALSE otherwise
 */
 function sendEmail($Session, $MailingList, $Subject, $TemplateName, $ReplaceInTemplate = array(), $Attachment = array(), $SpecialTemplatePath = '', $SpecialReplyTo = '', $ContentType = 'text/html')
 {
     global $DbCon;

     // We get the current SMTP to use
     $ArraySMTPServers = array_keys($GLOBALS['CONF_EMAIL_SMTP_SERVERS']);
     $iNbSMTPServers = count($ArraySMTPServers);

     if (file_exists($GLOBALS['CONF_EXPORT_DIRECTORY_HDD']."SMTP.txt"))
     {
         $CurrentSMTPServer = file_get_contents($GLOBALS['CONF_EXPORT_DIRECTORY_HDD']."SMTP.txt");
     }
     else
     {
         // By default, we use the first defined SMTP server
         if (empty($ArraySMTPServers))
         {
             // No SMTP server defined
             $CurrentSMTPServer = "localhost";
         }
         else
         {
             $CurrentSMTPServer = $ArraySMTPServers[0];
         }

         // Save the default SMTP server
         file_put_contents($GLOBALS['CONF_EXPORT_DIRECTORY_HDD']."SMTP.txt", $CurrentSMTPServer);
     }

     $Mailer = New PHPMailer\PHPMailer\PHPMailer();
     $Mailer->IsSMTP(TRUE);
     $Mailer->IsHTML(TRUE);

     // Who is the author of this e-mail
     if (is_Null($Session))
     {
         // The author is the software
         if ($CurrentSMTPServer == 'localhost')
         {
             $AuthorEmail = $GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"];
         }
         else
         {
             $AuthorEmail = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['From'];
         }
     }
     else
     {
         if ($GLOBALS["CONF_EMAIL_ANONYMOUS_SENDER"])
         {
             // The e-mail address of the sender isn't displayed
             // So, the author is the software
             if ($CurrentSMTPServer == 'localhost')
             {
                 $AuthorEmail = $GLOBALS["CONF_EMAIL_INTRANET_EMAIL_ADDRESS"];
             }
             else
             {
                 $AuthorEmail = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['From'];
             }
         }
         else
         {
             // The e-mail address of the sender is displayed
             if (isSet($Session["SupportMemberEmail"]))
             {
                 // The author is a supporter
                 $AuthorEmail = $Session["SupportMemberEmail"];
             }
             else
             {
                 // The author is the software
                 $AuthorEmail = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['From'];
             }
         }
     }

     // Configuration of the SMTP server
     if ($CurrentSMTPServer != 'localhost')
     {
         $Mailer->Host = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Host'];
         $Mailer->Port = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Port'];
         $Mailer->FromName = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['FromName'];
         $Mailer->From = str_replace(array("\n"), array(''), $AuthorEmail);

         if ($GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Auth'])
         {
             $Mailer->Username = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['User'];
             $Mailer->Password = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Pwd'];
             $Mailer->SMTPSecure = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Secure'];
             $Mailer->SMTPAuth = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Auth'];

             if ((isset($GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Options']))
                 && (!empty($GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Options'])))
             {
                 $Mailer->SMTPOptions = $GLOBALS['CONF_EMAIL_SMTP_SERVERS'][$CurrentSMTPServer]['Options'];
             }
         }
     }

     // Get the content of the template
     $TemplateDirectoryPath = $GLOBALS["CONF_EMAIL_TEMPLATES_DIRECTORY_HDD"];
     if (!empty($SpecialTemplatePath))
     {
         // We use another path
         $TemplateDirectoryPath = $SpecialTemplatePath;
     }

     if ($TemplateName == '')
     {
         // Error : no template selected
         return FALSE;
     }
     else
     {
         if (file_exists($TemplateDirectoryPath.$TemplateName.".php"))
         {
             // The template exists : we get its content
             $fp = fopen($TemplateDirectoryPath.$TemplateName.".php", "rt");
             $TemplateContent = fread($fp, filesize($TemplateDirectoryPath.$TemplateName.".php"));
             fclose($fp);

             // We use the given values to replace some parts of the template
             $TemplateContent = str_replace($ReplaceInTemplate[0], $ReplaceInTemplate[1], $TemplateContent) ;
         }
         else
         {
             // Error : the template doesn't exist
             return FALSE;
         }
     }

     // Recipients of the e-mail (TO, CC and BCC)
     $Reply = $GLOBALS["CONF_EMAIL_REPLY_EMAIL_ADDRESS"];

     $iNbRecipients = 0;
     if (array_key_exists("to", $MailingList))
     {
         if (count($MailingList["to"]) > 0)
         {
             // Keep once each e-mail
             $MailingList["to"] = array_unique($MailingList["to"]);
             $iNbRecipients = count($MailingList["to"]);

             foreach($MailingList["to"] as $a => $EmailAddress)
             {
                 $Mailer->AddAddress(str_replace(array("\n"), array(''), $EmailAddress));
             }

             $To = str_replace(array("\n"), array(''), implode(", ", $MailingList["to"]));

             // If only mail send for CC (so, TO = e-mail of the application) : we can remove this mail
             // but not if the application is in debug mode
             if ((!$GLOBALS['CONF_MODE_DEBUG']) && ($To == $GLOBALS['CONF_EMAIL_INTRANET_EMAIL_ADDRESS']))
             {
                 $Mailer->ClearAddresses();
             }
         }
         else
         {
             $Mailer->ClearAddresses();
         }
     }
     else
     {
         $Mailer->ClearAddresses();
     }

     if ((array_key_exists("cc", $MailingList)) && (count($MailingList["cc"]) > 0))
     {
         // Keep once each e-mail
         $MailingList["cc"] = array_unique($MailingList["cc"]);
         $iNbRecipients = count($MailingList["cc"]);

         foreach($MailingList["cc"] as $a => $EmailAddress)
         {
             $Mailer->AddCC(str_replace(array("\n"), array(''), $EmailAddress));
         }
     }

     if ((array_key_exists("bcc", $MailingList)) && (count($MailingList["bcc"]) > 0))
     {
         // Keep once each e-mail
         $MailingList["bcc"] = array_unique($MailingList["bcc"]);
         $iNbRecipients = count($MailingList["bcc"]);

         foreach($MailingList["bcc"] as $a => $EmailAddress)
         {
             $Mailer->AddBCC(str_replace(array("\n"), array(''), $EmailAddress));
         }
     }

     // Set charset
     switch(strToUpper($GLOBALS['CONF_CHARSET']))
     {
         case 'UTF-8':
             $Mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
             break;

         case 'ISO-8859-1':
         default:
             $Mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_ISO88591;
             break;
     }

     // Message content
     $Mailer->Body = $TemplateContent;

     // File attachment
     foreach($Attachment as $CurrentFile)
     {
         if (file_exists($CurrentFile))
         {
             // The file exists
             $Mailer->addAttachment($CurrentFile);
         }
     }

     // Reply-to e-mail address
     if ((!empty($Reply)) && (!empty($SpecialReplyTo)))
     {
         // We use another replay-to address
         $Reply = $SpecialReplyTo;
     }

     $Mailer->addReplyTo($Reply);

     // Subject of the e-mail
     $Mailer->Subject = $Subject;

     // Send the e-mail
     $bResult = $Mailer->Send();
     if ($bResult)
     {
         // Success : e-mail sent
         if ($GLOBALS['CONF_LOG_USE_STATS'])
         {
             dbUpdateStat($DbCon, date('Y-m-d H:i:s'), STAT_TYPE_NB_EMAILS_SENT, $iNbRecipients, $CurrentSMTPServer);
         }
     }
     else
     {
         // Error : we set the next SMTP server
         if ($GLOBALS['CONF_LOG_USE_STATS'])
         {
             dbUpdateStat($DbCon, date('Y-m-d H:i:s'), STAT_TYPE_NB_EMAILS_ERRORS, $iNbRecipients, $CurrentSMTPServer);
         }

         if ($CurrentSMTPServer != 'localhost')
         {
             $iPos = array_search($CurrentSMTPServer, $ArraySMTPServers);
             if ($iPos !== FALSE)
             {
                 if ($iPos < $iNbSMTPServers - 1)
                 {
                     // Next SMTP server
                     $iPos++;
                 }
                 else
                 {
                     // First SMTP server
                     $iPos = 0;
                 }

                 // Save the SMTP server to use for the nex send
                 file_put_contents($GLOBALS['CONF_EXPORT_DIRECTORY_HDD']."SMTP.txt", $ArraySMTPServers[$iPos]);
             }
         }
     }

     $Mailer->SmtpClose();

     return $bResult;
 }
?>