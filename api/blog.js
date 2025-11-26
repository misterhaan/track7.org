import Ajax from "./ajax.js";

const apiURL = "/api/blog.php/";

export default class BlogApi {
	static list(tag, skip) {
		let url = `${apiURL}list`;
		if(tag)
			url += `/${tag}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static edit(id) {
		const url = `${apiURL}edit/${id}`;
		return Ajax.get(url);
	}

	static idAvailable(newId, oldId) {
		const url = `${apiURL}idAvailable/${oldId}`;
		return Ajax.post(url, newId);
	}

	static save(originalId, newId, title, markdown, tags, originalTags) {
		const url = `${apiURL}save/${originalId}`;
		const data = {
			id: newId,
			title: title,
			markdown: markdown,
			addtags: FindAddedTags(tags, originalTags),
			deltags: FindAddedTags(originalTags, tags)
		};
		return Ajax.post(url, data);
	}

	static publish(id) {
		const url = `${apiURL}publish/${id}`;
		return Ajax.post(url);
	}

	static delete(id) {
		const url = `${apiURL}entry/${id}`;
		return Ajax.delete(url);
	}
}
