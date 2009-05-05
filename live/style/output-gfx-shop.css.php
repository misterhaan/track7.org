<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for shop page                                              *
\******************************************************************************/

ul#shopitems {
  margin: .5em 1em .5em 1em;
  padding: 0;
}
ul#shopitems li {
  float: left;
  width: 30%;
  margin: .5em 1.5%;
  list-style-type: none;
}
ul#shopitems li.start {
  clear: left;
}
ul#shopitems li div {
  border: 1px solid #<?=HEADMEDIUM; ?>;
  -khtml-border-radius: .8em;
  -webkit-border-radius: .8em;
  -moz-border-radius: .8em;
  border-radius: .8em;
}
ul#shopitems li div h2 {
  margin: 0;
  padding: 0 .5em;
}
ul#shopitems li div img {
  display: block;
  margin: .5em auto;
}
ul#shopitems li div p {
  margin: .5em 1em;
}
