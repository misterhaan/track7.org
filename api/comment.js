import Ajax from "./ajax.js";

const apiURL = "/api/comment.php/";

export default class CommentApi {
	static all(skip) {
		let url = `${apiURL}all`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static byPost(postID, skip) {
		let url = `${apiURL}bypost/${postID}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static byUser(username, skip) {
		let url = `${apiURL}byuser/${username}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static new(postID, newComment) {
		const url = `${apiURL}new/${postID}`;
		return Ajax.post(url, newComment);
	}

	static patch(id, markdown, stealth) {
		let url = `${apiURL}id/`;
		if(stealth)
			url += `stealth/`;
		url += id;
		return Ajax.patch(url, markdown);
	}

	static delete(id) {
		const url = `${apiURL}id/${id}`;
		return Ajax.delete(url);
	}
}
