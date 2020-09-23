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
 * JS plugin LostPwd module : display a form to get a new password if lost
 *
 * @author Christophe Javouhey
 * @version 2.4
 * @since 2012-09-05
 */

 var LostPwdPluginPath;
 var LostPwdPluginLangage;


/**
 * Function used to init this plugin
 *
 * @author Christophe Javouhey
 * @version 2.1 :
 *     - 2014-01-02 : taken into account english language
 *     - 2014-04-17 : taken into account Occitan language
 *
 * @since 2012-09-05
 */
 function initLostPwdPlugin(Path, Lang)
 {
     LostPwdPluginPath = Path;
     LostPwdPluginLangage = Lang;

     var objWebPage = document.getElementById('content');

     // Plugin only displayed if the user isn't logged
     if ((objWebPage) && (!document.getElementById('contextualmenu'))) {
         var objPwdMsg = document.createElement('div');
         var sMsg = '';
         switch(LostPwdPluginLangage) {
             case 'oc':
                 sMsg = "Senhal oblidat ?";
                 sMsgTip = "Clicatz aici per afichar lo formulari de recuperacion d'un novèl senhal...";
                 sEmailLabel = "Dintratz vòstra adreça e-mèl";
                 sEmailTip = "Dintratz l'adreça ligada al vòstre compte per recuperar lo novèl senhal.";
                 sSubmitCaption = 'Envejar';
                 sSubmitTip = "Clicatz sus aqueste boton per recebre vòstre novèl senhal.";
                 break;

             case 'fr':
                 sMsg = "Mot de passe oublié ?";
                 sMsgTip = "Cliquez ici pour afficher le formulaire de récupération d'un nouveau mot de passe...";
                 sEmailLabel = "Saisissez votre adresse e-mail";
                 sEmailTip = "Entrez l'adresse e-mail associée à votre compte pour récupérer un nouveau mot de passe.";
                 sSubmitCaption = 'Envoyer';
                 sSubmitTip = "Cliquez sur ce bouton pour recevoir votre nouveau mot de passe par e-mail.";
                 break;

             case 'en':
             default:
                 sMsg = "Forgotten password ?";
                 sMsgTip = "Click here to display the form to get a new password...";
                 sEmailLabel = "Enter your e-mail address";
                 sEmailTip = "Enter the address e-mail linked to your account to get a new password.";
                 sSubmitCaption = 'Send';
                 sSubmitTip = "Click on this button to get your new password by e-mail.";
                 break;
         }

         objPwdMsg.innerHTML = '<p><em id="LostPwdLink" title="' + sMsgTip + '">' + sMsg + '</em></p>';
         objPwdMsg.className = 'PHPLostPwdPlugin';

         var objImgIntranet = objWebPage.getElementsByTagName('img');
         if (objImgIntranet.length > 0) {
             var objLostPwdFormArea = document.createElement('p');
             objLostPwdFormArea.setAttribute('id', 'LostPwdFormArea');
             objLostPwdFormArea.style.display = 'none';
             objLostPwdFormArea.innerHTML = '<form name="LostPwdForm" action="../Plugins/PHPLostPwdPlugin/PHPLostPwdPluginFormProcess.php" method="post"><fieldset><legend>' + sEmailLabel + '</legend><input type="text" class="text" id="sEmailLostPwdPlugin" name="sEmailLostPwdPlugin" size="30" value="" title="' + sEmailTip + '" /><input type="submit" class="submit" id="bSubmitLostPwdPlugin" name="bSubmitLostPwdPlugin" title="' + sSubmitTip + '" value="' + sSubmitCaption + '" /></fieldset></form>';
             objPwdMsg.appendChild(objLostPwdFormArea);

             objImgIntranet[0].parentNode.appendChild(objPwdMsg);

             var objLostPwdLink = document.getElementById('LostPwdLink');
             if(window.attachEvent) {
                 // IE
                 objLostPwdLink.attachEvent("onclick", LostPwdPluginLinkClick);
             } else {
                 // FF
                 objLostPwdLink.addEventListener("click", LostPwdPluginLinkClick, false);
             }
         }
     }
 }


 function LostPwdPluginLinkClick(evt)
 {
     var obj = evt.target || evt.srcElement;

     var objDivForm = document.getElementById('LostPwdFormArea');
     if (objDivForm) {
         if ((objDivForm.style.display == 'none') || (objDivForm.style.display == '')) {
             objDivForm.style.display = 'block';
             document.getElementById('sEmailLostPwdPlugin').focus();
         } else {
             objDivForm.style.display = 'none';
         }
     }
 }



