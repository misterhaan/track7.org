<?php
define('MAX_LEGO', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true, 'bodytype' => 'gallery', 'rss' => ['title' => 'legos', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
$html->Open('original lego models');
?>
			<h1>
				original lego models
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of lego models"></a>
			</h1>

<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add lego model</a></nav>
<?php
}
?>
			<p>
				these lego models are <a href="/user/misterhaan/">my</a> own original
				creations.  each has step-by-step instructions and <a href="http://www.ldraw.org/">ldraw</a>
				model data file available for download.
			</p>

			<section id=legomodels>
				<p class=error v-if=error>{{error}}</p>
				<p v-if="!legos.length && !loading">this gallery is empty!</p>

				<ol id=legogallery class=gallery v-if=legos.length>
					<li v-for="model in legos">
						<a class="lego thumb" :href=model.url>
							<img :src="'/lego/data/' + model.url + '-thumb.png'">
							<span class=caption>{{model.title}}</span>
						</a>
					</li>
				</ol>

				<p class=loading v-if=loading>loading more legos . . .</p>
				<p class="more calltoaction" v-if="!loading && hasMore"><a class="action get" href=#nextpage v-on:click.prevent=Load>load more legos</a></p>
			</section>
<?php
$html->Close();
