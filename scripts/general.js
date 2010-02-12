addStartupFunction(enableLogin);
addStartupFunction(enableVotes);

function addStartupFunction(func) {
  if(window.addEventListener)  // W3C
    window.addEventListener("load", func, false);
  else if(window.attachEvent)  // MS
    window.attachEvent("onload", func);
}

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

function cancelLogin() {
  if(this.form) {
    this.form.style.display = "";
    var loginmask = document.getElementById("loginmask");
    if(loginmask)
      loginmask.style.display = "";
    return false;
  }
}

function submitLogin() {
  // DO:  show some sort of waiting message
  postFormAsync(this, loginFinished, this.form);
  return false;
}
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

function enableVotes() {
  var divs = document.getElementsByTagName("div");
  for(var i = 0; i < divs.length; i++)
    if(divs[i].className == "rating") {
      var links = new Array();
      for(var a = divs[i].firstChild; a != null; a = a.nextSibling) {
        // DO:  highlight related links on hover
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

function hilightOtherVotes() {
  if(this.hilite)
    for(var i = 0; i < this.hilite.length; i++)
      this.hilite[i].className = "votepartner";
}

function unhilightOtherVotes() {
  if(this.hilite)
    for(var i = 0; i < this.hilite.length; i++)
      this.hilite[i].className = "";
}

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
  return true;
}

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
  return true;
}

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

function getSelectedText(element) {
  if(element && element.setSelectionRange && typeof(element.selectionStart) != "undefined")
    return element.value.substr(element.selectionStart, element.selectionEnd - element.selectionStart);  // this gets executed in Firefox
  if(window.getSelection)  // element doesn't have its own selection
    return window.getSelection();
  if(document.selection)
    return document.selection.createRange().text;  // this gets executed in IE6
  return false;
}

function formAddData(parent, name, value) {
  var input = document.createElement("input");
  input.type = "hidden";  // IE seems to crash doing this if it's already been added to the document
  parent.appendChild(input);
  input.name = name;
  input.value = value;
}
