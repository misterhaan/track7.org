import Ajax from "./ajax.js";

const apiURL = "/api/story.php/";

export default class StoryApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static series(series) {
		const url = `${apiURL}series/${series}`;
		return Ajax.get(url);
	}
}
