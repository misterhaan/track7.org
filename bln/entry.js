import "jquery";
import { createApp } from "vue";
import "comment";

createApp({
	name: "BlogActions",
	methods: {
		Publish() {
			const publishLink = $("nav.actions a.publish");
			const url = publishLink.attr("href");
			$.post(url)
				.done(() => {
					publishLink.remove();
					$("nav.actions a.del").remove();
					$("nav.actions").append($("<span class=success>successfully published!</span>").delay(3000).fadeOut(1000));
				}).fail(request => {
					alert(request.responseText);
				});
		},
		Delete() {
			if(confirm("do you really want to delete this blog entry?  it will be gone forever!")) {
				const deleteLink = $("nav.actions a.del");
				const url = deleteLink.attr("href");
				$.ajax({ url: url, method: "DELETE" })
					.done(() => {
						window.location.href = "./";  // to index
					}).fail(request => {
						alert(request.responseText);
					});
			}
		}
	}
}).mount("nav.actions");
