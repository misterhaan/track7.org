addStartupFunction(setPageWidth);
window.onorientationchange = setPageWidth;
addStartupFunction(enableLogin);
addStartupFunction(enableVotes);

/**
 * Adds a function as a window onload function, supporting multiple functions.
 * @param func Function to run once the page finishes loading
 */
function addStartupFunction(func) {
  if(window.addEventListener)  // W3C
    window.addEventListener("load", func, false);
  else if(window.attachEvent)  // MS
    window.attachEvent("onload", func);
}

/**
 * Resets the page to fit a rotated viewport on mobile browsers
 */
function setPageWidth() {
  var viewport = document.getElementById("viewport");
  if(viewport)
    viewport.content = "width=device-width; initial-scale=1.0";  // setting it to the same value seems to make it re-calculate the available width
}

/**
 * Enables the ajax login feature.
 */
function enableLogin() {
  var loginlink = document.getElementById("headerloginlink");
  if(loginlink)
    loginlink.onclick = showLoginForm;
  var loginform = document.getElementById("loginform");
  if(loginform) {
    var fieldset = loginform.getElementsByTagName("fieldset");
    if(fieldset.length) {
      fieldset = fieldset[0];
      formAddData(fieldset, "return", "xml");
      formAddData(fieldset, "formid", "userlogin");
      formAddData(fieldset, "website", "DO NOT CHANGE THIS");
      formAddData(fieldset, "comment", "");
    }
  }
  var loginbutton = document.getElementById("loginbutton");
  if(loginbutton)
    loginbutton.onclick = submitLogin;
  var logincancel = document.getElementById("logincancel");
  if(logincancel)
    logincancel.onclick = cancelLogin;
}

/**
 * Show ajax login form.  Click event handler for login links.
 * @return False if the login form was displayed, so the click event should be canceled.
 */
function showLoginForm() {
  var mask = document.getElementById("loginmask");
  if(mask)
    mask.style.display = "block";
  var loginform = document.getElementById("loginform");
  if(loginform) {
    loginform.style.display = "block";
    var loginfield = document.getElementById("loginfield");
    if(loginfield)
      loginfield.focus();
    return false;
  }
}

/**
 * Hide ajax login form.  Click event for login form's cancel button.
 * @return False if the login form was hidden, so the click event should be canceled.
 */
function cancelLogin() {
  if(this.form) {
    this.form.style.display = "";
    var loginmask = document.getElementById("loginmask");
    if(loginmask)
      loginmask.style.display = "";
    return false;
  }
}

/**
 * Submit ajax login form.  Click event for login form's login button.
 * @return False always, so the click event should be canceled.
 */
function submitLogin() {
  // DO:  show some sort of waiting message
  postFormAsync(this, loginFinished, this.form);
  return false;
}
/**
 * Ajax login form completion handler.  Reloads the page on successful login, or
 * shows an error message.
 * @param req XMLHttpRequest object of the ajax request.
 * @param form Reference to the login form element.
 */
function loginFinished(req, form) {
  if(!req.responseXML || !req.responseXML.documentElement) {
    alert("Error:\n" + req.responseText);
    return;
  }
  var response = req.responseXML.documentElement;
  if(response.attributes.getNamedItem("result").value.toLowerCase() != "success") {
    var errors = "";
    for(var error = response.firstChild; error; error = error.nextSibling)
      if(error.firstChild)  // it will find the line breaks
        errors += "\n" + error.firstChild.nodeValue;
    alert("login attempt failed:\n" + errors);
    return;
  }
  // login worked, so reload the current page
  window.location.reload();
}

/**
 * Enable ajax voting for any voting links on the page.
 */
function enableVotes() {
  var divs = document.getElementsByTagName("div");
  for(var i = 0; i < divs.length; i++)
    if(divs[i].className == "rating") {
      var links = new Array();
      for(var a = divs[i].firstChild; a != null; a = a.nextSibling) {
        var img = a.getElementsByTagName("img");
        if(img.length) {
          a.vote = img[0].alt;
          var vote = +a.vote;
          links[vote] = a;
          if(vote < 0)
            for(var j = -3; j < vote; j++) {
              if(!links[j].hilite)
                links[j].hilite = new Array();
              links[j].hilite[links[j].hilite.length] = a;
            }
          if(vote > 1) {
            a.hilite = new Array();
            for(var j = 1; j < vote; j++)
              a.hilite[a.hilite.length] = links[j];
          }
          a.onclick = submitVote;
          a.onmouseover = hilightOtherVotes;
          a.onmouseout = unhilightOtherVotes;
        }
      }
    }
}

/**
 * Submit an ajax vote.  Called by click event of voting links.
 * @return False always, so the link isn't followed.
 */
function submitVote() {
  var params = new Array();
  var p = 0;
  params[p++] = "formid=vote";
  params[p++] = uriParam("vote", this.vote);
  params[p++] = uriParam("website", "DO NOT CHANGE THIS");  // this and the next are for anti-spam measures.
  params[p++] = "comment=";
  params[p++] = "return=xml";
  var msg = document.createElement("div");
  msg.appendChild(document.createTextNode("casting vote..."))
  this.parentNode.appendChild(msg);
  postAsync(this.href, voteFinished, new Array(this, msg), params);
  return false;
}
/**
 * Ajax vote completion handler.  Updates the rating display with the new
 * average rating and user's rating.
 * @param req XMLHttpRequest object of the ajax request.
 * @param args Array of the link element and the temporary message element.
 * @return
 */
function voteFinished(req, args) {
  if(args.length != 2) {
    alert("wrong number of arguments");
    return;
  }
  var link = args[0];
  var msg = args[1];
  try {
    if(!req.responseXML || !req.responseXML.documentElement) {
      alert("Error:\n" + req.responseText);
      return;
    }
    var response = req.responseXML.documentElement;
    if(response.attributes.getNamedItem("result").value.toLowerCase() != "success") {
      var errors = "";
      for(var error = response.firstChild; error; error = error.nextSibling)
        if(error.firstChild)  // it will find the line breaks
          errors += "\n" + error.firstChild.nodeValue;
      alert("error(s) encountered casting your vote:\n" + errors);
      return;
    }
    var vote = +response.getElementsByTagName("vote")[0].firstChild.nodeValue;
    var rating = +response.getElementsByTagName("rating")[0].firstChild.nodeValue;
    var votes = +response.getElementsByTagName("votes")[0].firstChild.nodeValue;
    for(var a = link.parentNode.firstChild; a != null; a = a.nextSibling)
      if(a.nodeName == "a") {
        var src = "/images/vote/";
        if(vote < 0 && +a.vote < 0 && +a.vote >= vote || vote == 0 && +a.vote == 0 || vote > 0 && +a.vote > 0 && +a.vote <= vote)
          src += "current/";
        if(+a.vote < 0)
          src += "down";
        else if(+a.vote == 0)
          src += "none";
        else
          src += "up";
        // negative link that needs to be filled
        if(+a.vote < 0 && rating < 0 && +a.vote + 1 > rating)
          if(+a.vote >= rating)
            src += "10";
          else
            src += round((rating - Math.ceil(rating)) * -10);
        // indifferent link that needs te be filled
        if(+a.vote == 0 && rating == 0)
          src += "0";
        // positive link that needs to be filled
        if(+a.vote > 0 && rating > 0 && +a.vote - 1 < rating)
          if(+a.vote <= rating)
            src += "10";
          else
            src += round((rating - Math.floor(rating)) * 10);
        a.getElementsByTagName("img")[0].src = src + ".png";
      } else if(a.nodeName == "div" && a.firstChild.nodeValue.match(/^\([0-9]+ votes?\)$/))
        a.firstChild.nodeValue = "(" + votes + "vote" + (votes == 1 ? ")" : "s)");
  } finally {
    msg.parentNode.removeChild(msg);
  }
}

/**
 * Show highlighting behind lesser votes of the same direction.  Called by
 * MouseOver event of voting links.
 */
function hilightOtherVotes() {
  if(this.hilite)
    for(var i = 0; i < this.hilite.length; i++)
      this.hilite[i].className = "votepartner";
}

/**
 * Hide highlighting added to other links when moving the mouse off a voting
 * link.  Called by MouseOut event of voting links.
 */
function unhilightOtherVotes() {
  if(this.hilite)
    for(var i = 0; i < this.hilite.length; i++)
      this.hilite[i].className = "";
}

/**
 * Enable a form text field for suggestions.
 * @param field ID of the form field to enable.
 */
function enableSuggest(field, suggestUrl) {
  if(field = document.getElementById(field)) {
    field.setAttribute("autocomplete", "off");
    field.toreq = false;
    field.ajaxreq = false;
    field.suggestUrl = suggestUrl;
    field.onkeydown = suggestKeyDown;
    field.onkeypress = suggestKeyPress;
    field.onblur = hideSuggestions;
  }
}

/**
 * Handle keys that don't change the field value for suggestion-enabled fields.
 * Called by KeyDown event handler.
 * @param e W3C style event object.
 * @return False if enter was pressed to select from the list, so the event should be canceled.
 */
function suggestKeyDown(e) {
  e = e || window.event;  // get event from ie model
  switch(e.which || e.keyCode) {
    case 8:  // backspace:  send keypress event for ie since it doesn't do that itself
      if(window.event)
        scheduleSuggest(this);
      break;
    case 9:  // tab:  accept selection if there is one
      if(this.dropdown && this.dropdown.current) {
        this.value = this.dropdown.current.firstChild.data;
        this.dropdown.parentNode.removeChild(this.dropdown);
        this.dropdown = false;
      }
      break;
    case 13:  // return:  accept selection if there is one (and cancel)
      if(this.dropdown && this.dropdown.current) {
        this.value = this.dropdown.current.firstChild.data;
        this.dropdown.parentNode.removeChild(this.dropdown);
        this.dropdown = false;
        return false;  // this should be in the if block so the form can be submitted using a second enter press
      }
    case 27:  // escape:  hide selection
      if(this.dropdown) {
        this.dropdown.parentNode.removeChild(this.dropdown);
        this.dropdown = false;
      }
      break;
    case 38:  // up arrow:  select previous
      if(this.dropdown)
        if(this.dropdown.current) {
          if(this.dropdown.current.previousSibling && this.dropdown.current.previousSibling.className != "message") {
            this.dropdown.current.className = "";
            this.dropdown.current = this.dropdown.current.previousSibling;
            this.dropdown.current.className = "current";
          }
        } else if(this.dropdown.lastChild.className != "message") {  // nothing currently selected; select last item
          this.dropdown.current = this.dropdown.lastChild;
          this.dropdown.current.className = "current";
        }
      break;
    case 40:  // down arrow:  select next
      if(this.dropdown)
        if(this.dropdown.current) {
          if(this.dropdown.current.nextSibling && this.dropdown.current.nextSibling.className != "message") {
            this.dropdown.current.className = "";
            this.dropdown.current = this.dropdown.current.nextSibling;
            this.dropdown.current.className = "current";
          }
        } else if(this.dropdown.firstChild.className != "message") {  // nothing currently selected; select first item
          this.dropdown.current = this.dropdown.firstChild;
          this.dropdown.current.className = "current";
        }
      break;
    case 46:  // delete key:  update suggestions
      scheduleSuggest(this);
      break;
  }
}

/**
 * Schedule suggestion retrieval for keys that change the field value.  Called
 * by KeyPress event.
 * @param e W3C style event object.
 */
function suggestKeyPress(e) {
  if(!e) {  // get event from ie model (do these together so we don't get firefox's e.keyCode when e.which is 0)
    e = window.event;
    e.which = e.keyCode;
  }
  if(e.which == 0 || e.which == 13 || e.which == 27)  // firefox sends 0 for arrow keys, etc.  ie sends 27 for escape
    return;
  scheduleSuggest(this);
}
/**
 * Schedules an ajax request to retrieve suggestions for the field.  Called by
 * suggestKeyPress or suggestKeyDown.
 * @param field Text field to suggest for.
 */
function scheduleSuggest(field) {
  if(field.toreq) {
    clearTimeout(field.toreq);
    field.toreq = false;
  }
  field.toreq = setTimeout(function() { submitSuggest(field); }, 250);
}
/**
 * Sends an ajax request for suggestions for a field.
 * @param field Text field to suggest for.
 */
function submitSuggest(field) {
  field.toreq = false;
  if(field.ajaxreq) {
    field.ajaxreq.abort();
    field.ajaxreq = false;
  }
  if(field.value)  // only request if field isn't blank
    field.ajaxreq = getAsync(field.suggestUrl + encodeURIComponent(field.value), suggestFinished, field);
  else if(field.dropdown) {  // if field is blank but a dropdown was showing, get rid of it
    field.dropdown.parentNode.removeChild(field.dropdown);
    field.dropdown = false;
  }
}
/**
 * Ajax suggest completion handler.  Displays suggestions as a list after the
 * field.
 * @param req XMLHttpRequest object of the ajax request.
 * @param field Text field the suggestions are for.
 */
function suggestFinished(req, field) {
  field.ajaxreq = false;
  var drop = document.createElement("ol");
  drop.className = "suggestdrop";
  var names = req.responseText.split("\n");
  for(var name in names)
    if(names[name]) {
      var li = document.createElement("li");
      li.appendChild(document.createTextNode(names[name]));
      drop.appendChild(li);
      if(names[name].charAt(0) == '<')
        li.className = "message";
      else {
        li.field = field;
        li.onclick = chooseSuggestion;
        li.onmouseover = clearSuggestion;
      }
    }
  if(field.dropdown)
    field.dropdown.parentNode.removeChild(field.dropdown);
  field.dropdown = drop;
  if(field.nextSibling)
    field.parentNode.insertBefore(drop, field.nextSibling);
  else
    field.parentNode.appendChild(drop);
}

/**
 * Choose a suggestion, place its value in the field, and hide the results.
 * Called by Click event handler of a suggestion item.
 */
function chooseSuggestion() {
  var field = this.field;
  if(field) {
    field.value = this.firstChild.data;
    if(field.dropdown) {
      field.dropdown.parentNode.removeChild(field.dropdown);
      field.dropdown = false;
    }
  }
}

/**
 * Clears the suggestion chosen by arrow keys.  Called by MouseOver event
 * handler of a suggestion item.
 */
function clearSuggestion() {
  if(this.field.dropdown.current) {
    this.field.dropdown.current.className = "";
    this.field.dropdown.current = false;
  }
}

/**
 * Cancels any waiting suggestion requests and hides any visible suggestions.
 * Called by Blur event handler of the field.
 * @param field Text field to hide suggestions for.
 */
function hideSuggestions(field) {
  field = field || this;
  if(field.toreq) {
    clearTimeout(field.toreq);
    field.toreq = false;
  }
  if(field.ajaxreq) {
    field.ajaxreq.abort();
    field.ajaxreq = false;
  }
  if(field.dropdown) {
    field.dropdown.parentNode.removeChild(field.dropdown);
    field.dropdown = false;
  }
}

/**
 * Submit an asynchronous HTTP request using the GET method.
 * @param url URL to request.  May contain querystring parameters, and usually will.
 * @param finished Function to call when the request has completed.  Should accept two arguments:  the XMLHttpRequest object with the results, and the args parameter passed to this function.
 * @param args Any argument(s) the completion function may need.  Use an array to pass multiple values.
 * @return The XMLHttpRequest object on success, or false on failure.
 */
function getAsync(url, finished, args) {
  var req = ajaxRequestObject();
  if(req == null) {
    alert("your browser supports javascript but not ajax.  please update your browser or try again with javascript off.");
    return false;
  }
  req.onreadystatechange = function() {
    if(req.readyState == 4)
      finished(req, args);
  }
  req.open("GET", url, true);
  req.send(null);
  return req;
}

/**
 * Submit a form asynchronously using the POST method.  Form fields are
 * automatically collected and sent as POST data.
 * @param submit The submit button used so submit the form, to make sure its value is posted.
 * @param finished Function to call when the request has completed.  Should accept two arguments:  the XMLHttpRequest object with the results, and the args parameter passed to this function.
 * @param args Any argument(s) the completion function may need.  Use an array to pass multiple values.
 * @return The XMLHttpRequest object on success, or false on failure.
 */
function postFormAsync(submit, finished, args) {
  var params = new Array();
  var p = 0;
  var form = submit.form;
  var inputs = form.getElementsByTagName("input");
  for(var i = 0; i < inputs.length; i++)
    if(inputs[i].name && (inputs[i].type != "submit" || inputs[i] == submit) && (inputs[i].type != "checkbox" || inputs[i].checked))
      params[p++] = uriParam(inputs[i].name, inputs[i].value);
  var selects = form.getElementsByTagName("select");
  for(var i = 0; i < selects.length; i++)
    if(selects[i].name)
      params[p++] = uriParam(selects[i].name, selects[i].options[selects[i].selectedIndex].value);
  var textareas = form.getElementsByTagName("textarea");
  for(var i = 0; i < textareas.length; i++)
    if(textareas[i].name)
      params[p++] = uriParam(textareas[i].name, textareas[i].value);
  return postAsync(form.action, finished, args, params);
}

/**
 * Submit an asynchronous HTTP request using the POST method.
 * @param url URL to POST request.  May contain querystring parameters.
 * @param finished Function to call when the request has completed.  Should accept two arguments:  the XMLHttpRequest object with the results, and the args parameter passed to this function.
 * @param args Any argument(s) the completion function may need.  Use an array to pass multiple values.
 * @param params POST data for the request.  May be a string formatted var1=data1&var2=data2 or an array of strings formatted var=data.
 * @return The XMLHttpRequest object on success, or false on failure.
 */
function postAsync(url, finished, args, params) {
  var req = ajaxRequestObject();
  if(req == null) {
    alert("your browser supports javascript but not ajax.  please update your browser or try again with javascript off.");
    return false;
  }
  req.onreadystatechange = function() {
    if(req.readyState == 4)
      finished(req, args);
  }
  req.open("POST", url, true);
  var data = params instanceof Array ? params.join("&") : params;
  req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  req.send(data);
  return req;
}

/**
 * Encode a name and value to be used as a URI parameter.
 * @param name Name of the data.
 * @param value Value of the data.
 * @return URI-encoded parameter string.
 */
function uriParam(name, value) {
  return encodeURIComponent(name) + "=" + encodeURIComponent(value);
}

function ajaxRequestObject() {
  var req = null;
  try {
    // modern browsers and internet explorer 7+
    req = new XMLHttpRequest();
  } catch(e) {
    try {
      // internet explorer 6
      req = new ActiveXObject("Msxml2.XMLHTTP");
    } catch(e) {
      try {
        // older internet explorer
        req = new ActiveXObject("Microsoft.XMLHTTP");
      } catch(e) {}  // couldn't get an http request object
    }
  }
  return req;
}

/**
 * Find the selected text within an element, the entire window.
 * @param element Element to search first for selected text.
 * @return Selected text, or false if selection could not be found.
 */
function getSelectedText(element) {
  if(element && element.setSelectionRange && typeof(element.selectionStart) != "undefined")
    return element.value.substr(element.selectionStart, element.selectionEnd - element.selectionStart);  // this gets executed in Firefox
  if(window.getSelection)  // element doesn't have its own selection
    return window.getSelection();
  if(document.selection)
    return document.selection.createRange().text;  // this gets executed in IE6
  return false;
}

/**
 * Add a hidden data field to a form.
 * @param parent Parent element for the hidden field.
 * @param name Name of the field.
 * @param value Value of the field.
 */
function formAddData(parent, name, value) {
  var input = document.createElement("input");
  input.type = "hidden";  // IE seems to crash doing this if it's already been added to the document
  parent.appendChild(input);
  input.name = name;
  input.value = value;
}
