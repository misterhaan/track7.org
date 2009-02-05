window.onload = windowLoad;

function windowLoad() {
  // for photos that are actually videos, there's an object tag that needs an embed tag added inside it
  var photo = document.getElementById("photo");
  if(photo && photo.nodeName.toLowerCase() == "object") {
    var embed = document.createElement("embed");
    embed.setAttribute("type", "application/x-shockwave-flash");
    var params = photo.getElementsByTagName("param");
    for(var i = 0; i < params.length; i++)
      if(params[i].name == "movie")
        embed.setAttribute("src", params[i].value);
      else
        embed.setAttribute(params[i].name, params[i].value);
    photo.appendChild(embed);
  }
}
