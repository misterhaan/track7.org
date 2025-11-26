import { createApp } from "vue";
import LegoApi from "/api/lego.js";

createApp({
	name: "LegoModels",
	data() {
		return {
			legos: [],
			hasMore: false,
			loading: false,
			error: null
		};
	},
	created() {
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				const result = await LegoApi.list(this.legos.length);
				this.legos = this.legos.concat(result.Legos);
				this.hasMore = result.HasMore;
			} catch(error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		}
	},
	template: /* html */ `
	<p class=error v-if=error>{{error}}</p>
	<p v-if="!legos.length && !loading">this gallery is empty!</p>

	<ol id=legogallery class=gallery v-if=legos.length>
		<li v-for="model in legos">
			<a class="lego thumb" :href=model.ID>
				<img :src="'/lego/data/' + model.ID + '-thumb.png'">
				<span class=caption>{{model.Title}}</span>
			</a>
		</li>
	</ol>

	<p class=loading v-if=loading>loading more legos . . .</p>
	<p class="more calltoaction" v-if="!loading && hasMore"><a class="action get" href=#nextpage @click.prevent=Load>load more legos</a></p>
	`
}).mount("#legomodels");
