class PopupManager {
	#popup = null;
	#seen = new Set();

	constructor() {
		document.addEventListener("click", () => {
			this.hide();
		});
	}

	register(popup, trigger) {
		if(typeof popup == "string")
			popup = document.querySelector(popup);
		if(typeof trigger == "string")
			trigger = document.querySelector(trigger);

		trigger?.addEventListener("click", event => {
			this.toggle(popup, trigger);
			event.preventDefault();
			event.stopPropagation();
		});

		if(popup)
			popup.trigger = trigger;
	}

	toggle(popup) {
		if(this.#popup && this.#popup == popup)
			this.hide();
		else {
			this.hide();
			this.#show(popup);
			popup.trigger?.classList.add("open");
		}
	}

	#show(popup) {
		this.#popup = popup;
		this.#stopPropagation(popup);
		popup.style.display = "unset";
	}

	#stopPropagation(popup) {
		if(!this.#seen.has(popup)) {
			this.#seen.add(popup);
			popup.addEventListener("click", event => {
				// so document click doesn't close the popup
				event.stopPropagation();
			});
		}
	}

	hide() {
		if(this.#popup) {
			this.#popup.style?.removeProperty("display");
			this.#popup.trigger?.classList.remove("open");
			this.#popup = null;
		}
	}
}

export const popup = new PopupManager();
