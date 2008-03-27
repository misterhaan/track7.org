window.onload = windowLoad;

function windowLoad() {
  enableAjaxPost();
  enableAjaxQuote();
}

function enableAjaxPost() {
  var form = document.getElementById("frmreply");
  if(!form)
    return;
  var formid = document.getElementsByName("formid");
  if(formid.length) {
    formid = formid[0];
    var ret = document.createElement("input");
    ret.type = "hidden";
    ret.name = "return";
    ret.value = "xml";
    formid.parentNode.insertBefore(ret, formid);
    var submits = document.getElementsByName("submit");
    for(var i = 0; i < submits.length; i++)
      if(submits[i].value.substring(0, 6) == "post a")
        submits[i].onclick = addReplySubmit;
  }
}

function addReplySubmit() {
  this.disabled = true;
  this.previousSibling.previousSibling.disabled = true;  // preview button
  postFormAsync(this, addReplyFinished, this);
  return false;
}
function addReplyFinished(req, submit) {
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
      alert("error(s) encountered saving your post:\n" + errors);
      return;
    }
    var lastpost = document.getElementById("frmreply");
    while(lastpost && lastpost.nodeName.toLowerCase() != "table")
      lastpost = lastpost.previousSibling;
    lastpost.parentNode.insertBefore(buildPostTable(response), lastpost.nextSibling);
    // clear form; re-enable buttons
    var field = document.getElementsByName("subject");
    if(field.length)
      field[0].value = field[0].defaultValue;
    field = document.getElementsByName("post");
    if(field.length)
      field[0].value = "";
  } finally {
    submit.disabled = false;
    submit.previousSibling.previousSibling.disabled = false;  // preview button
  }
}

function buildPostTable(response) {
  var table = document.createElement("table");
  table.className = "post";
  table.cellSpacing = 0;
  var tr = document.createElement("tr");
  var td = document.createElement("td");
  td.className = "userinfo";
  var user = response.getElementsByTagName("user")[0];
  var uid = user.attributes.getNamedItem("id").value;
  var username = user.getElementsByTagName("name")[0].firstChild.nodeValue;
  var friend = true;
  if(uid == "0")
    td.appendChild(document.createTextNode("anonymous"));
  else {
    var a = document.createElement("a");
    a.href = "/user/" + username + "/";
    a.appendChild(document.createTextNode(username));
    td.appendChild(a);
    if(friend = user.getElementsByTagName("friend").length) {
      var img = document.createElement("img");
      img.src = "/style/friend.png";
      img.alt = "friend";
      img.title = username + " is your friend";
      td.appendChild(img);
    }
    var avatar = user.getElementsByTagName("avatar");
    if(avatar.length) {
      avatar = avatar[0].firstChild.nodeValue;
      a = document.createElement("a");
      a.href = "/user/" + username + "/";
      var img = document.createElement("img");
      img.className = "avatar";
      img.alt = "";
      img.src = "/user/avatar/" + username + "." + avatar;
      a.appendChild(img);
      td.appendChild(a);
    }
    var div = document.createElement("div");
    div.className = "frequency";
    div.title = "frequency";
    div.appendChild(document.createTextNode(user.getElementsByTagName("rank")[0].firstChild.nodeValue));
    td.appendChild(div);
  }
  tr.appendChild(td);
  td = document.createElement("td");
  var div = document.createElement("div");
  div.className = "head";
  var div2 = document.createElement("div");
  div2.className = "subject";
  var a = document.createElement("a");
  var pid = response.getElementsByTagName("post")[0].attributes.getNamedItem("id").value;
  a.id = "p" + pid;
  a.className = "ref";
  a.href = "#p" + pid;
  a.appendChild(document.createTextNode("subject:"));
  div2.appendChild(a);
  div2.appendChild(document.createTextNode(" "));
  var span = document.createElement("span");
  span.className = "response";
  span.appendChild(document.createTextNode(response.getElementsByTagName("subject")[0].firstChild.nodeValue));
  div2.appendChild(span);
  div.appendChild(div2);
  div2 = document.createElement("div");
  div2.className = "time";
  div2.appendChild(document.createTextNode("posted: "));
  span = document.createElement("span");
  span.className = "response";
  span.appendChild(document.createTextNode(response.getElementsByTagName("time")[0].firstChild.nodeValue));
  div2.appendChild(span);
  div.appendChild(div2);
  td.appendChild(div);
  div = document.createElement("div");
  div.innerHTML = response.getElementsByTagName("message")[0].firstChild.nodeValue;
  td.appendChild(div);
  var sig = response.getElementsByTagName("signature");
  if(sig.length) {
    sig = sig[0].firstChild.nodeValue.split("\n\n");
    for(var i = 0; i < sig.length; i++) {
      var p = document.createElement("p");
      p.className = "signature";
      lines = sig[i].split("\n");
      for(var j = 0; j < lines.length; j++) {
        if(j)
          p.appendChild(document.createElement("br"));
        p.appendChild(document.createTextNode(lines[j]));
      }
      td.appendChild(p);
    }
  }
  div = document.createElement("div");
  div.className = "foot";
  if(uid != "0") {
    div2 = document.createElement("div");
    div2.className = "userlinks";
    div2.appendChild(buildImageLink("/user/sendmessage.php?to=" + username, "send " + username + " a privane message", "/style/pm.png", "pm"));
    var email = response.getElementsByTagName("email");
    if(email.length)
      div2.appendChild(buildImageLink("mailto:" + email[0].firstChild.nodeValue, "send " + username + " an e-mail", "/style/email.png", "e-mail"));
    var website = response.getElementsByTagName("website");
    if(website.length)
      div2.appendChild(buildImageLink(website[0].firstChild.nodeValue, "visit " + username + "'s website", "/style/www.png", "www"));
    if(!friend)
      div2.appendChild(buildImageLink("/user/friends.php?add=" + username, "add " + username + " to your friend list", "/style/friend-add.png", "add friend"));
    div.appendChild(div2);
    div.appendChild(buildImageLink("edit=" + pid, "edit the above post", "/style/edit.png", "edit"));
    div.appendChild(buildImageLink("delete=" + pid, "delete the above post", "/style/del.png", "delete"));
  }
  var quotelink = buildImageLink("reply" + pid, "quote the above post in a new reply", "/style/reply-quote.png", "quote");
  quotelink.className = "quote";
  quotelink.onclick = replyQuoteClick;
  div.appendChild(quotelink);
  td.appendChild(div);
  tr.appendChild(td);
  table.appendChild(tr);
  return table;
}

function buildImageLink(href, title, imgsrc, text) {
  var a = document.createElement("a");
  a.href = href;
  a.title = title;
  var img = document.createElement("img");
  img.src = imgsrc;
  img.alt = "";
  a.appendChild(img);
  a.appendChild(document.createTextNode(text));
  return a;
}

function enableAjaxQuote() {
  if(!document.getElementById("frmreply"))
    return;  // if there's no reply form on this page, i can't put the quote in the field
  links = document.getElementsByTagName("a");
  for(var i = 0; i < links.length; i++)
    if(links[i].className == "quote")
      links[i].onclick = replyQuoteClick;
}

function replyQuoteClick() {
  // get post id and make ajax call
  var pid = this.href.split("reply");
  if(pid.length != 2)
    return true;  // url is not the expected format, so just follow it
  pid = pid[1];
  getAsync("/hb/post.php?quote=" + pid, replyQuoteFinished);
  // go to reply form
  document.location = "#reply";
  return false;
}
function replyQuoteFinished(req, args) {
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
    alert("error(s) encountered quoting post:\n" + errors);
    return;
  }
  // put the quote in the post and subject fields
  for(var node = response.firstChild; node; node = node.nextSibling)
    switch(node.nodeName.toLowerCase()) {
      case "subject":
        var field = document.getElementsByName("subject");
        if(field.length)
          field[0].value = node.firstChild.nodeValue;
        break;
      case "quote":
        var field = document.getElementsByName("post");
        if(field.length)
          field[0].value = node.firstChild.nodeValue;
        break;
    }
    var field = document.getElementsByName("post");
    if(field.length)
      field[0].focus();
}
