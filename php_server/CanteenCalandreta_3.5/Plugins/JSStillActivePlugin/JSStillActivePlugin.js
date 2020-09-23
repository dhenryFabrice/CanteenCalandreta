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
 * @version 3.5
 *     - 2010-03-22 : check the connection on click on a submit button
 *     - 2014-04-17 : taken into account occitan language
 *
 * @since 2008-02-15
 */


 var StillActivePluginPath;
 var StillActivePluginAjax;
 var StillActivePluginLanguage;


/**
 * Function used to init this plugin
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-02-15
 *
 * @param Timer     Integer    Timeout to poll if the session is still active, in seconds [10..n]
 */
 function initStillActivePlugin(Timer)
 {
     if (Timer < 10) {
         Timer = 10;
     }

     StillActivePluginLanguage = 'fr';

     $A(document.getElementsByTagName("script")).findAll( function(s) {
         return (s.src && s.src.match(/JSStillActivePlugin\.js(\?.*)?$/))
     }).each( function(s) {
         StillActivePluginPath = s.src.replace(/JSStillActivePlugin\.js(\?.*)?$/,'');
     });

     var StillActivePluginPoll = new PeriodicalExecuter(function(pe)
                                                        {
                                                            // Check if the session of the logged user is still active
                                                            new Ajax.Request(StillActivePluginPath + 'PHPStillActivePlugin.php',
                                                                             {method: 'get',
                                                                              onComplete: function(requester)
                                                                                          {
                                                                                              if (null == requester.responseText.match(/^200/)) {
                                                                                                  // We stop the polling
                                                                                                  pe.stop();

                                                                                                  // Received code <> 200 -> The user isn't connected
                                                                                                  var DivAlert = document.createElement('div');
                                                                                                  DivAlert.setAttribute('id', 'StillActiveAlert');

                                                                                                  var ParaAlert = document.createElement('p');
                                                                                                  switch(StillActivePluginLanguage) {
                                                                                                      case 'oc':
                                                                                                          ParaAlert.innerHTML = "La vòstra sesilha es acabada! Avètz de vos tornar connectar se volètz pas pèrdre las donadas qu'èretz en tren de dintrar!<br /><br /><br />";
                                                                                                          break;

                                                                                                      case 'fr':
                                                                                                      default:
                                                                                                          ParaAlert.innerHTML = "ATTENTION : Votre session a expiré! Vous devez vous reconnecter si vous ne voulez pas perdre les données que vous étiez en train de saisir!<br /><br /><br />";
                                                                                                          break;
                                                                                                  }

                                                                                                  var ButtonAlert = document.createElement('button');
                                                                                                  ButtonAlert.setAttribute('type', 'button');
                                                                                                  ButtonAlert.innerHTML = "Ok";

                                                                                                  if(window.attachEvent) {
                                                                                                      ButtonAlert.attachEvent("onclick", StillActivePluginClick);                        // IE
                                                                                                  } else {
                                                                                                      ButtonAlert.addEventListener("click", StillActivePluginClick, false);                // FF
                                                                                                  }

                                                                                                  ParaAlert.appendChild(ButtonAlert);

                                                                                                  DivAlert.appendChild(ParaAlert);
                                                                                                  if (document.getElementById('webpage')) {
                                                                                                      document.getElementById('webpage').appendChild(DivAlert);
                                                                                                  } else {
                                                                                                      document.getElementById('content').appendChild(DivAlert);
                                                                                                  }
                                                                                              }
                                                                                          }
                                                                             });
                                                        }, Timer);

     // Get input fields with "submit" type
     if(window.XMLHttpRequest) // Firefox
         StillActivePluginAjax = new XMLHttpRequest();
     else if(window.ActiveXObject) // Internet Explorer
         StillActivePluginAjax = new ActiveXObject("Microsoft.XMLHTTP");
     else { // XMLHttpRequest non supporté par le navigateur
         alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
     }

     var ArrayTmp = new Array();
     for(var f = 0; f < document.forms.length; f++) {
         ArrayTmp = document.forms[f].getInputs('submit');
         for(var b = 0; b < ArrayTmp.length; b++) {
             if(window.attachEvent) {
                 ArrayTmp[b].attachEvent("onclick", StillActivePluginCheckBeforeSubmit);   // IE
             } else {
                 ArrayTmp[b].addEventListener("click", StillActivePluginCheckBeforeSubmit, false);   // FF
             }
         }
     }
 }


 function StillActivePluginClick(evt)
 {
     // Remove the alert message
     var DivAlert = document.getElementById('StillActiveAlert');
     DivAlert.parentNode.removeChild(DivAlert);
 }


 function StillActivePluginCheckBeforeSubmit(evt)
 {
     StillActivePluginAjax.open("GET", StillActivePluginPath + 'PHPStillActivePlugin.php', false);
     StillActivePluginAjax.send(null);

     if (StillActivePluginAjax.responseText != 200) {
         // No connected
         switch(StillActivePluginLanguage) {
             case 'oc':
                 alert("La vòstra sesilha es acabada!\nAvètz de vos tornar connectar se\nvolètz pas pèrdre las donadas qu'èretz\nen tren de dintrar!");
                 break;

             case 'fr':
             default:
                 alert("ATTENTION : Votre session a expiré!\nVous devez vour reconnecter si vous ne\nvoulez pas perdre les données que vous\nétiez en train de saisir!");
                 break;
         }

         evt.stop();

         return false;
     }
 }




