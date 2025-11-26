import Ajax from "./ajax.js";

const apiURL = "/api/vote.php/";

export default class VoteApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static cast(postID, vote) {
		const url = `${apiURL}cast/${postID}`;
		return Ajax.post(url, { vote: vote });
	}

	static delete(id) {
		const url = `${apiURL}delete/${id}`;
		return Ajax.post(url);
	}
}
