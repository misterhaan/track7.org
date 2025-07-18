/**
 * Translate a name into a URL segment based on the name.
 * @param name display name or title
 * @returns URL segment
 */
export function NameToUrl(name) {
	return name ? name.toLowerCase().replace(/ /g, "-").replace(/[^a-z0-9\-_]*/g, "") : name;
}

const Status = {
	Checking: "checking",
	Valid: "valid",
	Invalid: "invalid"
};

const activeValidationRequests = new Set();
const waitingValidationRequests = [];

function finishValidationRequest(request) {
	activeValidationRequests.delete(request);
	if(waitingValidationRequests.length) {
		const nextRequest = waitingValidationRequests.shift();
		activeValidationRequests.add(nextRequest);
		nextRequest().always(() => {
			finishValidationRequest(nextRequest);
		});
	}
}

export const ValidatingField = {
	props: [
		"value",
		"default",
		"urlCharsOnly",
		"validateUrl",
		"msgChecking",
		"msgValid",
		"msgBlank",
		"isBlankValid",
		"inputAttributes"
	],
	emits: [
		"validated"
	],
	data() {
		return {
			localValue: this.value,
			state: null,
			lastMessage: null
		};
	},
	watch: {
		localValue: function(val) {
			if(this.urlCharsOnly)
				this.localValue = NameToUrl(val);
		},
		value(val) {
			if(this.localValue != val) {
				this.localValue = val;
				this.Validate();
			}
		},
		default() {
			if(!this.localValue)
				this.Validate();
		}
	},
	computed: {
		effectiveValue() {
			return this.localValue || this.default || "";
		},
		message() {
			if(this.lastMessage)
				return this.lastMessage;

			switch(this.state) {
				case Status.Checking: return this.msgChecking;
				case Status.Valid:
					if(this.isBlankValid && this.effectiveValue == "")
						return this.msgBlank;
					return this.msgValid;
				case Status.Invalid:
					if(!this.isBlankValid && this.effectiveValue == "")
						return this.msgBlank;
					return "invalid";  // this is expected to come from this.lastMessage
			}
		}
	},
	created() {
		this.Validate();
	},
	methods: {
		Validate() {
			this.state = Status.Checking;
			this.$emit("validated", false, this.localValue);
			this.lastMessage = null;
			if(this.effectiveValue == "") {
				this.state = this.isBlankValid ? Status.Valid : Status.Invalid;
				this.$emit("validated", this.isBlankValid, "");
			} else {
				const request = () => {
					return $.ajax({
						method: "POST",
						url: this.validateUrl,
						contentType: "text/plain; charset=utf-8",
						data: this.effectiveValue
					}).done(result => {
						this.state = result.State;
						this.lastMessage = result.Message;
						this.$emit("validated", this.state == Status.Valid, this.localValue = result.NewValue || this.localValue);
					}).fail(request => {
						this.state = Status.Invalid;
						this.lastMessage = request.responseText;
						this.$emit("validated", false, this.localValue);
					});
				}
				if(activeValidationRequests.size < 2) {
					activeValidationRequests.add(request);
					request().always(() => {
						finishValidationRequest(request);
					});
				} else
					waitingValidationRequests.push(request);
			}
		}
	},
	template: /* html */ `
		<span class=field><input
			:maxlength=this.inputAttributes?.maxlength
			:required=this.inputAttributes?.required
			:pattern=this.inputAttributes?.pattern
			v-model.trim=this.localValue
			:placeholder=this.default
			@change=Validate
		></span>
		<span v-if=state class=validation :class=state :title=message></span>
	`
};
