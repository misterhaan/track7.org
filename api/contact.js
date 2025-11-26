import Ajax from "./ajax.js";

const apiURL = "/api/contact.php/";

export default class ContactApi {
	static list(username) {
		const url = `${apiURL}list/${username}`;
		return Ajax.get(url);
	}

	static validateEmail(email) {
		const url = `${apiURL}validate/email`;
		return Ajax.post(url, email);
	}

	static validateWebsite(website) {
		const url = `${apiURL}validate/website`;
		return Ajax.post(url, website);
	}

	static validateTwitter(twitter) {
		const url = `${apiURL}validate/twitter`;
		return Ajax.post(url, twitter);
	}

	static validateFacebook(facebook) {
		const url = `${apiURL}validate/facebook`;
		return Ajax.post(url, facebook);
	}

	static validateGithub(github) {
		const url = `${apiURL}validate/github`;
		return Ajax.post(url, github);
	}

	static validateDeviantart(deviantart) {
		const url = `${apiURL}validate/deviantart`;
		return Ajax.post(url, deviantart);
	}

	static validateSteam(steam) {
		const url = `${apiURL}validate/steam`;
		return Ajax.post(url, steam);
	}

	static validateTwitch(twitch) {
		const url = `${apiURL}validate/twitch`;
		return Ajax.post(url, twitch);
	}
}
