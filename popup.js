class PopupManager {
	#popup = null;;
	#seen = new Set();

	constructor() {
		$(document).click(() => {
			this.hide();
		});
	}

	register(popup, trigger) {
		if(!(popup instanceof $))
			popup = $(popup);
		if(!(trigger instanceof $))
			trigger = $(trigger);

		trigger.click(event => {
			this.toggle(popup);
			event.preventDefault();
			event.stopPropagation();
		});
	}

	toggle(popup) {
		if(!(popup instanceof $))
			popup = $(popup);
		if(this.#popup && this.#popup[0] == popup[0])
			this.hide();
		else {
			this.hide();
			this.#show(popup);
		}
	}

	#show(popup) {
		this.#popup = popup;
		this.#stopPropagation(popup);
		popup.show();
	}

	#stopPropagation(popup) {
		if(!this.#seen.has(popup[0])) {
			this.#seen.add(popup[0]);
			popup.click(event => {
				// so document click doesn't close the popup
				event.stopPropagation();
			});
		}
	}

	hide() {
		if(this.#popup) {
			this.#popup.hide();
			this.#popup = null;
		}
	}
}

export const popup = new PopupManager();
