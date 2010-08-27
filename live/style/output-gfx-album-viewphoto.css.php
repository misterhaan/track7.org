<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for photo album photo view pages                           *
\******************************************************************************/

div.tagnav {
  text-align: center;
  margin: .5em auto;
}
div.tagnav * {
  margin: 0 .75em;
}
div.tagnav a.tag {
  padding-left: 20px;
  background-image: url(/style/tag.png);
  background-repeat: no-repeat;
  background-position: left center;
}

div#content p.photo {
  text-align: center;
}

div#photometa {
  width: 570px;
  margin: .5em auto;
  font-size: .8em;
}
div#photometa span {
  margin: 0 1.5em;
}
