<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for user messages page                                     *
\******************************************************************************/

a.msgunread,
a.msgread,
a.msgreplied {
  background-position: center left;
  background-repeat: no-repeat;
  line-height: 15px;
  padding-left: 22px;
}
a.msgunread {
  background-image: url(/images/message/unread.png);
}
a.msgread {
  background-image: url(/images/message/read.png);
}
a.msgreplied {
  background-image: url(/images/message/replied.png);
}
