<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style to use most of the width of the browser                    *
\******************************************************************************/

div#content a.feed {
  display: none;
}


/* ==============================================================[ layout ]== */

div.dynamic {
  width: 24.81em;
}

div#foot {
  padding: 0;
  padding-top: 1em;
  color: #000000;
  background: none;
}
div#foot div {
  padding: .4em .75em;
  background: #<?=BGMEDIUM; ?>;
}
div#foot div#copyright {
  margin-top: -1.6em;
  padding: 0 .75em;
}
div#copyright a:link,
div#copyright a:visited {
  color: #000000;
  border-bottom: 1px dotted #000000;
}
@media all and (max-width: 40em;) {
  div#foot div#pagegen {
    padding-bottom: 0;
  }
  div#foot div#copyright {
    margin-top: 0;
    float: none;
    padding-bottom: .4em;
  }
}
