addStartupFunction(enhanceAddRoundLink);

/**
 * If geolocation supported, ask for location when clicking the link to add a round.
 */
function enhanceAddRoundLink() {
  var lnk = document.getElementById("addroundlink");
  if(lnk && navigator.geolocation)
    lnk.onclick = nearestCourses;
}

/**
 * Look up location when clicking the add round link.
 * @return false always, to avoid following the link
 */
function nearestCourses() {
  navigator.geolocation.getCurrentPosition(nearestCoursesFinished, nearestCoursesError);
  return false;
}

/**
 * Got a location, so go to courses page sorted by distance.
 * @param pos Current position object.
 */
function nearestCoursesFinished(pos) {
  var lnk = document.getElementById("addroundlink");
  location.href = lnk.href + "&coursesort=distance&lat=" + pos.coords.latitude + "&lon=" + pos.coords.longitude;
}

/**
 * Couldn't get position (maybe user denied access), so go to courses page
 * sorted by default.
 * @param err Position error object (not used)
 */
function nearestCoursesError(err) {
  var lnk = document.getElementById("addroundlink");
  location.href = lnk.href;
}
