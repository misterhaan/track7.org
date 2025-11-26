import Ajax from "./ajax.js";

const apiURL = "/api/user.php/";

export default class UserApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static suggest(match) {
		const url = `${apiURL}suggest`;
		return Ajax.post(url, match);
	}

	static info(username) {
		const url = `${apiURL}info/${username}`;
		return Ajax.get(url);
	}

	static registration() {
		const url = `${apiURL}registration`;
		return Ajax.get(url);
	}

	static register(csrf, username, displayname, email, website, linkprofile, useavatar) {
		const url = `${apiURL}register`;
		const data = { csrf: csrf, username: username, displayname: displayname, email: email, website: website, linkprofile: linkprofile, useavatar: useavatar };
		return Ajax.post(url, data);
	}

	static idAvailable(newId, oldId) {
		const url = `${apiURL}idAvailable/${oldId}`;
		return Ajax.post(url, newId);
	}

	static nameAvailable(newName, oldName) {
		const url = `${apiURL}nameAvailable/${oldName}`;
		return Ajax.post(url, newName);
	}

	static login(username, password, remember) {
		const url = `${apiURL}login/${remember}`;
		return Ajax.post(url, { username: username, password: password });
	}

	static auth(provider, remember) {
		let url = `${apiURL}auth/${provider}`
		if(remember)
			url += `/${remember}`;
		return Ajax.get(url);
	}

	static putFriend(id) {
		const url = `${apiURL}friend/${id}`;
		return Ajax.put(url);
	}

	static deleteFriend(id) {
		const url = `${apiURL}friend/${id}`;
		return Ajax.delete(url);
	}

	static logout() {
		const url = `${apiURL}logout`;
		return Ajax.post(url);
	}
}
