import { createApp } from "vue";
import "comment";
import BlogApi from "/api/blog.js";

if(document.querySelector("nav.actions"))
	createApp({
		name: "BlogActions",
		data() {
			return {
				showPublishSuccess: false
			};
		},
		methods: {
			async Publish() {
				const publishLink = document.querySelector("nav.actions a.publish");
				const postID = publishLink.href.split("/").pop();
				try {
					await BlogApi.publish(postID);
					publishLink.remove();
					document.querySelector("nav.actions a.del")?.remove();
					this.showPublishSuccess = true;
					await new Promise(resolve => setTimeout(resolve, 3000));
					this.showPublishSuccess = false;
				} catch(error) {
					alert(error.message);
				}
			},
			async Delete() {
				if(confirm("do you really want to delete this blog entry?  it will be gone forever!")) {
					const id = document.querySelector("nav.actions a.del").href.split("/").pop();
					try {
						await BlogApi.delete(id);
						location.href = "./";  // to index
					} catch(error) {
						alert(error.message);
					}
				}
			}
		}
	}).mount("nav.actions");
