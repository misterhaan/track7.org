<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for main index page                                        *
\******************************************************************************/

div#title {
  display: none;
}

div#what h2,
div.feed h2 {
  background: none;
}

div#features {
  background-color: #<?=BGLIGHT; ?>;
  margin: 1em 2em;
  padding: 0;
  float: right;
  width: 28em;
  -webkit-border-radius: .75em;
  -moz-border-radius: .75em;
  border-radius: .75em;
}
@media all and (max-width: 50em) {
  div#features {
    float: none;
    width: auto;
    margin: 1em;
  }
}
div#features.collapsed {
  width: auto;
}
div#features h2 {
  color: #000000;
  text-align: center;
  background-color: #<?=BGMEDIUM; ?>;
  -webkit-border-radius: .5em;
  -webkit-border-bottom-left-radius: 0;
  -webkit-border-bottom-right-radius: 0;
  -moz-border-radius: .5em;
  -moz-border-radius-bottomleft: 0;
  -moz-border-radius-bottomright: 0;
  border-radius: .5em;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin: 0;
}
div#features.collapsed h2 {
  -webkit-border-radius: .4em;
  -moz-border-radius: .4em;
  border-radius: .4em;
}

div#features h2 a {
  cursor: pointer;
  font-size: .7em;
  float: right;
  font-weight: normal;
  margin-left: 1em;
  margin-top: .25em;
}
div#features h2 a:before {
  content: "[ ";
}
div#features h2 a:after {
  content: " ]";
}

div#features dl {
  margin: 0;
  padding: .6em;
}
div#features.collapsed dl {
  display: none;
}
div#features dt {
  margin: 0;
  padding: 0;
  background-image: none;
  clear: left;
}
div#features dt img.icon {
  padding-left: 0;
  margin-top: .1em;
  margin-right: .5em;
}
div#features dd {
  margin-left: 32px;
  padding-left: .5em;
}

div#what h2 {
  clear: left;
  color:  #<?=TEXT; ?>;
}

div.feed {
  clear: left;
  margin: 1.5em 0;
  min-height: 72px;
}

div.typedate {
  clear: left;
  float: left;
  margin: 0 .5em .5em 2em;
  padding: 54px 0 0;
  text-align: center;
  width: 60px;
  background: #<?=BGMEDIUM; ?> no-repeat 6px 6px;
  -khtml-border-radius: 8px;
  -webkit-border-radius: 8px;
  -moz-border-radius: 8px;
  border-radius: 8px;
}
div.typedate div.date {
  font-size: .8em;
  line-height: 1.5em;
  color:  #000000;
}
div.update div.typedate {
  background-image: url(/images/storytype/update.png);
}
div.post div.typedate {
  background-image: url(/images/storytype/post.png);
}
div.comment div.typedate {
  background-image: url(/images/storytype/comment.png);
}
div.entry div.typedate {
  background-image: url(/images/storytype/entry.png);
}
div.photo div.typedate {
  background-image: url(/images/storytype/photo.png);
}
div.guide div.typedate {
  background-image: url(/images/storytype/guide.png);
}
div.art div.typedate {
  background-image: url(/images/storytype/art.png);
}
div.round div.typedate {
  background-image: url(/images/storytype/round.png);
}

img.photothumb {
  border: 1px solid #000000;
  vertical-align: bottom;
}

h2.feed {
  clear: none;
  font-size: 1.25em;
  margin: 1em 1.6em .2em;
  color:  #<?=TEXT; ?>;
}
h2.feed a.feed {
  display: none;
  margin-left: .75em;
  background-color: transparent;
}
h2.feed a.feed img {
  vertical-align: middle;
}

div.feed p,
div.feed samp,
div.feed ul,
div.feed ol {
  margin: .5em 7.5em;
}
@media all and (max-width: 45em) {
  div.feed p,
  div.feed samp,
  div.feed ul,
  div.feed ol {
    margin-left: 2em;
    margin-right: 2em;
  }
}
div.feed p.tags {
  background: url(/style/tag.png) no-repeat left center;
  min-height: 16px;
  padding-left: 20px;
  margin-top: .2em;
}
div#content p.tags a {
  font-weight: normal;
}
div#content p.readmore {
  padding-left: 2em;
}
div#content p.readmore a {
  font-weight: normal;
  font-style: italic;
}

div#content p.links {
  font-size: .8em;
  text-align: center;
}
