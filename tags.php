<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true]);
$html->Open('tags');
?>
			<h1>tag information</h1>
			<div class=tabbed>
				<nav class=tabs>
					<a href="#blog" title="tags for blog entries">blog entries</a>
					<a href="#guide" title="tags for guides">guides</a>
					<a href="#photos" title="tags for photos">photos</a>
					<a href="#art" title="tags for art">art</a>
					<a href="#forum" title="tags for forum discussions">forum</a>
				</nav>
				<ul id=taginfo data-bind="foreach: tags">
					<li v-for="tag in tags">
						<div class=tagdata>
							<a :href="urlPrefix + tag.name + '/'">{{tag.name}}</a>
							<span class=count>{{tag.count}} uses</span>
							<time :datetime=tag.lastused.datetime>{{tag.lastused.display}} ago</time>
						</div>
						<div class=description>
							<span class=prefix>{{prefix}}</span>
							<span class=editable v-html=tag.description v-if=!tag.editing></span>
<?php
if($user->IsAdmin()) {
?>
							<label class=multiline v-if=tag.editing>
								<span class=field><textarea v-model=descriptionedit></textarea></span>
								<span>
									<a href="#save" title="save tag description" class="action okay" v-on:click.prevent=Save(tag)></a>
									<a href="#cancel" title="cancel editing" class="action cancel" v-on:click.prevent=Cancel(tag)></a>
								</span>
							</label>
<?php
}
?>
							<span class=postfix>{{postfix}}</span>
<?php
if($user->IsAdmin()) {
?>
							<a href="#edit" class="action edit" v-if="!tag.editing && descriptionedit === false" v-on:click.prevent=Edit(tag)></a>
<?php
}
?>
						</div>
					</li>
				</ul>
			</div>
<?php
$html->Close();
