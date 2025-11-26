import Ajax from "./ajax.js";

const apiURL = "/api/settings.php/";

export default class SettingsApi {
	static loadProfile() {
		const url = `${apiURL}profile`;
		return Ajax.get(url);
	}

	static saveProfile(username, displayname, avatarsource) {
		const url = `${apiURL}profile`;
		const data = { username: username, displayname: displayname, avatarsource: avatarsource };
		return Ajax.post(url, data);
	}

	static loadTime() {
		const url = `${apiURL}time`;
		return Ajax.get(url);
	}

	static saveTime(currentTime, dst) {
		const url = `${apiURL}time`;
		const data = {
			currenttime: currentTime,
			dst: dst
		};
		return Ajax.post(url, data);
	}

	static loadContacts() {
		const url = `${apiURL}contacts`;
		return Ajax.get(url);
	}

	static saveContacts(contacts) {
		const url = `${apiURL}contacts`;
		return Ajax.post(url, JSON.stringify(contacts));
	}

	static loadNotification() {
		const url = `${apiURL}notification`;
		return Ajax.get(url);
	}

	static saveNotification(emailnewmessage) {
		const url = `${apiURL}notification`;
		const data = { emailnewmessage: emailnewmessage };
		return Ajax.post(url, data);
	}

	static loadLogins() {
		const url = `${apiURL}logins`;
		return Ajax.get(url);
	}

	static deleteLogin(provider, id) {
		const url = `${apiURL}login/${provider}/${id}`;
		return Ajax.delete(url);
	}

	static deletePassword() {
		const url = `${apiURL}password`;
		return Ajax.delete(url);
	}
}
