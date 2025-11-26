import VoteApi from "/api/vote.js";

const votingStars = document.querySelectorAll("#vote, #vote span");
const rating = document.querySelector("span.rating");

votingStars.forEach(star => {
	star.addEventListener("click", async event => {
		event.stopPropagation();
		event.preventDefault();
		const vote = +event.target.dataset.vote;
		const voteEl = document.querySelector("#vote");
		try {
			const result = await VoteApi.cast(voteEl.dataset.post, vote);
			votingStars.forEach(star => {
				star.classList.remove("voted");
			});
			voteEl.classList.add("voted");
			document.querySelectorAll("#vote span").forEach(star => {
				if(star.dataset.vote <= vote)
					star.classList.add("voted");
			});
			if(rating) {
				rating.dataset.stars = Math.round(2 * result.Rating) / 2;
				rating.title = "rated " + result.Rating + " stars by " + (result.VoteCount == 1 ? "1 person" : result.VoteCount + " people");
			}
		} catch(error) {
			alert(error.message);
		}
	});
});
