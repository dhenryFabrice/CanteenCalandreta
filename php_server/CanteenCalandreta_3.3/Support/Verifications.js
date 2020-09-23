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
 * Support module : functions used to validate some forms
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-01-13
 */


/**
 * Function used to validate the login form
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2006-04-18 : taken into account the new MD5 library
 *
 * @since 2004-02-04
 *
 * @param LoginMsgError         String     Error message if the login field is empty
 * @param PasswordMsgError      String     Error message if the password field is empty
 *
 * @return Boolean              TRUE if the login form is correctly entered, FALSE otherwise
 */
 function VerificationIndexPage(LoginMsgError, PasswordMsgError)
 {
     if (document.forms[0].sLogin.value == "")
     {
         alert(LoginMsgError);
         return false;
     }
     else if (document.forms[0].sPassword.value == "")
     {
         alert(PasswordMsgError);
         return false;
     }
     else
     {
         // Hash
         var Id = new String(document.forms[0].sLogin.value) ;
         var Pwd = new String(document.forms[0].sPassword.value);

         // This code is useful when the MD5 library of henri Torgemane is used
         document.forms[1].hidEncLogin.value = MD5(Id.toLowerCase());
         document.forms[1].hidEncPassword.value = MD5(Pwd.toLowerCase());
         return true;
     }
 }


/**
 * Function used to validate the login form of OpenID
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-02-04
 *
 * @param OpenIDUrlMsgError     String     Error message if the OpenID Url field is empty
 *
 * @return Boolean              TRUE if the login form of OpenID is correctly entered,
 *                              FALSE otherwise
 */
 function VerificationOpenIDIndexPage(OpenIDUrlMsgError)
 {
     if (document.forms[2].openid_identifier.value == "")
     {
         alert(OpenIDUrlMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to launch the verification of the login form after the enter key is pressed
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-11-10
 *
 * @param evt                   Object     Object which contains the event (here, the pressed key)
 * @param LoginMsgError         String     Error message if the login field is empty
 * @param PasswordMsgError      String     Error message if the password field is empty
 */
 function LoginEnterKey(evt, LoginMsgError, PasswordMsgError)
 {
     // Check if the browser is IE
     if (window.event)
     {
         // Enter key pressed?
         if (evt.keyCode == 13)
         {
             // Yes : we cancel the event
             evt.returnValue = false;

             // And we launch the verification of the login form (the tmp form)
             if (VerificationIndexPage(LoginMsgError, PasswordMsgError))
             {
                 // We submit the crypted form
                 document.forms[1].submit();
             }
         }
     }
     else
     {
         // Other browser : enter key pressed?
         if (evt.which == 13)
         {
             // And we launch the verification of the login form (the tmp form)
             if (VerificationIndexPage(LoginMsgError, PasswordMsgError))
             {
                 // We submit the crypted form
                 document.forms[1].submit();
             }
         }
     }
 }


/**
 * Function used to remove a waiting message when the page is loading
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-09-17
 *
 * @param objID                    String     ID of the HTML tag containing the dsplayed waiting message
 * @param SupportMemberStateID     Integer    ID of the support member state of the logged supporter [1..n]
 * @param ConcernedStateID         String     List of support member states ID concerned by the waiting
 *                                            message (x|y|z...)
 *
 * @return Boolean                 TRUE if the waiting message has been removed, FALSE otherwise
 */
 function WaitingPageLoadedManager(objID, SupportMemberStateID, ConcernedStateID)
 {
     if ((objID != '') && (SupportMemberStateID > 0) && (ConcernedStateID != ''))
     {
         var ArrayConcernedStateID = ListSepToArray(ConcernedStateID, "|");

         // The logged supporter is concerned ?
         if (In_Array(ArrayConcernedStateID, SupportMemberStateID))
         {
             // Delete the waiting message
             var objWaitingMsg = document.getElementById(objID);
             if (objWaitingMsg) {
                 objWaitingMsg.remove();

                 return true;
             }
         }
     }

     return false;
 }


/**
 * Function used to validate the user profil form
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-02-01
 *
 * @param LastnameMsgError      String    Error message if the lastname field is empty
 * @param FirstnameMsgError     String    Error message if the firstname field is empty
 * @param EmailMsgError         String    Error message if the e-mail field is empty
 *
 * @return Boolean                        TRUE if the user profil form is correctly entered, FALSE otherwise
 */
 function VerificationUserProfil(LastnameMsgError, FirstnameMsgError, EmailMsgError)
 {
     if (document.forms[0].sLastname.value == "")
     {
         alert(LastnameMsgError);
         return false;
     }
     else if (document.forms[0].sFirstname.value == "")
     {
         alert(FirstnameMsgError);
         return false;
     }
     else if (document.forms[0].sEmail.value == "")
     {
         alert(EmailMsgError);
         return false;
     }
     else if (isValideEmailAddress(document.forms[0].sEmail.value) == false)
     {
         alert(EmailMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate a new user profil form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-08-07
 *
 * @param LastnameMsgError          String     Error message if the lastname field is empty
 * @param FirstnameMsgError         String     Error message if the firstname field is empty
 * @param StateMsgError             String     Error message if the SupportMemberState field is empty
 * @param EmailMsgError             String     Error message if the e-mail field is empty
 * @param LoginMsgError             String     Error message if the login field is empty
 * @param PasswordMsgError          String     Error message if the password field is empty
 * @param DiffPasswordMsgError      String     Error message if the password and the password confirmation
 *                                             are different
 *
 * @return Boolean                  TRUE if the new user profil form is correctly entered, FALSE otherwise
 */
 function VerificationNewUserProfil(LastnameMsgError, FirstnameMsgError, StateMsgError, EmailMsgError, LoginMsgError, PasswordMsgError, DiffPasswordMsgError)
 {
     if (document.forms[0].sLastname.value == "")
     {
         alert(LastnameMsgError);
         return false;
     }
     else if (document.forms[0].sFirstname.value == "")
     {
         alert(FirstnameMsgError);
         return false;
     }
     else if (document.forms[0].lSupportMemberStateID.options[document.forms[0].lSupportMemberStateID.selectedIndex].value == 0)
     {
         alert(StateMsgError);
         return false;
     }
     else if (document.forms[0].sEmail.value == "")
     {
         alert(EmailMsgError);
         return false;
     }
     else if (isValideEmailAddress(document.forms[0].sEmail.value) == false)
     {
         alert(EmailMsgError);
         return false;
     }
     if (document.forms[0].sLogin.value == "")
     {
         alert(LoginMsgError);
         return false;
     }
     else if (document.forms[0].sPassword.value == "")
     {
         alert(PasswordMsgError);
         return false;
     }
     else if (document.forms[0].sPassword.value != document.forms[0].sConfirmPassword.value)
     {
         alert(DiffPasswordMsgError);
         return false;
     }
     else
     {
         // Hash MD5
         var Id = new String(document.forms[0].sLogin.value) ;
         var Pwd = new String(document.forms[0].sPassword.value);

         // We need to keep in clear login / password to send it after by e-mail to the user
         document.forms[0].hidLogin.value = Id.toLowerCase();
         document.forms[0].hidPassword.value = Pwd.toLowerCase();

         document.forms[0].sLogin.value = MD5(Id.toLowerCase());
         document.forms[0].sPassword.value = MD5(Pwd.toLowerCase());
         document.forms[0].sConfirmPassword.value = document.forms[0].sPassword.value;

         if (document.forms[0].sWebServiceKey.value != "")
         {
             document.forms[0].sWebServiceKey.value = MD5(document.forms[0].sWebServiceKey.value);
         }

         return true;
     }
 }


/**
 * Function used to validate the support member state form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param MandatoryMsgError       String    Error message if a mandatory field is empty or not valid
 *
 * @return Boolean                TRUE if the support member state form is correctly entered,
 *                                FALSE otherwise
 */
 function VerificationSupportMemberState(MandatoryMsgError)
 {
     if (document.forms[0].sStateName.value == "")
     {
         alert(MandatoryMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the family form
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2012-07-12 : sNbMembers can be = 0
 *     - 2016-05-31 : change the test of sMainEmail field
 *
 * @since 2012-01-16
 *
 * @param LastnameMsgError      String    Error message if the lastname field is empty
 * @param MainEmailMsgError     String    Error message if the main e-mail field is empty or not valid
 * @param SecondEmailMsgError   String    Error message if the second e-mail field is not valid
 * @param NbMembersMsgError     String    Error message if the number of members with or without power
 *                                        is wrong (empty, < 0)
 * @param TownMsgError          String    Error message if no selected town
 *
 * @return Boolean              TRUE if the family form is correctly entered, FALSE otherwise
 */
 function VerificationFamily(LastnameMsgError, MainEmailMsgError, SecondEmailMsgError, NbMembersMsgError, TownMsgError)
 {
     if ((document.forms[0].sLastname) && (document.forms[0].sLastname.value == ""))
     {
         alert(LastnameMsgError);
         return false;
     }
     else if (document.forms[0].lTownID.options[document.forms[0].lTownID.selectedIndex].value == 0)
     {
         alert(TownMsgError);
         return false;
     }
     else if ((document.forms[0].sMainEmail.value == "") || (isValideEmailAddress(document.forms[0].sMainEmail.value) == false))
     {
         alert(MainEmailMsgError);
         return false;
     }
     else if (document.forms[0].sSecondEmail.value != "")
     {
         if (isValideEmailAddress(document.forms[0].sSecondEmail.value) == false)
         {
             alert(SecondEmailMsgError);
             return false;
         }
     }
     else if ((document.forms[0].sNbMembers) && (isNaN(parseInt(document.forms[0].sNbMembers.value))))
     {
         alert(NbMembersMsgError);
         return false;
     }
     else if ((document.forms[0].sNbMembers) && (document.forms[0].sNbMembers.value < 0))
     {
         alert(NbMembersMsgError);
         return false;
     }
     else if ((document.forms[0].sNbPoweredMembers) && (isNaN(parseInt(document.forms[0].sNbPoweredMembers.value))))
     {
         alert(NbMembersMsgError);
         return false;
     }
     else if ((document.forms[0].sNbPoweredMembers) && (document.forms[0].sNbPoweredMembers.value < 0))
     {
         alert(NbMembersMsgError);
         return false;
     }
     else if ((document.forms[0].sNbMembers) && (document.forms[0].sNbPoweredMembers)
              && (document.forms[0].sNbMembers.value + document.forms[0].sNbPoweredMembers.value <= 0))
     {
         // The number of members (powered or not) must be > 0
         alert(NbMembersMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the child form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-24
 *
 * @param FirstnameMsgError      String    Error message if the firstname field is empty
 *
 * @return Boolean               TRUE if the child form is correctly entered, FALSE otherwise
 */
 function VerificationChild(FirstnameMsgError)
 {
     if (document.forms[0].sFirstname.value == "")
     {
         alert(FirstnameMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the suspension of a child form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-20
 *
 * @param DatesMsgError          String    Error message if the start date and end date fields are empty
 *                                         or wrong
 *
 * @return Boolean               TRUE if the suspension of a child form is correctly entered,
 *                               FALSE otherwise
 */
 function VerificationSuspension(DatesMsgError)
 {
     if (document.forms[0].startDate.value == "")
     {
         alert(DatesMsgError);
         return false;
     }
     else if (document.forms[0].endDate.value != "")
     {
         // We convert the start date to a Date object
         StartDateDMY = ListSepToArray(document.forms[0].startDate.value, "/");
         StartDate = new Date(StartDateDMY[2], StartDateDMY[1] - 1, StartDateDMY[0]);

         // We convert the end date to a Date object
         EndDateDMY = ListSepToArray(document.forms[0].endDate.value, "/");
         EndDate = new Date(EndDateDMY[2], EndDateDMY[1] - 1, EndDateDMY[0]);

         // Compute the number of days between the start date and the end date
         DeadlineDelay = (EndDate.getTime() - StartDate.getTime()) / 86400000;
         if (DeadlineDelay < 0)
         {
             alert(DatesMsgError);
             return false;
         }
     }

     return true;
 }


/**
 * Function used to validate the payment form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-27
 *
 * @param DateMsgError        String    Error message if the date field is empty
 * @param AmountMsgError      String    Error message if the amount field is empty
 * @param BankMsgError        String    Error message if the bank field is empty and
 *                                      the payment mode is "Check"
 * @param CheckNbMsgError     String    Error message if the check number field is empty and
 *                                      the payment mode is "Check"
 *
 * @return Boolean            TRUE if the payment form is correctly entered, FALSE otherwise
 */
 function VerificationPayment(DateMsgError, AmountMsgError, BankMsgError, CheckNbMsgError)
 {
     if (document.forms[0].paymentDate.value == "")
     {
         alert(DateMsgError);
         return false;
     }
     else if ((document.forms[0].fAmount.value == "") || (document.forms[0].fAmount.value <= 0) || (isNaN(parseFloat(document.forms[0].fAmount.value))))
     {
         alert(AmountMsgError);
         return false;
     }
     else
     {
         if (document.forms[0].lPaymentMode.options[document.forms[0].lPaymentMode.selectedIndex].value == 1)
         {
             // Mode = check
             if (document.forms[0].lBankID.options[document.forms[0].lBankID.selectedIndex].value == 0)
             {
                 alert(BankMsgError);
                 return false;
             }
             else if (document.forms[0].sCheckNb.value == "")
             {
                 alert(CheckNbMsgError);
                 return false;
             }
         }

         return true;
     }
 }


/**
 * Function used to validate the bank form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-28
 *
 * @param BankNameMsgError       String    Error message if the bank name field is empty
 *
 * @return Boolean               TRUE if the bank form is correctly entered, FALSE otherwise
 */
 function VerificationBank(BankNameMsgError)
 {
     if (document.forms[0].sName.value == "")
     {
         alert(BankNameMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the town form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-02
 *
 * @param TownNameMsgError       String    Error message if the town name field is empty
 * @param ZipCodeMsgError        String    Error message if the town name field is empty
 *
 * @return Boolean               TRUE if the town form is correctly entered, FALSE otherwise
 */
 function VerificationTown(TownNameMsgError, ZipCodeMsgError)
 {
     if (document.forms[0].sCode.value == "")
     {
         alert(ZipCodeMsgError);
         return false;
     }
     else if (document.forms[0].sName.value == "")
     {
         alert(TownNameMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the discount/increase form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-10-06
 *
 * @param AmountMsgError      String    Error message if the amount field is empty or = 0.00
 *
 * @return Boolean            TRUE if the discount/increase form is correctly entered, FALSE otherwise
 */
 function VerificationDiscountFamily(AmountMsgError)
 {
     if ((document.forms[0].fAmount.value == "") || (document.forms[0].fAmount.value == 0.0) || (isNaN(parseFloat(document.forms[0].fAmount.value))))
     {
         alert(AmountMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to say if a value is in an array
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-07
 *
 * @param Table                 Array     Array which contains values
 * @param Value                 mixed     Value searched in the array
 *
 * @return Boolean              TRUE if the value is in the array, FALSE otherwise
 */
 function In_Array(Table, Value)
 {
     var IsInArray = false;
     var i = 0;
     var NbElements = Table.length;
     while((i < NbElements) && (IsInArray == false))
     {
         if (Table[i] == Value)
         {
             IsInArray = true;
         }
         else
         {
             i++;
         }
     }

     return IsInArray;
 }


/**
 * Function used to transform a list of values, separeted by '#', in an array
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-21
 *
 * @param List                  String     String which contains values, separeted by #
 *
 * @return Array of values                 An array of values (strings, integer, etc.), an empty array otherwise
 */
 function ListToArray(List)
 {
     var ArrayResult = new Array();

     if (List != "")
     {
         var i = 0;
         var PosInit = 0;
         var Pos = List.indexOf("#", PosInit);
         while (Pos != -1)
         {
             // We extract the value
             ArrayResult[i] = List.substring(PosInit, Pos);
             PosInit = Pos + 1;
             i++;

             // We extract the next value
             Pos = List.indexOf("#", PosInit);
         }

         // We try to extract the last value
         var sLastValue = List.substring(PosInit, List.length);
         if (sLastValue != "")
         {
             ArrayResult[i] = sLastValue;
         }
     }

     return ArrayResult;
 }


/**
 * Function used to transform a list of values, separeted by a separator, in an array
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-02-18
 *
 * @param List                  String     String which contains values, separeted by a separator
 * @param Separator             String     String used as separator between elements of the list
 *
 * @return Array of values                 An array of values (strings, integer, etc.), an empty array otherwise
 */
 function ListSepToArray(List, Separator)
 {
     var ArrayResult = new Array();

     if (List != "")
     {
         var i = 0;
         var PosInit = 0;
         var Pos = List.indexOf(Separator, PosInit);
         while (Pos != -1)
         {
             // We extract the value
             ArrayResult[i] = List.substring(PosInit, Pos);
             PosInit = Pos + 1;
             i++;

             // We extract the next value
             Pos = List.indexOf(Separator, PosInit);
         }

         // We try to extract the last value
         var sLastValue = List.substring(PosInit, List.length);
         if (sLastValue != "")
         {
             ArrayResult[i] = sLastValue;
         }
     }

     return ArrayResult;
 }


/**
 * Function used to duplicate data of a web page
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2006-03-16
 *
 * @param Url   String       Url of the script used to duplicate data
 */
 function DuplicatePage(Url)
 {
     if (Url != "")
     {
         location = Url;
     }
 }


/**
 * Check if the value of the parameter is a valide e-mail address
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-06-29
 *
 * @param $Email          String     Value to ckeck if it's a valide e-mail address
 *
 * @return Boolean                   TRUE if the value is a valide e-mail address, FALSE otherwise
 */
 function isValideEmailAddress(Email)
 {
     var RegExpression = /^[a-z0-9\.\-_]{1,}@[a-z0-9\.\-_]{1,}\.[a-z]{2,4}$/;

     return RegExpression.test(Email);
 }


/**
 * Function used to print a web page
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2004-08-12 : taken into account that the "hidOnPrint" can be in another form than the first
 *
 * @since 2004-07-08
 */
 function PrintWebPage()
 {
     // We get the elements names of the first form
     var FieldsList = new Array();
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         if (document.forms[0].elements[i].name != "")
         {
             FieldsList[i] = document.forms[0].elements[i].name;
         }
     }

     // Check if the "hidOnPrint" field is in the first form
     if (In_Array(FieldsList, "hidOnPrint"))
     {
         // yes
         document.forms[0].hidOnPrint.value = 1;
         document.forms[0].submit();
     }
     else
     {
         // no
         document.forms[1].hidOnPrint.value = 1;
         document.forms[1].submit();
     }
 }


/**
 * Function used to print a web page with some features
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-09-12
 *
 * @param Feature       Integer      ID of the feature [1..n]
 */
 function PrintWebPageWithFeatures(Feature)
 {
     // We get the elements names of the first form
     var FieldsList = new Array();
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         if (document.forms[0].elements[i].name != "")
         {
             FieldsList[i] = document.forms[0].elements[i].name;
         }
     }

     // Check if the "hidOnPrint" field is in the first form
     if (In_Array(FieldsList, "hidOnPrint"))
     {
         // yes
         document.forms[0].hidOnPrint.value = Feature;
         document.forms[0].submit();
     }
     else
     {
         // no
         document.forms[1].hidOnPrint.value = Feature;
         document.forms[1].submit();
     }
 }


/**
 * Function used to export a web page
 *
 * @author STNA/7SQ
 * @version 3.0
 *     - 2004-08-13 : taken into account that the "hidOnExport" can be in another form than the first
 *     - 2004-08-13 : taken into account that the "hidOnExport" can have a suffix and
 *                    can be in another form than the first
 *
 * @since 2004-07-09
 *
 * @param Filename      String       Filename to store the export result
 */
 function ExportWebPage(Filename)
 {
     if (Filename != "")
     {
         if (arguments.length == 1)
         {
             // We get the elements names of the first form
             var FieldsList = new Array();
             for(i = 0 ; i < document.forms[0].length ; i++)
             {
                 if (document.forms[0].elements[i].name != "")
                 {
                     FieldsList[i] = document.forms[0].elements[i].name;
                 }
             }

             // Check if the "hidOnExport" field is in the first form
             if (In_Array(FieldsList, "hidOnExport"))
             {
                 // yes
                 document.forms[0].hidOnExport.value = 1;
                 document.forms[0].hidExportFilename.value = Filename;
                 document.forms[0].submit();
             }
             else
             {
                 // no
                 document.forms[1].hidOnExport.value = 1;
                 document.forms[1].hidExportFilename.value = Filename;
                 document.forms[1].submit();
             }
         }
         else
         {
             // The function has more than 1 argument (the filename)
             switch(arguments.length)
             {
                 case 2:
                 default:
                     // There are a suffix to the name of the field used to export
                     sSuffix = arguments[1];

                     // We search the right number of form
                     iNumForm = 0;
                     bFound = false;
                     while((bFound == false) && (iNumForm < document.forms.length))
                     {
                         if (document.forms[iNumForm].elements["hidOnExport" + sSuffix])
                         {
                             bFound = true;
                         }
                         else
                         {
                             iNumForm++;
                         }
                     }

                     document.forms[iNumForm].elements["hidOnExport" + sSuffix].value = 1;
                     document.forms[iNumForm].elements["hidExportFilename" + sSuffix].value = Filename;
                     document.forms[iNumForm].submit();
                     break;
             }
         }
     }
 }


/**
 * Function used to backup the value of the prepared request selected in the dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-08-13
 *
 * @param Value   Integer       ID of the prepared request selected
 */
 function onChangePreparedRequest(Value)
 {
     document.forms[0].hidPreparedRequestID.value = Value;
 }


/**
 * Function used to validate the form allow a supporter to update his login and his password.
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2005-04-05 : tak en into account the confirmation of the password
 *
 * @since 2004-08-20
 *
 * @param LoginMsgError             String     Error message if the login field is empty
 * @param PasswordMsgError          String     Error message if the password field is empty
 * @param DiffPasswordMsgError      String     Error message if the password and the password confirmation
 *                                             are different
 *
 * @return Boolean                             TRUE if the form is correctly entered, FALSE otherwise
 */
 function VerificationUpdateLoginPwd(LoginMsgError, PasswordMsgError, DiffPasswordMsgError)
 {
    if (document.FormLoginPwdTmp.sLogin.value == "")
    {
        alert(LoginMsgError);
        return false;
    }
    else if (document.FormLoginPwdTmp.sPassword.value == "")
    {
        alert(PasswordMsgError);
        return false;
    }
    else if (document.FormLoginPwdTmp.sPassword.value != document.FormLoginPwdTmp.sConfirmPassword.value)
    {
        alert(DiffPasswordMsgError);
        return false;
    }
    else
    {
        // Crypt
        var Id = new String(document.FormLoginPwdTmp.sLogin.value) ;
        var Pwd = new String(document.FormLoginPwdTmp.sPassword.value);
        document.FormUpdateLoginPwd.hidEncLogin.value = MD5(Id.toLowerCase());
        document.FormUpdateLoginPwd.hidEncPassword.value = MD5(Pwd.toLowerCase());
        document.FormUpdateLoginPwd.hidEncConfirmPassword.value = document.FormUpdateLoginPwd.hidEncPassword.value;
        return true;
    }
 }


/**
 * Function used to refresh the form to add a payment for a bill of a family
 * selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-03-08
 *
 * @param Value   Integer       If of the selected family [1..n]
 */
 function onChangeSelectedFamily(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the form to generate monthly bills with the year and the month
 * selected in the dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2012-02-21
 *
 * @param Value   Integer       Year and month selected [1..12]
 */
 function onChangeSelectedYearMonth(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the planning with the selected view in the dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2012-01-28
 *
 * @param Value   Integer       Selected view [1..n]
 */
 function onChangeSelectedPlanningView(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the planning with the month selected in the dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2012-01-28
 *
 * @param Value   Integer       Month selected [1..12]
 */
 function onChangeSelectedMonth(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the planning with the week selected in the dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2012-01-28
 *
 * @param Value   Integer       Week selected [1..53]
 */
 function onChangeSelectedWeek(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the planning with the year selected in the dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2012-01-28
 *
 * @param Value   Integer       Year selected [2003..2100]
 */
 function onChangeSelectedYear(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to check or uncheck all children of a class for a day.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-30
 *
 * @param Class           Integer              ID of the concerned class [0..n]
 * @param NumDay          Integer              Number of the day in the month [0..n]
 * @param DateDay         Date                 Date of the day (yyyy-mm-dd)
 */
 function checkClassCanteenPlanning(Class, NumDay, DateDay)
 {
     var objClassChk = document.getElementById("chkCanteenRegitrationClass_" + Class + "_" + NumDay);
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkCanteenRegitration[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(DateDay + "#" + Class + "#", 0) != -1)
             {
                 document.forms[0].elements[i].checked = objClassChk.checked;

                 // We change the style
                 if (objClassChk.checked)
                 {
                     // Registre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormation";
                 }
                 else
                 {
                     // Unregistre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningWorkingDay";
                 }
             }
         }
     }
 }


/**
 * Function used to set the cell style of a day for a child in the canteen planning.
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2015-10-06 : taken into account the WithoutPork parameter
 *     - 2017-11-09 : taken into account "packed lunch" value for WithoutPork
 *
 * @since 2012-01-31
 *
 * @param Class           Integer              ID of the concerned class [0..n]
 * @param NumDay          Integer              Number of the day in the month [0..n]
 * @param DateDay         Date                 Date of the day (yyyy-mm-dd)
 * @param ChildID         Integer              ID of the child [1..n]
 * @param WithoutPork     Integer              Child want meal with or without pork
 */
 function checkChildDayCanteenPlanning(Class, NumDay, DateDay, ChildID, WithoutPork)
 {
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkCanteenRegitration[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(DateDay + "#" + Class + "#" + ChildID + "#", 0) != -1)
             {
                 // We change the style
                 if (document.forms[0].elements[i].checked)
                 {
                     // Registre the child
                     switch(WithoutPork)
                     {
                         case '1':
                             // Without pork
                             document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormationNoPork";
                             break;

                         case '2':
                             // Packed lunch
                             document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormationPackedLunch";
                             break;

                         case '0':
                         default:
                             document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormation";
                             break;
                     }
                 }
                 else
                 {
                     // Unregistre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningWorkingDay";
                 }
             }
         }
     }
 }


/**
 * Function used to check or uncheck all children of a class for a day (AM only).
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-15
 *
 * @param Class           Integer              ID of the concerned class [0..n]
 * @param NumDay          Integer              Number of the day in the month [0..n]
 * @param DateDay         Date                 Date of the day (yyyy-mm-dd)
 */
 function checkClassNurseryPlanningAM(Class, NumDay, DateDay)
 {
     var objClassChk = document.getElementById("chkNurseryRegitrationAMClass_" + Class + "_" + NumDay);
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkNurseryRegitrationAM[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(DateDay + "#" + Class + "#", 0) != -1)
             {
                 document.forms[0].elements[i].checked = objClassChk.checked;

                 // We change the style
                 if (objClassChk.checked)
                 {
                     // Registre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormationAM";
                 }
                 else
                 {
                     // Unregistre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningWorkingDayAM";
                 }
             }
         }
     }
 }


/**
 * Function used to check or uncheck all children of a class for a day (PM only).
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-15
 *
 * @param Class           Integer              ID of the concerned class [0..n]
 * @param NumDay          Integer              Number of the day in the month [0..n]
 * @param DateDay         Date                 Date of the day (yyyy-mm-dd)
 */
 function checkClassNurseryPlanningPM(Class, NumDay, DateDay)
 {
     var objClassChk = document.getElementById("chkNurseryRegitrationPMClass_" + Class + "_" + NumDay);
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkNurseryRegitrationPM[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(DateDay + "#" + Class + "#", 0) != -1)
             {
                 document.forms[0].elements[i].checked = objClassChk.checked;

                 // We change the style
                 if (objClassChk.checked)
                 {
                     // Registre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormationPM";
                 }
                 else
                 {
                     // Unregistre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningWorkingDayPM";
                 }
             }
         }
     }
 }


/**
 * Function used to set the cell style of a day (AM only) for a child in the nursery planning.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-15
 *
 * @param Class           Integer              ID of the concerned class [0..n]
 * @param NumDay          Integer              Number of the day in the month [0..n]
 * @param DateDay         Date                 Date of the day (yyyy-mm-dd)
 * @param ChildID         Integer              ID of the child [1..n]
 */
 function checkChildDayNurseryPlanningAM(Class, NumDay, DateDay, ChildID)
 {
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkNurseryRegitrationAM[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(DateDay + "#" + Class + "#" + ChildID + "#", 0) != -1)
             {
                 // We change the style
                 if (document.forms[0].elements[i].checked)
                 {
                     // Registre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormationAM";
                 }
                 else
                 {
                     // Unregistre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningWorkingDayAM";
                 }
             }
         }
     }
 }


/**
 * Function used to set the cell style of a day (PM only) for a child in the nursery planning.
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-15
 *
 * @param Class           Integer              ID of the concerned class [0..n]
 * @param NumDay          Integer              Number of the day in the month [0..n]
 * @param DateDay         Date                 Date of the day (yyyy-mm-dd)
 * @param ChildID         Integer              ID of the child [1..n]
 */
 function checkChildDayNurseryPlanningPM(Class, NumDay, DateDay, ChildID)
 {
     for(i = 0 ; i < document.forms[0].length ; i++)
     {
         // Check if it's a field of the class
         if (document.forms[0].elements[i].name == "chkNurseryRegitrationPM[]")
         {
             // Check if it's a field of the day
             if (document.forms[0].elements[i].value.indexOf(DateDay + "#" + Class + "#" + ChildID + "#", 0) != -1)
             {
                 // We change the style
                 if (document.forms[0].elements[i].checked)
                 {
                     // Registre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningSupporterFormationPM";
                 }
                 else
                 {
                     // Unregistre the child
                     document.forms[0].elements[i].parentNode.className = "PlanningWorkingDayPM";
                 }
             }
         }
     }
 }


/**
 * Function used to refresh the nursery delay form with the child selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-02-03
 *
 * @param Value   Integer       Child ID selected [1..n]
 */
 function onChangeSelectedChild(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to validate the nursery registration delay form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-02-03
 *
 * @param ChildMsgError                String    Error message if the child field is empty
 * @param NurseryMsgError              String    Error message if the nursery registration field is empty
 *
 * @return Boolean                     TRUE if the nursery registration delay form is correctly entered,
 *                                     FALSE otherwise
 */
 function VerificationNurseryDelay(ChildMsgError, NurseryMsgError)
 {
     if (document.forms[0].lChildID.options[document.forms[0].lChildID.selectedIndex].value == 0)
     {
         alert(ChildMsgError);
         return false;
     }
     else if (document.forms[0].lNurseryRegistrationID.options[document.forms[0].lNurseryRegistrationID.selectedIndex].value == 0)
     {
         alert(NurseryMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to show the options of the repeat function.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-10-05
 */
 function DisplayPlanningRepeatOptions()
 {
     var objArea = document.getElementById('RepeatOptions');
     if (document.forms[0].chkRepeat.checked)
     {
         // We show the options
         objArea.style.display = 'block';
     }
     else
     {
         // We hide the options
         objArea.style.display = 'none';
     }
 }


/**
 * Function used to refresh the canteen week synthesis with the year and week selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-02
 *
 * @param Value   Integer       Year and week selected
 */
 function onChangeCanteenWeekSynthesisWeek(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the canteen day synthesis with the day selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-03
 *
 * @param Value   Integer       Selected day
 */
 function onChangeCanteenDaySynthesisDay(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the canteen day synthesis with the display type selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-10-17
 *
 * @param Value   Integer       Selected day
 */
 function onChangeCanteenDaySynthesisDisplayType(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the snack planning with the school year selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-16
 *
 * @param Value   Integer       School year selected
 */
 function onChangeSnackPlanningYear(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to validate the swap snack planning form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param MandatoryMsgError       String    Error message if a mandatory field is empty or not valid
 *
 * @return Boolean                TRUE if the swap snack planning form is correctly entered,
 *                                FALSE otherwise
 */
 function VerificationSwapSnackPlanning(MandatoryMsgError)
 {
     var objSelect = document.getElementById('lFirstSnackRegistrationID');
     var FirstOptGroup = objSelect.options[objSelect.selectedIndex].parentNode.label;
     var iFirstID = objSelect.options[objSelect.selectedIndex].value;

     objSelect = document.getElementById('lSecondSnackRegistrationID');
     var SecondOptGroup = objSelect.options[objSelect.selectedIndex].parentNode.label;
     var iSecondID = objSelect.options[objSelect.selectedIndex].value;

     // Selected items must be in the same classroom, but different families
     if ((iFirstID == 0) || (iSecondID == 0) || (iFirstID == iSecondID) || (FirstOptGroup != SecondOptGroup))
     {
         alert(MandatoryMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to refresh the laundry planning with the school year selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-06-16
 *
 * @param Value   Integer       School year selected
 */
 function onChangeLaundryPlanningYear(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the nursery day synthesis with the day selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-20
 *
 * @param Value   Integer       Selected day
 */
 function onChangeNurseryDaySynthesisDay(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to refresh the nursery day synthesis with the display type selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-20
 *
 * @param Value   Integer       Selected day
 */
 function onChangeNurseryDaySynthesisDisplayType(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to validate the swap laundry planning form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param MandatoryMsgError       String    Error message if a mandatory field is empty or not valid
 *
 * @return Boolean                TRUE if the swap laundry planning form is correctly entered,
 *                                FALSE otherwise
 */
 function VerificationSwapLaundryPlanning(MandatoryMsgError)
 {
     var iFirstID = document.forms[0].lFirstLaundryRegistrationID.options[document.forms[0].lFirstLaundryRegistrationID.selectedIndex].value;
     var iSecondID = document.forms[0].lSecondLaundryRegistrationID.options[document.forms[0].lSecondLaundryRegistrationID.selectedIndex].value;
     if ((iFirstID == 0) || (iSecondID == 0) || (iFirstID == iSecondID))
     {
         alert(MandatoryMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to refresh the exit permissions list of the day with the day selected in the dropdown list
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-07-10
 *
 * @param Value   Integer       Selected day
 */
 function onChangeExitPermissionsDay(Value)
 {
     document.forms[0].submit();
 }


/**
 * Function used to validate the "Document approval" form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-05-027
 *
 * @param DocumentNameError          String               Error message if the document name field is empty
 * @param FilenameError              String               Error message if the no file is selected
 * @param AllowedExtensions          Array of Strings     Contains the files extentions allowed to be uploaded
 * @param ExtensionsMsgError         String               Error message if the filename has a not allowed extension
 * @param WaitingMsg                 String               Waiting message displayed during the upload
 *
 * @return Boolean                   TRUE if the "Document approval" form is correctly entered, FALSE otherwise
 */
 function VerificationDocumentApproval(DocumentNameError, FilenameError, AllowedExtensions, ExtensionsMsgError, WaitingMsg)
 {
     if ((document.getElementById('sDocumentApprovalName')) && (document.getElementById('sDocumentApprovalName').value == ""))
     {
         alert(DocumentNameError);
         return false;
     }
     else
     {
         if ((document.getElementById('fFilename')) && (document.getElementById('fFilename').value != ""))
         {
             if (AllowedExtensions == "")
             {
                 // All extensions are allowed
                 // Display the waiting bar
                 showWait(WaitingMsg);

                 return true;
             }
             else
             {
                 // The filename must have an allowed extension
                 // We create an array which will contain the allowed extensions strings
                 var AllowedExtensionsList = ListToArray(AllowedExtensions);

                 // We extract the extension of the filename
                 var FileExtension = new String(document.forms[0].fFilename.value);
                 var iPosExtension = FileExtension.lastIndexOf(".");

                 if (iPosExtension == -1)
                 {
                     // No extension
                     FileExtension = "";
                 }
                 else
                 {
                     // The file has got an extension
                     FileExtension = FileExtension.slice(iPosExtension + 1, FileExtension.length);
                     FileExtension = FileExtension.toLowerCase();
                 }

                 // We search the file extension in the allowed extensions array
                 var ExtensionFound = In_Array(AllowedExtensionsList, FileExtension);
                 if (ExtensionFound == false)
                 {
                     // The file extension isn't allowed
                     alert(ExtensionsMsgError);
                     return false ;
                 }

                 // Display the waiting bar
                 showWait(WaitingMsg);

                 return true;
             }
         }
         else
         {
             if ((document.getElementById('hidDocumentApprovalFile')) && (document.getElementById('hidDocumentApprovalFile').value == ""))
             {
                 // Error : no selected file and no old file !
                 alert(FilenameError);
                 return false;
             }
             else
             {
                 return true;
             }
         }
     }
 }


/**
 * Function used to change the content of the "lPerioType" SELECT tag in relation with
 * the selected value of the "lRepeatType" dropdown list
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-10-05
 *
 * @param Value           Integer              ID of the repeat type selected
 * @param RepeatTypes     Array of Strings     List of repeat types ID [1..n]
 * @param PeriodTypes     Array of Strings     List of repeat period values [1..n]
 * @param PeriodNames     Array of Strings     List of repeat period names
 */
 function onChangePlanningRepeatType(Value, RepeatTypes, PeriodTypes, PeriodNames)
 {
     // For Netscape 4
     Value = document.forms[0].elements["lRepeatType"].options[document.forms[0].elements["lRepeatType"].selectedIndex].value;

     // Manage the SELECT tag to select a period in relation with the repeat mode
     if ((RepeatTypes != "") && (PeriodTypes != "") && (PeriodNames != ""))
     {
         // We create an array which will contain the repeat types and periods
         var RepeatTypesList = ListToArray(RepeatTypes);
         var PeriodTypesList = ListToArray(PeriodTypes);
         var PeriodNamesList = ListToArray(PeriodNames);

         var ListSize = RepeatTypesList.length;

         // We check that the 3 arrays have the same size
         if ((ListSize == PeriodTypesList.length) && (ListSize == PeriodNamesList.length))
         {
             // We reset the content of the SELECT tag
             var Pos = 0;
             document.forms[0].elements["lRepeatPeriod"].options.length = 0;
             for(i = 0 ; i < ListSize ; i++)
             {
                 if (RepeatTypesList[i] == Value)
                 {
                     // This period is allowed to be displayed for the repeat mode selected
                     // It is added in the SELECT tag
                     var c = new Option(PeriodNamesList[i], PeriodTypesList[i]);
                     document.forms[0].elements["lRepeatPeriod"].options[Pos] = c;
                     Pos++;
                 }
             }
         }
     }
 }


/**
 * Open a popup window with a given size
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-10-21
 *
 * @param $Url            String     Url of the web page to display in the popup window
 * @param $WindowName     String     Name of the popup window
 * @param $Width          Integer    Width of the popup window
 * @param $Height         Integer    Height of the popup window
 */
 function openWindow(Url, WindowName, Width, Height)
 {
     if ((Url != "") && (Width > 0) && (Height > 0))
     {
         // Display the popup window in the right of the screen, and in the middle of the height screen
         var Top = (screen.height - Height) / 2;
         var Left = screen.width - (Width + 30);
         window.open(Url, WindowName, "top=" + Top + ", left=" + Left + ", directories=no, location=no, menubar=no, resizable=yes, scrollbars=yes, toolbar=no, width=" + Width + ", height=" + Height);
     }
 }


/**
 * Copy the e-mail address in the "sUsersInCopy" field
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2007-10-10 : the separator between 2 e-mail adresses is now ", "
 *     - 2009-11-02 : patch the problem with comments
 *
 * @since 2005-10-21
 *
 * @param $Email          String     E-mail to put in copy
 */
 function SelectEmailInCopy(Email)
 {
     var iFormInCopy = 0;
     var iFormSendTo = 0;

     if (opener.document.forms[0].sUsersInCopy)
     {
         iFormInCopy = 0;
     }
     else if (opener.document.forms[1].sUsersInCopy)
     {
         iFormInCopy = 1;
     }
     else if (opener.document.forms[0].sSendTo)
     {
         iFormSendTo = 0;
     }
     else if (opener.document.forms[1].sSendTo)
     {
         iFormSendTo = 1;
     }

     if (opener.document.forms[iFormInCopy].sUsersInCopy)
     {
         if (opener.document.forms[iFormInCopy].sUsersInCopy.value == "")
         {
             opener.document.forms[iFormInCopy].sUsersInCopy.value = Email;
         }
         else
         {
             opener.document.forms[iFormInCopy].sUsersInCopy.value += ", " + Email;
         }
     }
     else if (opener.document.forms[iFormSendTo].sSendTo)
     {
         if (opener.document.forms[iFormSendTo].sSendTo.value == "")
         {
             opener.document.forms[iFormSendTo].sSendTo.value = Email;
         }
         else
         {
             opener.document.forms[iFormSendTo].sSendTo.value += ", " + Email;
         }
     }
 }


/**
 * Function used to show or hide a XHTML element thanks to its ID
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-06-19
 *
 * @param String $idElt         ID of the XHTML element to show or hide
 */
 function showHide(idElt)
 {
     var objEld = document.getElementById(idElt);

     if (objEld.style.display == 'none') {
         // We show the element
         objEld.style.display = 'block';
     } else {
         // We hide the element
         objEld.style.display = 'none';
     }
 }


/**
 * Function used to delete a date of a given calendar
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2006-01-31
 *
 * @param CalendarName      String        Name of the calendar to reset
 */
 function DeleteCalendarDate(CalendarName)
 {
     if (CalendarName != "")
     {
         document.forms[0].elements[CalendarName].value = "";
     }
 }


/**
 * Function used to backup a value of a form field and reload the form
 * after the change
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2010-03-22
 *
 * @param oField         Object        Form field concerned
 * @param Action         String        Url to call to reload the form
 * @param BackupField    String        Form field used to backup the value (hidden field)
 */
 function onChangeFormFieldAndReload(oField, Action, BackupField)
 {
     // Get the form which contains the field
     var iFormIndex = -1;
     for(var f = 0; f < document.forms.length; f++)
     {
         if (document.forms[f].elements[oField.name])
         {
             iFormIndex = f;
             break;
         }
     }

     if (iFormIndex != -1)
     {
         if (BackupField != '')
         {
             document.getElementById(BackupField).value = oField.value;
         }

         document.forms[iFormIndex].action = Action;
         document.forms[iFormIndex].submit();
     }
 }


/**
 * Function used to validate the event form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-05
 *
 * @param TypeMsgError                 String    Error message if the event type field is empty
 * @param TitleMsgError                String    Error message if the title field is empty or not valid
 * @param TownMsgError                 String    Error message if no selected town
 * @param StartDateMsgError            String    Error message if the start date field is empty or not valid
 * @param WrongDatesMsgError           String    Error message if the start date and end dates aren't valid
 * @param NbMaxParticipantsMsgError    String    Error message if the number of participants is wrong (empty, < 0)
 * @param RegistrationDelayMsgError    String    Error message if the closing registration delay is wrong (empty, < 0)
 * @param DescriptionMsgError          String    Error message if the description field is empty or not valid
 *
 * @return Boolean                     TRUE if the event form is correctly entered, FALSE otherwise
 */
 function VerificationEvent(TypeMsgError, TitleMsgError, TownMsgError, StartDateMsgError, WrongDatesMsgError, NbMaxParticipantsMsgError, RegistrationDelayMsgError, DescriptionMsgError)
 {
     if (document.forms[0].lEventTypeID.options[document.forms[0].lEventTypeID.selectedIndex].value == 0)
     {
         alert(TypeMsgError);
         return false;
     }
     else if (document.forms[0].sTitle.value == "")
     {
         alert(TitleMsgError);
         return false;
     }
     else if (document.forms[0].lTownID.options[document.forms[0].lTownID.selectedIndex].value == 0)
     {
         alert(TownMsgError);
         return false;
     }
     else if (document.forms[0].startDate.value == "")
     {
         alert(StartDateMsgError);
         return false;
     }
     else
     {
         // We convert the start date to a Date object
         StartDateDMY = ListSepToArray(document.forms[0].startDate.value, "/");
         StartDate = new Date(StartDateDMY[2], StartDateDMY[1] - 1, StartDateDMY[0]);

         // We convert the end date to a Date object
         EndDateDMY = ListSepToArray(document.forms[0].endDate.value, "/");
         EndDate = new Date(EndDateDMY[2], EndDateDMY[1] - 1, EndDateDMY[0]);

         // Compute the number of days between the start date and the end date
         DeadlineDelay = (EndDate.getTime() - StartDate.getTime()) / 86400000;
         if (DeadlineDelay < 0)
         {
             alert(WrongDatesMsgError);
             return false;
         }
     }

     if (isNaN(parseInt(document.forms[0].sNbMaxParticipants.value)))
     {
         alert(NbMaxParticipantsMsgError);
         return false;
     }
     else if (document.forms[0].sNbMaxParticipants.value < 0)
     {
         alert(NbMaxParticipantsMsgError);
         return false;
     }
     else if (isNaN(parseInt(document.forms[0].sRegistrationDelay.value)))
     {
         alert(RegistrationDelayMsgError);
         return false;
     }
     else if (document.forms[0].sRegistrationDelay.value < 0)
     {
         alert(RegistrationDelayMsgError);
         return false;
     }
     else if ((document.forms[0].registrationClosingDate) && (document.forms[0].registrationClosingDate.value == ''))
     {
         // A date is used to enter the registration delay
         alert(RegistrationDelayMsgError);
         return false;
     }
     else if (document.forms[0].sDescription.value == "")
     {
         alert(DescriptionMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the event registration form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-04-19
 *
 * @param FamilyMsgError               String    Error message if the family field is empty
 *
 * @return Boolean                     TRUE if the event registration form is correctly entered, FALSE otherwise
 */
 function VerificationEventRegistration(FamilyMsgError)
 {
     if (document.forms[0].lFamilyID.options[document.forms[0].lFamilyID.selectedIndex].value == 0)
     {
         alert(FamilyMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the event swapped registration form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-05-23
 *
 * @param TypeMsgError                 String    Error message if the family field is empty
 *
 * @return Boolean                     TRUE if the event swapped registration form is correctly entered,
 *                                     FALSE otherwise
 */
 function VerificationSwapEventRegistration(RequestorFamilyMsgError, RequestorEventMsgError, AcceptorFamilyMsgError, AcceptorEventMsgError)
 {
     if (document.forms[0].lRequestorFamilyID.options[document.forms[0].lRequestorFamilyID.selectedIndex].value == 0)
     {
         alert(RequestorFamilyMsgError);
         return false;
     }
     else if ((document.forms[0].lRequestorEventID)
              && (document.forms[0].lRequestorEventID.options[document.forms[0].lRequestorEventID.selectedIndex].value == 0))
     {
         alert(RequestorEventMsgError);
         return false;
     }
     else if (document.forms[0].lAcceptorFamilyID.options[document.forms[0].lAcceptorFamilyID.selectedIndex].value == 0)
     {
         alert(AcceptorFamilyMsgError);
         return false;
     }
     else if (document.forms[0].lAcceptorEventID.options[document.forms[0].lAcceptorEventID.selectedIndex].value == 0)
     {
         alert(AcceptorEventMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the event type form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-27
 *
 * @param MandatoryMsgError       String    Error message if a mandatory field is empty or not valid
 *
 * @return Boolean                TRUE if the event type form is correctly entered, FALSE otherwise
 */
 function VerificationEventType(MandatoryMsgError)
 {
     if ((document.forms[0].sEventTypeName.value == "")
        || (document.forms[0].lEventTypeCategory.options[document.forms[0].lEventTypeCategory.selectedIndex].value == -1))
     {
         alert(MandatoryMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the workgroup form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-12
 *
 * @param NameMsgError      String    Error message if the name of the workgroup field is empty
 * @param EmailMsgError     String    Error message if the e-mail of the workgroup field is not valid
 *
 * @return Boolean          TRUE if the workgroup form is correctly entered, FALSE otherwise
 */
 function VerificationWorkGroup(NameMsgError, EmailMsgError)
 {
     if ((document.forms[0].sWorkGroupName) && (document.forms[0].sWorkGroupName.value == ""))
     {
         alert(NameMsgError);
         return false;
     }
     else if (document.forms[0].sWorkGroupEmail.value != "")
     {
         if (isValideEmailAddress(document.forms[0].sWorkGroupEmail.value) == false)
         {
             alert(EmailMsgError);
             return false;
         }
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the workgroup registration form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-10-19
 *
 * @param LastnameError                String    Error message if the lastname field is empty
 * @param FirstnameError               String    Error message if the firstname field is empty
 * @param EmailError                   String    Error message if the Email field is empty
 *
 * @return Boolean                     TRUE if the workgroup registration form is correctly entered, FALSE otherwise
 */
 function VerificationWorkGroupRegistration(LastnameError, FirstnameError, EmailError)
 {
     if ((document.forms[0].sLastname) && (document.forms[0].sLastname.value == ""))
     {
         alert(LastnameError);
         return false;
     }
     else if ((document.forms[0].sFirstname) && (document.forms[0].sFirstname.value == ""))
     {
         alert(FirstnameError);
         return false;
     }
     else if ((document.forms[0].sEmail) && (document.forms[0].sEmail.value == ""))
     {
         alert(EmailError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the alias form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-07
 *
 * @param NameMsgError            String    Error message if the name of the alias field is empty
 * @param MailingListMsgError     String    Error message if the mailing-list of the alias field is not valid
 *
 * @return Boolean                TRUE if the alias form is correctly entered, FALSE otherwise
 */
 function VerificationAlias(NameMsgError, MailingListMsgError)
 {
     if ((document.forms[0].sAliasName) && (document.forms[0].sAliasName.value == ""))
     {
         alert(NameMsgError);
         return false;
     }
     else if ((document.forms[0].sAliasMailingList) && (document.forms[0].sAliasMailingList.value == ""))
     {
         alert(MailingListMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Copy in the objRecipientsList <div> the name of the selected recipient with the ID
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-14
 *
 * @param $ID          String     ID of the selected name to put in recipient of the message
 * @param $Name        String     Selected name to put in recipient of the message
 * @param $Lang        String     Langage of the application (en, fr, oc...)
 */
 function SelectRecipientName(ID, Name, Lang)
 {
     var objRecipientsList = opener.document.getElementById('objRecipientsList');

     if (objRecipientsList) {
         // Add an item in this <div>
         var objItemAdded = document.createElement('div');
         objItemAdded.setAttribute('id', 'L' + ID);
         objItemAdded.className = 'AutoCompletionSendMessageItem';
         objItemAdded.innerHTML = Name;

         var sTip = '';
         switch(Lang) {
             case 'oc':
                 sTip = "Clicatz aici per suprimir la familha/alias " + Name + ".";
                 break;

             case 'fr':
                 sTip = "Cliquez ici pour supprimer la destinataire " + Name + ".";
                 break;

             case 'en':
             default:
                 sTip = "Click here to delete the " + Name + " recipient.";
                 break;
         }

         objItemAdded.setAttribute('title', sTip);

         if (window.attachEvent) {
             objItemAdded.attachEvent("onclick", VerificationSendMessageItemOnClick);            // IE
         } else {
             objItemAdded.addEventListener("click", VerificationSendMessageItemOnClick, false);  // FF
         }

         objRecipientsList.appendChild(objItemAdded);

         // Delete value in the input type text
         opener.document.forms[0].sRecipients.value = '';
     }
 }


/**
 * Delete the selected item of the objRecipientsList <div>
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-14
 *
 * @param $evt         Object     Clicked item to delete
 */
 function VerificationSendMessageItemOnClick(evt)
 {
     var objItem = evt.target || evt.srcElement;

     // Delete the item
     objItem.parentNode.removeChild(objItem);
 }


/**
 * Function used to validate the "Send message" form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-02
 *
 * @param AuthorError                String               Error message if the author field is empty
 * @param RecipientsError            String               Error message if the recipients field is empty
 * @param SubjectError               String               Error message if the subject field is empty
 * @param MessageError               String               Error message if the message field is empty
 * @param AllowedExtensions          Array of Strings     Contains the files extentions allowed to be uploaded
 * @param ExtensionsMsgError         String               Error message if the filename has a not allowed extension
 * @param WaitingMsg                 String               Waiting message displayed during the upload
 *
 * @return Boolean                   TRUE if the "Send message" form is correctly entered, FALSE otherwise
 */
 function VerificationSendMessage(AuthorError, RecipientsError, SubjectError, MessageError, AllowedExtensions, ExtensionsMsgError, WaitingMsg)
 {
     // Get selected recipients
     var sRecipientsList = '';
     var objRecipientsList = document.getElementById('objRecipientsList').getElementsByTagName('div');

     for(var i=0; i < objRecipientsList.length; i++)
     {
         if (sRecipientsList != '')
         {
             sRecipientsList = sRecipientsList + ";";
         }

         sRecipientsList = sRecipientsList + objRecipientsList[i].id.substring(1);
     }

     // We save recipients in a hidden field
     document.forms[0].hidMessageRecipients.value = sRecipientsList;

     if ((document.forms[0].sAuthor) && (document.forms[0].sAuthor.value == ""))
     {
         alert(AuthorError);
         return false;
     }
     else if (sRecipientsList == "")
     {
         alert(RecipientsError);
         return false;
     }
     else if ((document.forms[0].sSubject) && (document.forms[0].sSubject.value == ""))
     {
         alert(SubjectError);
         return false;
     }
     else if ((document.forms[0].sMessage) && (document.forms[0].sMessage.value == ""))
     {
         alert(MessageError);
         return false;
     }
     else
     {
         if (document.forms[0].fFilename.value != "")
         {
             if (AllowedExtensions == "")
             {
                 // All extensions are allowed
                 // Display the waiting bar
                 showWait(WaitingMsg);

                 return true;
             }
             else
             {
                 // The filename must have an allowed extension
                 // We create an array which will contain the allowed extensions strings
                 var AllowedExtensionsList = ListToArray(AllowedExtensions);

                 // We extract the extension of the filename
                 var FileExtension = new String(document.forms[0].fFilename.value);
                 var iPosExtension = FileExtension.lastIndexOf(".");

                 if (iPosExtension == -1)
                 {
                     // No extension
                     FileExtension = "";
                 }
                 else
                 {
                     // The file has got an extension
                     FileExtension = FileExtension.slice(iPosExtension + 1, FileExtension.length);
                     FileExtension = FileExtension.toLowerCase();
                 }

                 // We search the file extension in the allowed extensions array
                 var ExtensionFound = In_Array(AllowedExtensionsList, FileExtension);
                 if (ExtensionFound == false)
                 {
                     // The file extension isn't allowed
                     alert(ExtensionsMsgError);
                     return false ;
                 }

                 // Display the waiting bar
                 showWait(WaitingMsg);

                 return true;
             }
         }
         else
         {
             return true;
         }
     }
 }


/**
 * Function used to validate the job form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-27
 *
 * @return Boolean                TRUE if the job form is correctly entered, FALSE otherwise
 */
 function VerificationJob()
 {
     return true;
 }


/**
 * Function used to validate the donation form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-05-31
 *
 * @param ReferenceMsgError           String    Error message if the reference of the donation field is empty
 * @param RelationshipMsgError        String    Error message if no relationship is selected and a family is selected
 * @param LastnameMsgError            String    Error message if the lastname of the donation field is empty
 * @param FirstnameMsgError           String    Error message if the firstname of the donation field is empty
 * @param AddressMsgError             String    Error message if the address of the donation field is empty
 * @param TownMsgError                String    Error message if no selected town
 * @param MainEmailMsgError           String    Error message if the main e-mail field is empty or not valid
 * @param SecondEmailMsgError         String    Error message if the second e-mail field is not valid
 * @param AmountMsgError              String    Error message if the amount field is empty
 * @param BankMsgError                String    Error message if the bank field is empty and the payment mode
 *                                              is "Check"
 * @param CheckNbMsgError             String    Error message if the check number field is empty and the
 *                                              payment mode is "Check"
 *
 * @return Boolean                    TRUE if the donation form is correctly entered, FALSE otherwise
 */
 function VerificationDonation(ReferenceMsgError, RelationshipMsgError, LastnameMsgError, FirstnameMsgError, AddressMsgError, TownMsgError, MainEmailMsgError, SecondEmailMsgError, AmountMsgError, BankMsgError, CheckNbMsgError)
 {
     if ((document.forms[0].sReference) && (document.forms[0].sReference.value == ""))
     {
         alert(ReferenceMsgError);
         return false;
     }
     else if ((document.forms[0].lFamilyID) && (document.forms[0].lRelationship)
              && (document.forms[0].lFamilyID.options[document.forms[0].lFamilyID.selectedIndex].value > 0)
              && (document.forms[0].lRelationship.options[document.forms[0].lRelationship.selectedIndex].value == 0))
     {
         // Family selected nut no relationship selected
         alert(RelationshipMsgError);
         return false;
     }
     else if ((document.forms[0].sLastname) && (document.forms[0].sLastname.value == ""))
     {
         alert(LastnameMsgError);
         return false;
     }
     else if ((document.forms[0].sFirstname) && (document.forms[0].sFirstname.value == ""))
     {
         alert(FirstnameMsgError);
         return false;
     }
     else if ((document.forms[0].sAddress) && (document.forms[0].sAddress.value == ""))
     {
         alert(AddressMsgError);
         return false;
     }
     else if (document.forms[0].lTownID.options[document.forms[0].lTownID.selectedIndex].value == 0)
     {
         alert(TownMsgError);
         return false;
     }

     else if ((document.forms[0].sMainEmail) && (document.forms[0].sMainEmail.value != "")
              && (isValideEmailAddress(document.forms[0].sMainEmail.value) == false))
     {
         alert(MainEmailMsgError);
         return false;
     }
     else if ((document.forms[0].sSecondEmail) && (document.forms[0].sSecondEmail.value != "")
              && (isValideEmailAddress(document.forms[0].sSecondEmail.value) == false))
     {
         alert(SecondEmailMsgError);
         return false;
     }
     else if ((document.forms[0].fAmount) && ((document.forms[0].fAmount.value == "") || (document.forms[0].fAmount.value <= 0)
              || (isNaN(parseFloat(document.forms[0].fAmount.value)))))
     {
         alert(AmountMsgError);
         return false;
     }
     else
     {
         if ((document.forms[0].lPaymentMode)
             && (document.forms[0].lPaymentMode.options[document.forms[0].lPaymentMode.selectedIndex].value == 1))
         {
             // Mode = check
             if (document.forms[0].lBankID.options[document.forms[0].lBankID.selectedIndex].value == 0)
             {
                 alert(BankMsgError);
                 return false;
             }
             else if (document.forms[0].sCheckNb.value == "")
             {
                 alert(CheckNbMsgError);
                 return false;
             }
         }

         return true;
     }
 }


/**
 * Function used to validate the holiday form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param StartDateMsgError            String    Error message if the start date field is empty or not valid
 * @param WrongDatesMsgError           String    Error message if the start date and end dates aren't valid
 *
 * @return Boolean                     TRUE if the holiday form is correctly entered, FALSE otherwise
 */
 function VerificationHoliday(StartDateMsgError, WrongDatesMsgError)
 {
     if (document.forms[0].startDate.value == "")
     {
         alert(StartDateMsgError);
         return false;
     }
     else if (document.forms[0].endDate.value == "")
     {
         alert(WrongDatesMsgError);
         return false;
     }
     else
     {
         // We convert the start date to a Date object
         StartDateDMY = ListSepToArray(document.forms[0].startDate.value, "/");
         StartDate = new Date(StartDateDMY[2], StartDateDMY[1] - 1, StartDateDMY[0]);

         // We convert the end date to a Date object
         EndDateDMY = ListSepToArray(document.forms[0].endDate.value, "/");
         EndDate = new Date(EndDateDMY[2], EndDateDMY[1] - 1, EndDateDMY[0]);

         // Compute the number of days between the start date and the end date
         DeadlineDelay = (EndDate.getTime() - StartDate.getTime()) / 86400000;
         if (DeadlineDelay < 0)
         {
             alert(WrongDatesMsgError);
             return false;
         }
     }

     return true;
 }


/**
 * Function used to validate the opened special day form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param DateMsgError            String    Error message if the date field is empty or not valid
 *
 * @return Boolean                TRUE if the opened special day form is correctly entered, FALSE otherwise
 */
 function VerificationOpenedSpecialDay(DateMsgError)
 {
     if (document.forms[0].startDate.value == "")
     {
         alert(DateMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }


/**
 * Function used to validate the config parameter form
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-03
 *
 * @param MandatoryMsgError       String    Error message if a mandatory field is empty or not valid
 *
 * @return Boolean                TRUE if the config parameter form is correctly entered,
 *                                FALSE otherwise
 */
 function VerificationConfigParameter(MandatoryMsgError)
 {
     if (document.forms[0].sParamName.value == "")
     {
         alert(MandatoryMsgError);
         return false;
     }
     else if (document.forms[0].sParamType.value == "")
     {
         alert(MandatoryMsgError);
         return false;
     }
     else
     {
         return true;
     }
 }
