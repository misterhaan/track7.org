<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $lego = 'select l.id, l.name, l.notes, l.pieces, l.minifigs, l.adddate, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from legos as l left join ratings as r on r.type=\'lego\' and r.selector=l.id left join votes as v on v.ratingid=r.id and (not (v.uid=0) and v.uid=' . $user->ID . ' or v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\') where l.id=\'' . addslashes($_GET['id']) . '\'';
  if(false !== $lego = $db->GetRecord($lego, 'error looking up lego model information', 'unable to find requested lego model', true)) {
    $page->Start($lego->name . ' - ' . 'legos', $lego->name);
?>
      <ul class=actions>
<?
    // DO:  uncomment when edit is available
    /*if($user->GodMode) {
?>
        <li class=edit><a href="">edit this model</a></li>
<?
    }*/
?>
        <li class=instructions><a href="/files/art/lego/<?=$lego->id; ?>-img.zip">download instructions</a></li>
        <li class=ldr><a href="/files/art/lego/<?=$lego->id; ?>-ldr.zip">download ldraw data file</a></li>
      </ul>
      <img class=render src=<?=$lego->id; ?>.png alt="">
      <ul class=facts>
        <li class=added title="added <?=strtolower($user->tzdate('g:i a \o\n l F jS Y', $lego->adddate)); ?>"><?=auText::HowLongAgo($lego->adddate); ?> ago</li>
        <li class=pieces title="made from <?=$lego->pieces; ?> pieces"><?=$lego->pieces; ?> pieces</li>
        <li class=mans title="can carry <?=$lego->minifigs; ?> lego m<?=$lego->minifigs == 1 ? 'a' : 'e'; ?>n"><?=$lego->minifigs; ?> mans</li>
      </ul>
<?
    if($lego->id == 'stalker') {
?>
      <a href="http://www.brickengineer.com/nestor/" class="award" title="see naughty nestor blow up this model"><img src="award-nestor-destruction.png" alt="worthy of destruction award" /></a>
<?
    }
?>
      <p><?=$lego->notes; ?></p>
      <? auRating::Show('lego', $lego->id, $lego->rating, $lego->votes, $lego->vote); ?>
<?
    $page->End();
  }
?>
