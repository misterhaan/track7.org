addStartupFunction(enableFeatureCollapse);

function enableFeatureCollapse() {
  var feat = document.getElementById("features");
  if(feat) {
    feat = feat.getElementsByTagName("h2");
    if(feat.length) {
      feat = feat[0];
      var link = document.createElement("a");
      link.appendChild(document.createTextNode("hide"));
      link.onclick = collapseFeatures;
      feat.appendChild(link);
    }
  }
}

function collapseFeatures() {
  var feat = document.getElementById("features");
  if(feat) {
    feat.className = "collapsed";
    this.onclick = expandFeatures;
    while(this.firstChild)
      this.removeChild(this.firstChild);
    this.appendChild(document.createTextNode("show"));
  }
}

function expandFeatures() {
  var feat = document.getElementById("features");
  if(feat) {
    feat.className = "";
    this.onclick = collapseFeatures;
    while(this.firstChild)
      this.removeChild(this.firstChild);
    this.appendChild(document.createTextNode("hide"));
  }
}