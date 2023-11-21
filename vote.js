import "jquery";

const votingStars = $("#vote, #vote span");
const rating = $("span.rating");

votingStars.click(function() {
	const vote = +$(this).data("vote");
	$.post("/api/vote.php/cast/" + $("#vote").data("post"), { vote: vote })
		.done(result => {
			votingStars.removeClass("voted");
			$("#vote").addClass("voted");
			$("#vote span").each(function() {
				if(+$(this).data("vote") <= vote)
					$(this).addClass("voted");
				rating.attr("data-stars", Math.round(2 * result.Rating) / 2);
				rating.attr("title", "rated " + result.Rating + " stars by " + (result.VoteCount == 1 ? "1 person" : result.VoteCount + " people"));
			}).fail(request => {
				alert(request.responseText);
			});
		});
	return false;
});
