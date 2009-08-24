<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for audio / visual pages                                   *
\******************************************************************************/

div.thumb {
  float: left;
  margin-top: .4em;
  padding-left: 2.5em;
  padding-bottom: .5em;
  width: 152px;
  text-align: center;
}
div.thumb div {
  font-style: italic;
  font-size: .8em;
}
div.thumb a.img {
  border: 1px solid #<?=LINKLIGHT; ?>;
  margin: 0 0 .5em;
  padding: 0;
  display: block;
}
div.thumb a.img:hover {
  border: 1px solid #<?=LINKDARK; ?>;
  margin: 0 0 .5em;
  padding: 0;
}
div.thumb a.img img {
  display: block;
  width: 150px;
  height: 100px;
}
p.thumbed,
div.thumbed {
  padding-left: 150px;
  margin-left: 3.5em;
}

img.comic {
  display: block;
  margin: 1em auto;
}
