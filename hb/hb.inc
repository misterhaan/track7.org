<?
/******************************************************************************\
 * Title:    holla back shared functions
 * Purpose:  Provides functions used by multiple pages for holla back forums
\******************************************************************************/

  class HB {
    function TagLinks($tags) {
      if(strlen($tags)) {
      	foreach(explode(',', $tags) as $tag)
      	  $ret[] = '<a href="/hb/?tag=' . $tag . '" title="view threads tagged with &ldquo;' . $tag . '&rdquo;">' . $tag . '</a>';
      	return implode(', ', $ret);
      } else
        return '<em>(none)</em>';
    }
  }
?>