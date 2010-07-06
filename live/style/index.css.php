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

div#features {
  background-color: #<?=BGLIGHT; ?>;
  margin: 0 1em;
  padding: 0;
  float: right;
  width: 28em;
  border: 1px solid #<?=BGMEDIUM; ?>;
  -webkit-border-radius: .75em;
  -moz-border-radius: .75em;
  border-radius: .75em;
}
@media all and (max-width: 50em) {
  div#features {
    float: none;
    width: auto;
  }
}
div#features h2 {
  color: #000000;
  text-align: center;
  background-color: #<?=BGMEDIUM; ?>;
  -webkit-border-radius: .3em;
  -webkit-border-bottom-left-radius: 0;
  -webkit-border-bottom-right-radius: 0;
  -moz-border-radius: .3em;
  -moz-border-radius-bottomleft: 0;
  -moz-border-radius-bottomright: 0;
  border-radius: .3em;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin: 0;
}

div#features dl {
  margin: 0;
  padding: .5em;
}
div#features dt {
  margin: 0;
  padding: 0;
  background-image: none;
  clear: left;
}
div#features dt img.icon {
  padding-left: 0;
  margin-right: .5em;
}
div#features dd {
  margin-left: 32px;
  padding-left: .5em;
}

div.feed {
  clear: left;
  margin: 1em 0;
}

div.typedate {
  clear: left;
  float: left;
  margin: 0 .5em .5em 2em;
  padding: 54px 0 0;
  text-align: center;
  width: 60px;
  background: #<?=HEADMEDIUM; ?> no-repeat 6px 6px;
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

img.photothumb {
  border: 1px solid #000000;
  vertical-align: bottom;
}

h2.feed {
  clear: none;
  border: none;
  font-size: 1.25em;
  margin: 0 1.6em .2em;
  background: #<?=HEADMEDIUM; ?>;
  line-height: 1.4em;
  -khtml-border-radius: 8px;
  -webkit-border-radius: 8px;
  -moz-border-radius: 8px;
  border-radius: 8px;
}

h2.feed a.feed {
  float: right;
  display: block;
  margin-top: .1em;
  height: 1.25em;
  width: 1.25em;
  background: url(/style/feed.png) no-repeat center center;
}

div.feed p,
div.feed samp,
div.feed ul,
div.feed ol {
  margin-left: 7.5em;
}
@media all and (max-width: 45em) {
  div.feed p,
  div.feed samp,
  div.feed ul,
  div.feed ol {
    margin-left: 2em;
  }
}

div#content p.links {
  font-size: .8em;
  text-align: center;
}
