<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for user profile view page                                 *
\******************************************************************************/

ul.actions {
  background-color: #<?=BGLIGHT; ?>;
  padding: .1em 0;
  margin: 1em 1.5em;
  line-height: 1.75em;
  -moz-border-radius: .5em;
  border-radius: .5em;
}
ul.actions li {
  padding: 0 .5em;
  display: inline;
  list-style-type: none;
}
ul.actions li a,
ul.actions li em {
  padding-left: 20px;
  background-position: left;
  background-repeat: no-repeat;
}
ul.actions li.edit a {
  background-image: url(/style/edit.png);
}
ul.actions li.del {
  float: right;
}
ul.actions li.del a {
  background-image: url(/style/del.png);
}
ul.actions li.pm a {
  background-image: url(/style/pm.png);
}
ul.actions li.addfriend a {
  background-image: url(/style/friend-add.png);
}
ul.actions li.delfriend a {
  background-image: url(/style/friend-del.png);
}

div#connect ul {
  background: none;
  margin: .85em 1.5em;
  padding: 0;
}
@media all and (min-width: 45em) {
  div#connect {
    float: right;
    width: 50%;
  }
  div#connect h2 {
    /*background-color: #<?=BGMEDIUM; ?>;*/
    -moz-border-radius-bottomleft: 0;
    -moz-border-radius-bottomright: 0;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    margin: 0 1.25em;
  }
  div#connect ul {
    margin-top: 0;
    background-color: #<?=HEADLIGHT; ?>;
    -moz-border-radius-topleft: 0;
    -moz-border-radius-topright: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    padding: .15em 0;
  }
}
ul.actions li.email a,
ul.actions li.email em {
  background-image: url(/images/contact/email.png);
}
ul.actions li.www a {
  background-image: url(/images/contact/www.png);
}
ul.actions li.xmpp a {
  background-image: url(/images/contact/xmpp.png);
}
ul.actions li.icq a {
  background-image: url(/images/contact/icq.png);
}
ul.actions li.aim a {
  background-image: url(/images/contact/aim.png);
}
ul.actions li.twitter a {
  background-image: url(/images/contact/twitter.png);
}
ul.actions li.steam a {
  background-image: url(/images/contact/steam.png);
}
ul.actions li.spore a {
  background-image: url(/images/contact/spore.png);
}

img#profileavatar {
  float: left;
  margin: .25em .5em .25em 2em;
}

table.list th,
table.list td {
  padding: 0;
}
table.list th {
  white-space: nowrap;
  font-weight: normal;
  font-size: .8em;
  padding-top: .2em;
  text-align: right;
  color: #<?=BGMEDIUM; ?>;
  padding-right: .5em;
  vertical-align: top;
}
table.list th:after {
  content: ":";
  color: #<?=TEXT;?>;
}
table#userprofile {
  margin-left: 2em;
  clear: left;
}
table#userprofile td.signature p {
  margin: 0;
  line-height: inherit;
}

div#rank ul {
  list-style-type: none;
  padding: 0;
}
div#rank ul li {
  line-height: 1.5em;
}
@media all and (min-width: 45em) {
  div#rank {
    margin: 1em 2em;
    float: left;
    clear: right;
  }
  div#rank h2 {
    margin: 0;
    /*background-color: #<?=BGMEDIUM; ?>;*/
    -moz-border-radius-bottomleft: 0;
    -moz-border-radius-bottomright: 0;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
  }
  div#rank ul {
    margin: 0;
    background-color: #<?=HEADLIGHT; ?>;
    -moz-border-radius-bottomleft: .5em;
    -moz-border-radius-bottomright: .5em;
    border-bottom-left-radius: .5em;
    border-bottom-right-radius: .5em;
    padding: .25em .5em;
  }
}

div#useractivity ol {
  list-style-type: none;
  line-height: 1.5em;
  padding: 0;
  margin-right: 2em;
}
div#useractivity li {
  background-position: left;
  background-repeat: no-repeat;
  padding-left: 20px;
}
div#useractivity li.post {
  background-image: url(/hb/favicon-16.png);
}
div#useractivity li.comment {
  background-image: url(/favicon-16.png);
}
div#useractivity li.round {
  background-image: url(/geek/favicon-16.png);
}
@media all and (min-width: 45em) {
  div#useractivity {
    float: left;
    /*margin-bottom: 1.5em;*/
  }
  div#useractivity h2 {
    margin-top:   .833em;
  }
}

ul#friends {
  margin: .5em 1.5em;
  padding: 0;
  text-align: center;
  list-style-type: none;
}
ul#friends li {
  display: inline-block;
  margin: 0.3em;
  padding: 0;
}
div.friend a.profile {
  display: block;
  padding: 5px 5px 0;
  margin-bottom: .5em;
}
div.friend a.profile img {
  display: block;
  margin: 0 auto .2em;
}
div.friend div.actions a img {
  vertical-align: middle;
}
