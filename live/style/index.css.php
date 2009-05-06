<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for main index page                                        *
\******************************************************************************/

div#features {
  float: right;
  width: 350px;
  background-color: #<?=BGLIGHT; ?>;
  margin: 0 1em;
  padding: .5em;
  border: 1px solid #<?=BGMEDIUM; ?>;
  -webkit-border-radius: .75em;
  -moz-border-radius: .75em;
  border-radius: .75em;
}

div#features dl {
  margin: 0;
  padding: 0;
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

div.feed p {
  margin-left: 7.5em;
}

div#content p.links {
  font-size: .8em;
  text-align: center;
}

table#updates th {
  font-size: .8em;
  white-space: nowrap;
}
table#updates td {
  vertical-align: top;
  font-size: .9em;
}
table#updates td.type {
  padding-right: 0;
}
table#updates img {
  display: block;
  width: 16px;
  height: 16px;
}
