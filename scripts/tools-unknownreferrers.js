var refCheckReq = null;
window.onload = window_onLoad;

function window_onLoad() {
  // add link to check referrer
  var ref = document.getElementById("referrer");
  var a = document.createElement("a");
  a.setAttribute("href", "#");
  a.setAttribute("title", "search for highlighted text in known referrers");
  a.onclick = function() { return checkReferrer(); };
  a.appendChild(document.createTextNode("?"));
  ref.appendChild(a);
}

function checkReferrer() {
  var ref = getSelectedText(document.getElementById("referrer"));
  if(ref == false) {
    alert("select text to search for from within the referrer heading");
    return;
  }
  var div = document.getElementById("refcheckresults");
  // clear the area
  while(div.firstChild)
    div.removeChild(div.firstChild);
  // create the xhtml for the message and add it to the area
  var p = document.createElement("p");
  p.className = "info";
  var msg = document.createTextNode("checking referrer...");
  p.appendChild(msg);
  div.appendChild(p);
  // start the asynchronous server request
  refCheckReq = getAsync("/scripts/tools/unknownreferrers.php?ref=" + ref, refCheckFinished);
}

function refCheckFinished() {
  if(refCheckReq && (refCheckReq.readyState == 4 || refCheckReq.readyState == "complete")) {
    var div = document.getElementById("refcheckresults");
    // clear the area
    while(div.firstChild)
      div.removeChild(div.firstChild);
    div.innerHTML = refCheckReq.responseText;
  }
}
