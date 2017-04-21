$(function() {
	ko.applyBindings(window.VotesVM = new VotesViewModel());
});

function VotesViewModel() {
	var self = this;
	this.votes = ko.observableArray([]);
	this.oldest = "";
	this.more = ko.observable(0);
	this.loading = ko.observable(false);

	this.Load = function() {
		self.loading(true);
		$.get("?ajax=list", {oldest: self.oldest}, function(result) {
			if(result.fail)
				alert(result.message);
			else {
				for(var v = 0; v < result.votes.length; v++)
					self.votes.push(result.votes[v]);
				self.oldest = result.oldest;
				self.more(result.more);
			}
			self.loading(false);
		}, "json");
	};
	this.Load();

	this.Delete = function(vote) {
		$.post("?ajax=delete", {type: vote.type, id: vote.id, item: vote.item}, function(result) {
			if(result.fail)
				alert(result.message);
			else if(result.deleted)
				self.votes.remove(vote);
			else
				alert("vote not deleted");
		}, "json");
	}
}
