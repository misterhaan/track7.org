addStartupFunction(checkMobile);
addStartupFunction(enhanceTypeField);
addStartupFunction(enhancePlayerFields);
addStartupFunction(enableAddPlayer);
addStartupFunction(enhanceScoreFields);

/**
 * Check if this page comes from the mobile site.  Determined based on whether
 * the score fields are select elements.
 */
function checkMobile() {
  document.mobile = document.getElementById("score0_0");
  if(document.mobile)
    document.mobile = (document.mobile.nodeName.toLowerCase() == "select");  // if scores are select elements, we're on the mobile site
}

/**
 * Show or hide partner name / field when the roundtype changes (for new rounds
 * only).
 */
function enhanceTypeField() {
  var type = document.getElementById("fldtype");
  var scoreset = document.getElementById("scoreset");
  if(type && scoreset && document.getElementById("frmnewround")) {
    var tbodies = scoreset.getElementsByTagName("tbody");
    if(tbodies.length > 0) {
      var rows = tbodies[0].getElementsByTagName("tr");
      if(rows.length > 1) {  // need second row because first one is par
        var cells = rows[1].getElementsByTagName("th");
        if(cells.length > 0) {
          var input = document.createElement("input");
          input.id = "fldpartner0";
          input.className = "string partner";
          input.type = "text";
          input.name = "partner[0]";
          input.size = 12;
          input.maxLength = 32;
          cells[0].appendChild(input);
          type.onchange = roundTypeChanged;
        }
      }
    }
  }
}

/**
 * Show or hide partner name / field when the roundtype changes.
 */
function roundTypeChanged() {
  var scoreset = document.getElementById("scoreset");
  if(scoreset) {
    if(this.options[this.selectedIndex].value == "doubles - best disc")
      scoreset.className = "partners";
    else
      scoreset.className = "";
  }
}

/**
 * To save space, show text instead of fields for player and partner fields when
 * not being edited.  This must be called after the partner field was created
 * by enhanceTypeField() so the partner field will work also.
 */
function enhancePlayerFields() {
  var player = document.getElementById("fldplayer0");
  var partner = document.getElementById("fldpartner0");
  if(player && document.getElementById("frmnewround")) {
    enableSuggest(player, "/user/list.php?return=suggest&match=");
    player.oldonblur = player.onblur;
    player.onblur = playerFieldLeft;
    if(partner) {
      enableSuggest(partner, "/user/list.php?return=suggest&match=");
      partner.oldonblur = partner.onblur;
      partner.onblur = playerFieldLeft;
    }
    var scoreset = document.getElementById("scoreset");
    if(scoreset) {
      var tbodies = scoreset.getElementsByTagName("tbody");
      if(tbodies.length > 1) {
        player.others = new Array();
        if(partner)
          partner.others = new Array();
        for(var t = 1; t < tbodies.length; t++) {
          var rows = tbodies[t].getElementsByTagName("tr");
          if(rows.length > 1) {
            var cells = rows[1].getElementsByTagName("th");
            if(cells.length > 0) {
              if(!cells[0].firstChild) {
                var span = document.createElement("span");
                span.className = "player";
                player.others[t - 1] = span;
                span.appendChild(document.createTextNode(player.value));
                cells[0].appendChild(span);
                span = document.createElement("span");
                span.className = "partner";
                if(partner) {
                  partner.others[t - 1] = span;
                  span.appendChild(document.createTextNode(partner.value));
                }
                cells[0].appendChild(span);
              }
            }
          }
        }
      }
    }
    player.onblur();
    if(partner)
      partner.onblur();
  }
}

/**
 * Hide the player field and show the value as text.
 */
function playerFieldLeft() {
  if(this.oldonblur)  // run the onblur from the suggest script
    this.oldonblur();
  if(!this.span) {
    this.span = document.createElement("span");
    this.span.input = this;
    this.span.onclick = editPlayer;
    if(this.className.indexOf("partner") > -1)
      this.span.className = "partner";
    this.span.style.display = "none";
    if(this.nextSibling)
      this.parentNode.insertBefore(this.span, this.nextSibling);
    else
      this.parentNode.appendChild(this.span);
  }
  scheduleHidePlayerField(this);  // need to delay hiding the field to give suggest clicks time to work
}
/**
 * Hide the player field and show its value as text.
 * @param field Player field to hide
 */
function scheduleHidePlayerField(field) {
  if(field.hidereq) {
    clearTimeout(field.hidereq);
    field.hidereq = false;
  }
  field.hidereq = setTimeout(function() { hidePlayerField(field); }, 150);
}
/**
 * Hide the player field and show its value as text.
 * @param field Player field to hide
 */
function hidePlayerField(field) {
  if(field.hidereq)
    field.hidereq = false;
  //if(field.value == "")
  //  return;  // don't hide the field if it's empty
  while(field.span.firstChild)
    field.span.removeChild(field.span.firstChild);
  field.span.appendChild(document.createTextNode(field.value || "(nobody)"));
  if(field.others)
    for(var s = 0; s < field.others.length; s++) {
      while(field.others[s].firstChild)
        field.others[s].removeChild(field.others[s].firstChild);
      field.others[s].appendChild(document.createTextNode(field.value));
    }
  field.style.display = "none";
  field.span.style.display = "";
}

/**
 * Show the player field again so it can be edited.
 */
function editPlayer() {
  if(this.input) {
    this.style.display = "none";
    this.input.style.display = "";
    this.input.focus();
  }
}

/**
 * Add a button under the player field to add scores for more players.
 */
function enableAddPlayer() {
  var scoreset = document.getElementById("scoreset");
  if(scoreset && document.getElementById("frmnewround")) {
    var tables = scoreset.getElementsByTagName("table");
    if(tables.length) {
      var tfoot = document.createElement("tfoot");
      tables[0].appendChild(tfoot);
      var row = document.createElement("tr");
      tfoot.appendChild(row);
      var cell = document.createElement("td");
      row.appendChild(cell);
      var btn = document.createElement("input");
      btn.type = "button";
      btn.value = "add";
      btn.title = "add scores for another player";
      cell.appendChild(btn);
      btn.onclick = addPlayer;
    }
  }
}

/**
 * Add rows to each score table for a new player.
 */
function addPlayer() {
  var scoreset = document.getElementById("scoreset");
  if(scoreset) {
    if(scoreset.extraPlayers)
      scoreset.extraPlayers++;
    else
      scoreset.extraPlayers = 1;
    var tbodies = scoreset.getElementsByTagName("tbody");
    var player = false;
    var partner = false;
    var otherIndex = 0;
    var total = document.createElement("th");
    for(var t = 0; t < tbodies.length; t++) {
      var row = document.createElement("tr");
      tbodies[t].appendChild(row);
      row.total = total;
      if(tbodies.length > 1) {  // if more than one table, add nine-hole cell
        var nine = document.createElement("th");
        row.nine = nine;
      }
      var cell = document.createElement("th");
      row.appendChild(cell);
      cell.className = "player";
      if(player) {
        // not the first table, so create span elements and save them in player and partner
        var span = document.createElement("span");
        span.className = "player";
        player.others[otherIndex] = span;
        cell.appendChild(span);
        span = document.createElement("span");
        span.className = "partner";
        partner.others[otherIndex] = span;
        cell.appendChild(span);
        otherIndex++;
      } else {
        // first row, so create player and partner fields
        player = document.createElement("input");
        player.className = "string";
        player.type = "text";
        player.name = "player[" + scoreset.extraPlayers + "]";
        player.size = 12;
        player.maxLength = 32;
        cell.appendChild(player);
        player.others = new Array();
        enableSuggest(player, "/user/list.php?return=suggest&match=");
        player.onblur = playerFieldLeft;
        player.focus();
        partner = document.createElement("input");
        partner.className = "string partner";
        partner.type = "text";
        partner.name = "partner[" + scoreset.extraPlayers + "]";
        partner.size = 12;
        partner.maxLength = 32;
        cell.appendChild(partner);
        partner.others = new Array();
        enableSuggest(partner, "/user/list.php?return=suggest&match=");
        partner.onblur = playerFieldLeft;
      }
      for(var h = t * 9; h < t * 9 + 9; h++) {
        cell = document.createElement("td");
        row.appendChild(cell);
        if(document.mobile) {
          var score = document.createElement("select");
          score.name = "score[" + scoreset.extraPlayers + "][" + h + "]";
          for(var s = 1; s <= 9; s++) {
            option = document.createElement("option");
            if(s == 3)
              option.selected = "selected";
            option.appendChild(document.createTextNode(s));
            score.appendChild(option);
          }
          cell.appendChild(score);
          var span = document.createElement("span");
          span.appendChild(document.createTextNode(0));
          span.style.display = "none";
          cell.appendChild(span);
          score.updateScore = updateScore;
          if(h < scoreset.hole) {
            score.updateScore();
            span.style.display = "";
            score.style.display = "none";
          } else if(h > scoreset.hole)
            score.style.display = "none";
        } else {
          var score = document.createElement("input");
          score.className = "integer";
          score.type = "text";
          score.name = "score[" + scoreset.extraPlayers + "][" + h + "]";
          score.size = 1;
          score.maxLength = 1;
          score.value = 3;
          cell.appendChild(score);
          var span = document.createElement("span");
          span.appendChild(document.createTextNode(0));
          span.style.display = "none";
          cell.appendChild(span);
          score.onblur = updateScore;
          score.onblur();
        }
      }
      if(row.nine)  // if row has a nine-hole total cell, add it
        row.appendChild(row.nine);
      if(t == tbodies.length - 1)  // if last table, add total cell
        row.appendChild(total);
    }
  }
}

/**
 * Update totals when score fields are changed, hide future holes' score fields
 * for mobile site.
 */
function enhanceScoreFields() {
  var scoreset = document.getElementById("scoreset");
  var newgame = document.getElementById("frmnewround");
  if(scoreset) {
    scoreset.hole = 0;
    var tbodies = scoreset.getElementsByTagName("tbody");
    var total = tbodies[tbodies.length - 1].getElementsByTagName("th");
    total = total[total.length - 1];
    for(var t = 0; t < tbodies.length; t++) {
      var rows = tbodies[t].getElementsByTagName("tr");
      if(rows.length > 1) {
        var row = rows[1];  // skip first row since it's par
        if(tbodies.length > 1) {
          var nine = row.getElementsByTagName("th");
          row.nine = nine[1];  // second th since the first one is player
        }
        row.total = total;
        var cells = row.getElementsByTagName("td");
        for(var c = 0; c < cells.length; c++) {
          var fld = cells[c].firstChild;
          var span = document.createElement("span");
          span.style.display = "none";
          cells[c].appendChild(span);
          if(document.mobile)
            if(newgame)
              span.appendChild(document.createTextNode(0));
            else {
              span.appendChild(document.createTextNode(fld.options[fld.selectedIndex].value));
              addScore(row.nine, row.total, +fld.options[fld.selectedIndex].value);
            }
          else {
            span.appendChild(document.createTextNode(fld.value));
            addScore(row.nine, row.total, +fld.value);
          }
          if(newgame && document.mobile) {
            if(t > 0 || c > 0)
              fld.style.display = "none";
            fld.updateScore = updateScore;
          } else
            fld.onblur = updateScore;
        }
      }
      if(newgame && document.mobile) {
        var tfoot = tbodies[t].nextSibling;
        var frow = false;
        if(!tfoot) {  // first table will have tfoot for add player button
          tfoot = document.createElement("tfoot");
          tbodies[t].parentNode.appendChild(tfoot);
          frow = document.createElement("tr");
          tfoot.appendChild(frow);
          frow.appendChild(document.createElement("td"));
          frow.appendChild(document.createElement("td"));
        } else {
          var cell = document.createElement("td");
          frow = tfoot.firstChild;
          frow.appendChild(cell);
          var btn = document.createElement("input");
          btn.id = "btnnext";
          btn.type = "button";
          btn.value = "→";
          btn.title = "accept scores for this hole and move on to the next";
          cell.appendChild(btn);
          btn.onclick = nextHole;
          var submit = document.getElementById("frmnewround");
          if(submit) {
            submit = submit.getElementsByTagName("input");
            if(submit.length)
              submit[submit.length - 1].disabled = "disabled";
          }
        }
        for(var h = 1; h < 9; h++)
          frow.appendChild(document.createElement("td"));
      }
    }
  }
}

/**
 * Update totals when a score field changes.
 */
function updateScore() {
  if(this.nextSibling) {
    var oldvalue = +this.nextSibling.firstChild.data;
    var newvalue = +(this.nodeName == "select" ? this.options[this.selectedIndex].value : this.value);
    this.nextSibling.replaceChild(document.createTextNode(newvalue), this.nextSibling.firstChild);
    // two .parentNodes for cell then row
    addScore(this.parentNode.parentNode.nine, this.parentNode.parentNode.total, newvalue - oldvalue);
  }
}

/**
 * Add score to nine-hole total and overall total.
 * @param nine Cell holding the nine-hole total
 * @param total Cell holding the overall total
 * @param score Score to add (may be negative if score was changed from 3 to 2)
 */
function addScore(nine, total, score) {
  if(nine)
    if(nine.firstChild)
      nine.firstChild.data = +nine.firstChild.data + score;
    else
      nine.appendChild(document.createTextNode(score));
  if(total)
    if(total.firstChild)
      total.firstChild.data = +total.firstChild.data + score;
    else
      total.appendChild(document.createTextNode(score));
}

/**
 * Update totals based on current scores and move on to the next.
 */
function nextHole() {
  var scoreset = document.getElementById("scoreset");
  if(scoreset) {
    var c0 = scoreset.hole % 9;
    // input.td.tr.tfoot.tbody
    var tbody = this.parentNode.parentNode.parentNode.previousSibling;
    var rows = tbody.getElementsByTagName("tr");
    var c1 = c0 + 1;
    var nextRows = rows;
    if(c1 >= 9)
      if(tbody.parentNode.nextSibling && tbody.parentNode.nextSibling.nodeName == "table") {
        nextRows = tbody.parentNode.nextSibling.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
        c1 = 0;
      }
      else
        nextRows = false;
    for(var r = 1; r < rows.length; r++) {  // skip first row since it's par
      var cells = rows[r].getElementsByTagName("td");
      cells[c0].firstChild.updateScore();
      cells[c0].firstChild.style.display = "none";
      cells[c0].lastChild.style.display = "";
      if(nextRows) {
        cells = nextRows[r].getElementsByTagName("td");
        cells[c1].firstChild.style.display = "";
        if(r == 1)
          cells[c1].firstChild.focus();
      }
    }
    scoreset.hole++;
    if(nextRows) {  // move next button if there are more holes
      var tfoot = nextRows[0].parentNode.nextSibling;
      var cells = tfoot.getElementsByTagName("td");
      cells[c1 + 1].appendChild(this);
    } else {  // enable save button after last hole
      var submit = document.getElementById("frmnewround");
      if(submit) {
        submit = submit.getElementsByTagName("input");
        if(submit.length)
          submit[submit.length - 1].disabled = "";
      }
      var best = document.getElementById("fldbestdisc");
      if(best)
        best.focus();
      this.style.display = "none";
    }
  }
}
