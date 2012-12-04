jQuery(document).ready(
	function() {
		PageRater.init();
		PageRater.extraFormInit();
	}
);

var PageRater = {

	formSelector: "#Form_PageRatingForm",

	submitButtonSelector: "#Form_PageRatingForm .Actions input",

	loadingMessage: "saving ...",

	loadingClass: "loading",

	starSelectedIdentifier: ".star-rating-on",

	reminderMessage: "Please make sure that you select a rating from one to five stars",

	formOptions : {
		target: "#Form_PageRatingForm",
		beforeSubmit: function (formData, jqForm, options) {
			if(!jQuery(PageRater.starSelectedIdentifier).length) {
				alert(PageRater.reminderMessage);
				return false;
			}
			jQuery(PageRater.formSelector).text(PageRater.loadingMessage).addClass(PageRater.loadingClass);
			jQuery(PageRater.extraFormSelector).addClass(PageRater.loadingClass);
			return true;
		},
		success: function (responseText, statusText) {
			jQuery(this.target).html(responseText);
			jQuery(PageRater.formSelector).removeClass(PageRater.loadingClass);
			jQuery(PageRater.extraFormSelector).unbind("submit").submit();
		}
	},

	extraFormSelector: "",
		set_extra_form_selector: function(v) { this.extraFormSelector = v;},
		has_extra_form_selector: function(v) { if(this.extraFormSelector) {return true;} else {return false;}},

	init: function() {
		jQuery(PageRater.submitButtonSelector).click(
			function() {
				jQuery(PageRater.formSelector).ajaxSubmit(PageRater.formOptions);
				return false;
			}
		)
	},

	extraFormInit: function() {
		//do we need to add at all?
		if(PageRater.has_extra_form_selector()) {
			//is there is a rating form
			if(jQuery(PageRater.formSelector).length) {
				jQuery(PageRater.submitButtonSelector).hide();
				jQuery(PageRater.extraFormSelector).submit(
					function() {
						jQuery(PageRater.formOptions.target).ajaxSubmit(PageRater.formOptions);
						return false;
					}
				);
			};
		}
	}

}
