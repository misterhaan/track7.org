import Ajax from "./ajax.js";

const apiURL = "/api/activity.php/";

export default class ActivityApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static byuser(username, skip) {
		let url = `${apiURL}byuser/${username}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}
}
