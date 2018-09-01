$(function() {
	$("a.publish").click(function() {
		$.post(this.href, {id: $(this).parent().data("id")}, result => {
			if(!result.fail) {
				$("a.del").remove();
				var nav = $(this).parent();
				$(this).remove();
				nav.append($("<span class=success>successfully published!</span>").delay(3000).fadeOut(1000));
			} else
				alert(result.message);
		});
		return false;
	});
	$("a.del").click(function() {
		if(confirm("do you really want to delete this guide?  it will be gone forever!"))
			$.post(this.href, {id: $(this).parent().data("id")}, result => {
				if(!result.fail)
					window.location.href = "/guides/";  // to index
				else
					alert(result.message);
			});
		return false;
	});
});
