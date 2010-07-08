<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for shop page                                              *
\******************************************************************************/

ul#shopitems {
  margin: .5em 1em;
  padding: 0;
}
ul#shopitems li {
  float: left;
  width: 29.99%;
  margin: .5em 1.5%;
  list-style-type: none;
}
ul#shopitems li.start3 {
  clear: left;
}
@media all and (max-width: 620px) {
  ul#shopitems li {
    width: 46.99%;
  }
  ul#shopitems li.start3 {
    clear: none;
  }
  ul#shopitems li.start2 {
    clear: left;
  }
}
@media all and (max-width: 410px) {
  ul#shopitems li {
    width: 96.99%;
  }
}
@media all and (min-width: 840px) {
  ul#shopitems li {
    width: 21.99%
  }
  ul#shopitems li.start3 {
    clear: none;
  }
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
  -webkit-border-bottom-left-radius: 0;
  -webkit-border-bottom-right-radius: 0;
  -moz-border-radius-bottomleft: 0;
  -moz-border-radius-bottomright: 0;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
ul#shopitems li div img {
  display: block;
  margin: .5em auto;
}
ul#shopitems li div p {
  margin: .5em 1em;
  text-align: left;
}