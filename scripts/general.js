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
}

function showLoginForm() {
  // create mask to shade out the rest of the page
  var mask = document.createElement("div");
  var body = document.getElementsByTagName("body")[0];
  body.appendChild(mask);
  mask.id = "loginmask";
  // create login form
  var form = createElementAttributes("form", ["id=loginform", "method=post", "action=/user/login.php"]);
  body.appendChild(form);
  form.mask = mask;  // save mask so it can easily be removed if the cancel button is selected
  var fieldset = document.createElement("fieldset");
  form.appendChild(fieldset);
  formAddData(fieldset, "return", "xml");
  formAddData(fieldset, "formid", "userlogin");
  formAddData(fieldset, "website", "DO NOT CHANGE THIS");
  formAddData(fieldset, "comment", "");
  var table = document.createElement("table");
  fieldset.appendChild(table);
  table.className = "columns";
  table.cellSpacing = "0";
  formAddField(table, "loginfield", "login", "text", "string", "username", 20, 32).focus();
  formAddField(table, "passfield", "password", "password", "password", "password", 20);
  var tr = document.createElement("tr");
  table.appendChild(tr);
  var th = document.createElement("th");
  tr.appendChild(th);
  var td = document.createElement("td");
  tr.appendChild(td);
  var input = createElementAttributes("input", ["id=rememberbox", "className=checkbox", "type=checkbox", "name=remember", "value=remember"]);
  td.appendChild(input);
  label = document.createElement("label");
  td.appendChild(label);
  label.htmlFor = "rememberbox";
  label.appendChild(document.createTextNode("remember this (sends a cookie)"));
  tr = document.createElement("tr");
  table.appendChild(tr);
  tr.appendChild(document.createElement("td"));
  td = document.createElement("td");
  tr.appendChild(td);
  input = createElementAttributes("input", ["type=submit", "name=submit", "value=login", "title=log in to track7"]);
  td.appendChild(input);
  input.onclick = submitLogin;
  td.appendChild(document.createTextNode(" "));
  input = createElementAttributes("input", ["type=submit", "name=submit", "value=cancel", "title=don't log in after all"]);
  td.appendChild(input);
  input.onclick = cancelLogin;
  td.appendChild(document.createTextNode(" "));
  input = createElementAttributes("input", ["type=submit", "name=submit", "value=reset password", "title=have your password reset and e-mailed to you"]);
  td.appendChild(input);
  return false;
}

function cancelLogin() {
  if(this.form) {
    if(this.form.mask)
      this.form.mask.parentNode.removeChild(this.form.mask);
    this.form.parentNode.removeChild(this.form);
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
    alert("error(s) encountered casting your vote:\n" + errors);
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
  parent.appendChild(input);
  input.type = "hidden";
  input.name = name;
  input.value = value;
}

function formAddField(table, id, name, type, className, prompt, size, maxLength) {
  var tr = document.createElement("tr");
  table.appendChild(tr);
  var th = document.createElement("th");
  tr.appendChild(th);
  var label = document.createElement("label");
  th.appendChild(label);
  label.htmlFor = id;
  label.appendChild(document.createTextNode(prompt));
  var td = document.createElement("td");
  tr.appendChild(td);
  var props = ["id=" + id, "name=" + name, "className=" + className, "type=" + type, "size=" + size];
  if(maxLength)
    props[props.length] = "maxLength=" + maxLength;
  var input = createElementAttributes("input", props);
  td.appendChild(input);
  return input;
}

function createElementAttributes(name, attrs) {
  var el = document.createElement(name);
  for(var i = 0; i < attrs.length; i++) {
    attr = attrs[i].split("=");
    el[attr[0]] = attr[1];
  }
  return el;
}
