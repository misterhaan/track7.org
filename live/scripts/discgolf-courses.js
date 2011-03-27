addStartupFunction(allowLocationSort);
addStartupFunction(enhanceParFields);
addStartupFunction(enhanceCourseLocation);

/**
 * Add a link for sorting courses by distance if showing a list of courses,
 * geolocation is available, and the list isn't already sorted by distance.
 */
function allowLocationSort() {
  if(navigator.geolocation && location.search.indexOf('coursesort=distance') == -1) {
    var coursesort = document.getElementById("coursesort");
    if(coursesort) {
      var li = document.createElement("li");
      coursesort.appendChild(li);
      var a = document.createElement("a");
      a.id = "sortdistance";
      li.insertBefore(a, li.firstChild);
      if(location.search.indexOf("addround") > -1)
        a.href = "?addround&coursesort=distance";
      else
        a.href = "?coursesort=distance";
      a.appendChild(document.createTextNode("distance"));
      a.onclick = sortByDistance;
    }
  }
}

/**
 * Attempt to get geolocation for sorting course list by distance.
 * @return False to cancel click event.
 */
function sortByDistance() {
  navigator.geolocation.getCurrentPosition(sortByDistanceFinished, sortByDistanceError);
  return false;
}
/**
 * Success handler for geolocation.getCurrentPosition for course list sort by distance.
 * Send current geolocation to server for sorting course list by distance.
 * @param pos Current position object.
 */
function sortByDistanceFinished(pos) {
  var sortlink = document.getElementById("sortdistance");
  if(sortlink)
    location.href = sortlink.href + "&lat=" + pos.coords.latitude + "&lon=" + pos.coords.longitude;
}
/**
 * Error handler for geolocation.getCurrentPosition for course list sort by distance.
 * Display a message and remove distance sort link if unable to get location.
 * @param err Position error object.
 */
function sortByDistanceError(err) {
  if(err.code == 1)  // permission denied means user said not to allow location, so don't remove the link
    return;
  alert("unable to determine current location — sort by distance unavailable.");
  var sortlink = document.getElementById("sortdistance");
  if(sortlink && sortlink.parentNode && sortlink.parentNode.parentNode)
    sortlink.parentNode.parentNode.removeChild(sortlink.parentNode);
}

/**
 * Set the number of holes field to show / hide par fields so the correct number
 * of holes are shown.  Also initially hides the appropriate par fields.
 */
function enhanceParFields() {
  var holes = document.getElementById("fldholes");
  if(holes) {
    var partable = document.getElementById("parfields");
    if(partable) {
      holes.holesShown = 27;
      holes.partable = partable;
      holes.onchange = holesChanged;
      holes.onchange();
    }
  }
}

/**
 * Show / hide par fields based on the number of holes chosen.
 */
function holesChanged() {
  var val = +this.value || +this.options[this.selectedIndex].value;
  if(val < this.holesShown) {
    var rows = this.partable.getElementsByTagName("tr");
    for(var r = 0; r < rows.length; r++)
      if(+rows[r].className.substring(7) > val)
        rows[r].style.display = "none";
    this.holesShown = val;
  } else if(val > this.holesShown) {
    var rows = this.partable.getElementsByTagName("tr");
    for(var r = 0; r < rows.length; r++)
      if(+rows[r].className.substring(7) <= val)
        rows[r].style.display = "";
    this.holesShown = val;
  }
}

/**
 * If showing a course form and geolocation supported, add a button for filling
 * in latitude and longitude from the current location.
 */
function enhanceCourseLocation() {
  var latfld = document.getElementById("fldlatitude");
  var lonfld = document.getElementById("fldlongitude");
  if(navigator.geolocation && latfld && lonfld
      && latfld.parentNode && latfld.parentNode.parentNode && latfld.parentNode.parentNode.parentNode) {
    var latrow = latfld.parentNode.parentNode;
    var btnrow = document.createElement("tr");
    latrow.parentNode.insertBefore(btnrow, latrow);
    var btncell = document.createElement("th");
    btnrow.appendChild(btncell);
    btncell.appendChild(document.createTextNode("geolocation"));
    btncell = document.createElement("td");
    btnrow.appendChild(btncell);
    var btn = document.createElement("input");
    btn.id = "getcourseloc";
    btn.type = "button";
    btn.value = "get current location";
    btn.latfld = latfld;
    btn.lonfld = lonfld;
    btn.row = btnrow;
    btn.onclick = getCourseLocation;
    btncell.appendChild(btn);
  }
}

/**
 * Request geolocation to fill in latitude and longitude fields.
 * @return False so click event is canceled.
 */
function getCourseLocation() {
  navigator.geolocation.getCurrentPosition(getCourseLocationFinished, getCourseLocationError);
  return false;
}
/**
 * Success event handler for course location lookup geolocation.getCurrentPosition
 * Fill in latitude and longitude fields from current location.
 * @param pos Current position object.
 */
function getCourseLocationFinished(pos) {
  var btn = document.getElementById("getcourseloc");
  if(btn && btn.latfld && btn.lonfld) {
    btn.latfld.value = pos.coords.latitude;
    btn.lonfld.value = pos.coords.longitude;
  }
}
function getCourseLocationError(err) {
  if(err.code == 1)  // permission denied means user said not to allow location, so don't remove the button
    return;
  alert("unable to determine current location.");
  var btn = document.getElementById("getcourseloc");
  if(btn && btn.row && btn.row.parentNode)
    btn.row.parentNode.removeChild(btn.row);
}