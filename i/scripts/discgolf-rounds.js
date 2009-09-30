addStartupFunction(enableRoundSetup);

function enableRoundSetup() {
  var submit = document.getElementById("sharedinfosubmit");
  if(submit)
    submit.onclick = handleRoundSetup;
}

function handleRoundSetup() {
  var fieldset = document.getElementById("sharedinfo");
  if(fieldset) {
    var submit = document.getElementById("sharedinfosubmit");
    if(submit)
      submit.parentNode.parentNode.parentNode.removeChild(submit.parentNode.parentNode);
    var flddate = document.getElementById("flddate");
    if(flddate) {
      var id = flddate.id;
      var name = flddate.name;
      var value = flddate.value;
      if(value)
        flddate.parentNode.replaceChild(document.createTextNode(value), flddate);
      else
        flddate.parentNode.replaceChild(document.createTextNode("today"), flddate);
      var input = document.createElement("input");
      input.type = "hidden";
      input.id = id;
      input.name = name;
      input.value = value;
      fieldset.appendChild(input);
    }
    var fldtype = document.getElementById("fldtype");
    if(fldtype) {
      var id = fldtype.id;
      var name = fldtype.name;
      var value = fldtype.value;
      if(value != "null")
        fldtype.parentNode.replaceChild(document.createTextNode(value), fldtype);
      else
        fldtype.parentNode.replaceChild(document.createTextNode(""), fldtype);
      var input = document.createElement("input");
      input.type = "hidden";
      input.id = id;
      input.name = name;
      input.value = value;
      fieldset.appendChild(input);
    }
    var fldtees = document.getElementById("fldtees");
    if(fldtees) {
      var id = fldtees.id;
      var name = fldtees.name;
      var value = fldtees.value;
      if(value != "null")
        fldtees.parentNode.replaceChild(document.createTextNode(value), fldtees);
      else
        fldtees.parentNode.replaceChild(document.createTextNode(""), fldtees);
      var input = document.createElement("input");
      input.type = "hidden";
      input.id = id;
      input.name = name;
      input.value = value;
      fieldset.appendChild(input);
    }
    var fldplayers = document.getElementById("fldplayers");
    if(fldplayers) {
      var value = fldplayers.value;
      fldplayers.parentNode.replaceChild(document.createTextNode(value), fldplayers);
      initializePlayerScoreTable(+value);
    }
    return false;
  }
}

function initializePlayerScoreTable(players) {
  var scorelist = document.getElementById("scorelist");
  if(scorelist) {
    var table = scorelist.getElementsByTagName("table");
    if(table && table.length) {
      table = table[0];
      var holes = table.getElementsByTagName("thead")[0].getElementsByTagName("tr")[0].getElementsByTagName("th").length - 1;
      var tbody = document.createElement("tbody");
      table.appendChild(tbody);
      for(var p = 0; p < players; p++) {
        var tr = document.createElement("tr");
        tbody.appendChild(tr);
        var th = document.createElement("th");
        tr.appendChild(th);
        var input = document.createElement("input");
        th.appendChild(input);
        input.id = "name" + p;
        if(p == 0) {
          var liu = document.getElementById("loggedinuser");
          if(liu && liu.firstChild)
            input.value = liu.firstChild.nodeValue;
        }
        for(var h = 0; h < holes; h++)
          tr.appendChild(document.createElement("td"));
        var td = document.createElement("th");
        td.className = "total";
        tr.appendChild(td);
        tr.totalcell = td;
      }
      var tfoot = document.createElement("tfoot");
      table.appendChild(tfoot);
      var row = document.createElement("tr");
      tfoot.appendChild(row);
      var cell = document.createElement("td");
      cell.colSpan = 2;
      row.appendChild(cell);
      var submit = document.createElement("input");
      submit.type = "submit";
      submit.value = "next";
      submit.onclick = savePlayerNames;
      submit.tbody = tbody;
      cell.appendChild(submit);
      var name0 = document.getElementById("name0");
      if(name0) {
        name0.focus();
        name0.select();  // this doesn't seem to work
      }
    }
  }
}

function savePlayerNames() {
  var tbody = this.tbody;
  var rows = tbody.getElementsByTagName("tr");
  var focus = false;
  for(var r = 0; r < rows.length; r++) {
    var cell = rows[r].firstChild;
    var input = cell.firstChild;
    var name = input.value;
    cell.replaceChild(document.createTextNode(name), input);
    cell = cell.nextSibling;
    var select = document.createElement("select");
    cell.appendChild(select);
    for(var v = 1; v <= 9; v++) {
      var option = document.createElement("option");
      select.appendChild(option);
      option.appendChild(document.createTextNode(v));
      if(v == 3)
        option.selected = true;
    }
    if(r == 0)
      focus = select;
  }
  this.parentNode.parentNode.insertBefore(document.createElement("td"), this.parentNode);
  this.onclick = saveScores;
  this.hole = 1;
  if(focus)
    focus.focus();
  return false;
}

function saveScores() {
  var tbody = this.tbody;
  var rows = tbody.getElementsByTagName("tr");
  var focus = false;
  for(var r = 0; r < rows.length; r++) {
    var cell = rows[r].firstChild;  // this gets the name cell, so count over to the current hole
    for(var c = 0; c < this.hole; c++)
      cell = cell.nextSibling;
    var score = cell.firstChild.value;
    cell.replaceChild(document.createTextNode(score), cell.firstChild);
    var total = rows[r].totalcell;
    if(total.firstChild)
      total.firstChild.nodeValue = parseInt(score) + parseInt(total.firstChild.nodeValue);
    else
      total.appendChild(document.createTextNode(score));
    cell = cell.nextSibling;
    if(cell != total) {
      var select = document.createElement("select");
      cell.appendChild(select);
      for(var v = 1; v <= 9; v++) {
        var option = document.createElement("option");
        select.appendChild(option);
        option.appendChild(document.createTextNode(v));
        if(v == 3)
          option.selected = true;
      }
      if(r == 0)
        focus = select;
    }
  }
  if(focus) {
    this.parentNode.parentNode.insertBefore(document.createElement("td"), this.parentNode);
    this.onclick = saveScores;
    this.hole++;
    focus.focus();
  } else {
    // all scores entered, so show notes field with save button
    var form = this.form;
    var tfoot = this.parentNode.parentNode.parentNode;
    tfoot.parentNode.removeChild(tfoot);
    var fieldset = document.createElement("fieldset");
    fieldset.id = "roundnotes";
    form.appendChild(fieldset);
    var label = document.createElement("label");
    label.htmlFor = "fldnotes";
    fieldset.appendChild(label);
    label.appendChild(document.createTextNode("notes on this round:"))
    var textarea = document.createElement("textarea");
    fieldset.appendChild(textarea);
    textarea.rows = 4;
    textarea.cols = 41;
    var submit = document.createElement("input");
    submit.type = "submit";
    fieldset.appendChild(submit);
    submit.value = "submit scores";
    submit.onclick = submitScores;
    textarea.focus();
  }
  return false;
}

function submitScores() {
  this.disabled = true;
  var params = new Array();
  var p = 0;
  var sharedinfo = document.getElementById("sharedinfo");
  if(sharedinfo) {
    var inputs = sharedinfo.getElementsByTagName("input");
    for(var i = 0; i < inputs.length; i++)
      params[p++] = uriParam(inputs[i].name, inputs[i].value);
  }
  var scores = document.getElementById("scorelist");
  if(scores) {
    var table = scores.getElementsByTagName("table")[0];
    var tbody = table.getElementsByTagName("tbody")[0];
    var rows = tbody.getElementsByTagName("tr");
    for(var r = 0; r < rows.length; r++) {
      params[p++] = uriParam("player[" + r + "]", rows[r].firstChild.firstChild.nodeValue);
      var cells = rows[r].getElementsByTagName("td");
      var sc = new Array();
      var s = 0;
      for(var c = 0; c < cells.length; c++)
        sc[s++] = cells[c].firstChild.nodeValue;
      params[p++] = uriParam("scores[" + r + "]", sc.join("|"));
    }
  }
  var textarea = this.form.getElementsByTagName("textarea");
  if(textarea.length) {
    textarea = textarea[0];
    params[p++] = uriParam("notes", textarea.value);
  }
  params[p++] = uriParam("return", "xml");
  postAsync(this.form.action, submitScoresFinished, this, params);
  return false;
}
function submitScoresFinished(req, submit) {
  if(!req.responseXML || !req.responseXML.documentElement) {
    alert("Error:\n" + req.responseText);
    return;
  }
  var response = req.responseXML.documentElement;
  for(var node = response.firstChild; node; node = node.nextSibling)
    switch(node.nodeName) {
      case "rounds":
        handleSavedRounds(node);
        break;
      case "course":
        checkCourseUpdate(node);
        break;
      case "errors":
        showSubmitErrors(node);
        break;
    }
  submit.disabled = false;
}

function handleSavedRounds(rounds) {
  var scorelist = document.getElementById("scorelist");
  if(scorelist) {
    var tbody = scorelist.getElementsByTagName("tbody");
    if(tbody.length) {
      tbody = tbody[0];
      var scorerows = tbody.getElementsByTagName("tr");
      var list = scorelist.getElementsByTagName("ul");
      if(list.length)
        list = list[0];
      else {
        list = document.createElement("ul");
        scorelist.appendChild(list);
      }
      var removerows = new Array();
      var r = 0;
      for(var round = rounds.firstChild; round; round = round.nextSibling)
        if(round.nodeName == "round") {
          var index = round.attributes.getNamedItem("index").value;
          var result = round.getElementsByTagName("result")[0].firstChild.nodeValue;
          var li = document.createElement("li");
          list.appendChild(li);
          if(result == "success") {
            li.className = "success";
            var a = document.createElement("a");
            li.appendChild(a);
            a.href = "http://www.track7.org/geek/discgolf/rounds.php?id=" + round.getElementsByTagName("id")[0].firstChild.nodeValue;
            a.appendChild(document.createTextNode(scorerows[index].firstChild.firstChild.nodeValue + "’s scores"));
            li.appendChild(document.createTextNode(" saved successfully"));
            removerows[r++] = scorerows[index];
          } else {
            li.className = "error";
            li.appendChild(document.createTextNode("error saving " + scorerows[index].firstChild.firstChild.nodeValue + "’s scores"));
          }
        }
      for(var rr = 0; rr < r; rr++)
        removerows[rr].parentNode.removeChild(removerows[rr]);
    }
  }
}

function checkCourseUpdate(course) {
  // if there's a problem with updating course stats, there should be an error which the next function will display
}

function showSubmitErrors(errors) {
  var roundnotes = document.getElementById("roundnotes");
  if(roundnotes) {
    for(var error = errors.firstChild; error; error = error.nextSibling)
      if(error.nodeName == "error") {
        var p = document.createElement("p");
        roundnotes.parentNode.insertBefore(p, roundnotes);
        p.className = "error";
        var errorText = error.firstChild.nodeValue;
        var loginpos = errorText.indexOf("log in using a new page");
        if(loginpos > 0) {
          p.appendChild(document.createTextNode(errorText.substring(0, loginpos)));
          var a = document.createElement("a");
          p.appendChild(a);
          a.href = "/user/login.php";
          a.target = "_blank";
          a.appendChild(document.createTextNode("log in using a new page"));
          p.appendChild(document.createTextNode(errorText.substring(loginpos + "log in using a new page".length)));
        } else
          p.appendChild(document.createTextNode(errorText));
      }
  }
}
