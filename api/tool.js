import Ajax from "./ajax.js";

const apiURL = "/api/tool.php/";

export default class ToolApi {
	static gitpull() {
		const url = `${apiURL}gitpull`;
		return Ajax.post(url);
	}

	static tweetAuthStatus() {
		const url = `${apiURL}tweetAuthStatus`;
		return Ajax.get(url);
	}

	static tweetAuthURL() {
		const url = `${apiURL}tweetAuthURL`;
		return Ajax.get(url);
	}

	static tweetAuth(code, csrf) {
		const url = `${apiURL}tweetAuth`;
		const data = { code: code, csrf: csrf };
		return Ajax.post(url, data);
	}

	static tweet(message, url) {
		const url = `${apiURL}tweet`;
		const data = { message: message, url: url };
		return Ajax.post(url, data);
	}

	static regexmatch(pattern, subject, findAll) {
		let url = `${apiURL}regexmatch`;
		if(findAll)
			url += "/all";
		const data = { pattern: pattern, subject: subject };
		return Ajax.post(url, data);
	}

	static regexreplace(pattern, replacement, subject) {
		const url = `${apiURL}regexreplace`;
		const data = { pattern: pattern, replacement: replacement, subject: subject };
		return Ajax.post(url, data);
	}

	static timestamp(value, zone, formatted) {
		let url = `${apiURL}timestamp/${zone}`;
		if(formatted)
			url += "/formatted";
		return Ajax.get(url, { value: value });
	}
}
