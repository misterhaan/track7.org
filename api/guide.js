import Ajax from "./ajax.js";
import { FindAddedTags } from "tag";

const apiURL = "/api/guide.php/";

export default class GuideApi {
	static list(tag, skip) {
		let url = `${apiURL}list`;
		if(tag)
			url += `/${tag}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static chapters(id) {
		const url = `${apiURL}chapters/${id}`;
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

	static save(originalId, newId, title, summary, level, tags, originalTags, chapters, correctionsOnly) {
		const url = `${apiURL}save/${originalId}`;
		const data = {
			id: newId,
			title: title,
			summary: summary,
			level: level,
			addTags: FindAddedTags(tags, originalTags),
			delTags: FindAddedTags(originalTags, tags),
			chapters: chapters,
			correctionsOnly: correctionsOnly
		};
		return Ajax.post(url, JSON.stringify(data));
	}

	static publish(id) {
		const url = `${apiURL}publish/${id}`;
		return Ajax.post(url);
	}

	static delete(id) {
		const url = `${apiURL}id/${id}`;
		return Ajax.delete(url);
	}
}
