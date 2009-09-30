addStartupFunction(enableLocationForDiscGolf);

function enableLocationForDiscGolf() {
  var link = document.getElementById("discgolflink");
  if(link)
    link.onclick = getLocationForDiscGolf;
}

function getLocationForDiscGolf() {
  if(navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(gpsDiscGolf);
    return false;
  }
}

function gpsDiscGolf(pos) {
  link = document.getElementById("discgolflink");
  if(link)
    document.location = link.href + "?lat=" + pos.coords.latitude + "&lon=" + pos.coords.longitude;
}