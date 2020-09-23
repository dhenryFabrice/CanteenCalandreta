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
 * Web services module : access point to all mail services (XML)
 *
 * @author STNA/7SQ
 * @version 3.6
 * @since 2010-02-09
 */

 $DOCUMENT_ROOT = dirname(__FILE__)."/../";
 //$DOCUMENT_ROOT = "/local/data/www5/Astres/";

 include_once($DOCUMENT_ROOT."Common/DefinedConst.php");
 include_once($DOCUMENT_ROOT."Common/Config.php");
 include_once($DOCUMENT_ROOT."Common/FctLibrary.php");
 include_once($DOCUMENT_ROOT."Languages/SetLanguage.php");
 include_once($DOCUMENT_ROOT."/WSLibrary.php");

 // To use the PEAR library
 require_once("DB.php");

 $CONF_EMAIL_TEMPLATES_DIRECTORY_HDD = $DOCUMENT_ROOT."Templates/";

 $DbCon = dbConnection();

 $ArrayMailServices = array_keys($CONF_MAIL_SERVICES);
 foreach($ArrayMailServices as $ms => $MailService)
 {
     // Get the configuraiotn of the mail service
     $ArrayConfig = $CONF_MAIL_SERVICES[$MailService][WEB_SERVICE_CONFIG];

     // Open the mailbox
     $mbox = imap_open($ArrayConfig['Mailbox'], $ArrayConfig['Username'], $ArrayConfig['Password']);
     $headers = imap_headers($mbox);
     if ($headers != false)
     {
         // Get the not read mails with a specific title
         $ConditionDate = ' SINCE '.date('d-M-Y');
         $ArrayMsgFound = imap_search($mbox, "UNSEEN SUBJECT \"".$ArrayConfig['Subject']."\"$ConditionDate");

         if (!empty($ArrayMsgFound))
         {
             foreach($ArrayMsgFound as $i => $no)
             {
                 // We read the content of the mail
                 $objHeader = imap_headerinfo($mbox, $no);
                 getmsg($mbox, $no);

                 if (!empty($plainmsg))
                 {
                     $plainmsg = trim(strtolower($plainmsg));

                     // We check if this mail is for the current mail service
                     $iPosStart = strpos($plainmsg, "<$MailService>");
                     if ($iPosStart !== FALSE)
                     {
                         $iPosEnd = strpos($plainmsg, "</$MailService>");
                         if ($iPosEnd !== FALSE)
                         {
                             $sXMLData = substr($plainmsg, $iPosStart, $iPosEnd - $iPosStart + strlen("</$MailService>"));
                             $sXMLData = str_replace(array("\r\n"), array(""), $sXMLData);
                             $sXMLData = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>".$sXMLData;

                             if (file_exists(dirname(__FILE__)."/$MailService/Service.php"))
                             {
                                 require_once dirname(__FILE__)."/$MailService/Service.php";
                                 $ServiceToLaunch = 'RunMail'.ucfirst($MailService);
                                 if (function_exists($ServiceToLaunch))
                                 {
                                     // Get the parameters of the mail service
                                     $ServiceParams = $CONF_MAIL_SERVICES[$MailService][WEB_SERVICE_PARAMS];

                                     // Add the XML content of the mail to the parameters of the mail service
                                     $ServiceParams[WS_XMLDOC] = $sXMLData;

                                     // Add the header of the mail
                                     $ServiceParams[WS_MAIL_HEADER] = $objHeader;

                                     call_user_func(
                                                    $ServiceToLaunch,
                                                    $DbCon,
                                                    $CONF_MAIL_SERVICES[$MailService][WEB_SERVICE_CONFIG],
                                                    $ServiceParams
                                                   );
                                 }
                             }
                         }
                     }
                 }
             }
         }
     }

     // Close the mailbox
     imap_close($mbox);
 }

 // We close the database connection
 dbDisconnection($DbCon);


 //################################### Functions to decode imap mails #########################
 function getmsg($mbox,$mid) {
     // input $mbox = IMAP stream, $mid = message id
     // output all the following:
     global $htmlmsg,$plainmsg,$charset,$attachments;

     // the message may in $htmlmsg, $plainmsg, or both
     $htmlmsg = $plainmsg = $charset = '';
     $attachments = array();

     // HEADER
     $h = imap_header($mbox,$mid);

     // add code here to get date, from, to, cc, subject...
     // BODY
     $s = imap_fetchstructure($mbox,$mid);

     if (!isset($s->parts))  // not multipart
        getpart($mbox,$mid,$s,0);  // no part-number, so pass 0
     else {  // multipart: iterate through each part
         foreach ($s->parts as $partno0=>$p)
             getpart($mbox,$mid,$p,$partno0+1);
     }
 }


 function getpart($mbox,$mid,$p,$partno) {
     // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart
     global $htmlmsg,$plainmsg,$charset,$attachments;

     // DECODE DATA
     $data = ($partno)?
         imap_fetchbody($mbox,$mid,$partno):  // multipart
         imap_body($mbox,$mid);  // not multipart
     // Any part may be encoded, even plain text messages, so check everything.
     if ($p->encoding==4)
         $data = quoted_printable_decode($data);
     elseif ($p->encoding==3)
         $data = base64_decode($data);
     // no need to decode 7-bit, 8-bit, or binary

     // PARAMETERS
     // get all parameters, like charset, filenames of attachments, etc.
     $params = array();

     if (isset($p->parameters))
         foreach ($p->parameters as $x)
             $params[ strtolower( $x->attribute ) ] = $x->value;

     if (isset($p->dparameters))
         foreach ($p->dparameters as $x)
             $params[ strtolower( $x->attribute ) ] = $x->value;

     // ATTACHMENT
     // Any part with a filename is an attachment,
     // so an attached text file (type 0) is not mistaken as the message.
     if (isset($params['filename']) || isset($params['name'])) {
         // filename may be given as 'Filename' or 'Name' or both
         $filename = ($params['filename'])? $params['filename'] : $params['name'];

         // filename may be encoded, so see imap_mime_header_decode()
         $attachments[$filename] = $data;  // this is a problem if two files have same name
     }

     // TEXT
     elseif ($p->type==0 && $data) {
         // Messages may be split in different parts because of inline attachments,
         // so append parts together with blank row.
         if (strtolower($p->subtype)=='plain')
             $plainmsg .= trim($data) ."\n\n";
         else
             $htmlmsg .= $data ."<br><br>";
         $charset = $params['charset'];  // assume all parts are same charset
     }

     // EMBEDDED MESSAGE
     // Many bounce notifications embed the original message as type 2,
     // but AOL uses type 1 (multipart), which is not handled here.
     // There are no PHP functions to parse embedded messages,
     // so this just appends the raw source to the main message.
     elseif ($p->type==2 && $data) {
         $plainmsg .= trim($data) ."\n\n";
     }

     // SUBPART RECURSION
     if (isset($p->parts)) {
         foreach ($p->parts as $partno0=>$p2)
             getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
     }
 }
?>