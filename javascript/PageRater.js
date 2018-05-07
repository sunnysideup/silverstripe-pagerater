if(jQuery('#Form_PageRatingForm').length > 0) {

/**
 *
 *
 *
 * see: https://github.com/fyneworks/star-rating for details ...
 */

 /*
  ### jQuery Star Rating Plugin v4.11 - 2013-03-14 ###
  * Home: http://www.fyneworks.com/jquery/star-rating/
  * Code: http://code.google.com/p/jquery-star-rating-plugin/
  *
  * Licensed under http://en.wikipedia.org/wiki/MIT_License
  ###
 */

jQuery(document).ready(
    function() {
        PageRater.init();
        PageRater.extraFormInit();
    }
);

var PageRater = {

    formSelector: "#Form_PageRatingForm",

    submitButtonSelector: ".Actions input",

    loadingMessage: "saving ...",

    loadingClass: "loading",

    starSelectedIdentifier: ".star-rating-on",

    reminderMessage: "Please make sure that you select a rating from one to five stars",

    extraFormSelector: "",
    set_extra_form_selector: function(v) { this.extraFormSelector = v;},
    has_extra_form_selector: function(v) { if(this.extraFormSelector) {return true;} else {return false;}},

    init: function() {
        //check if variables have been defined in html
        if(typeof PageRaterVariables !== "undefined") {
            //loop through page rating field variables
            for(var i = 0; i < PageRaterVariables.length; i++ ) {

                //retrieve id and fields
                var obj = PageRaterVariables[i];
                PageRater.formSelector = '#' + jQuery('input.'+obj.id).closest("form").attr("id");
                jQuery(PageRater.formSelector+ ' ' + PageRater.submitButtonSelector).hide();
                var fields = obj.fields;
                //call back on rating taking place ....
                jQuery('input.'+obj.id).rating({

                    required: true,

                    callback: function(value, link) {
                        jQuery(PageRater.formSelector).addClass('addingDetails');
                        jQuery('#'+obj.id).val(value);
                        for(var j = 0; j < fields.length; j++) {
                            jQuery('#'+fields[j]).attr('required', 'required').fadeIn();
                        }
                        jQuery(PageRater.formSelector+ ' ' + PageRater.submitButtonSelector).fadeIn();
                    }
                });
                jQuery(PageRater.formSelector+ ' ' + PageRater.submitButtonSelector).click(
                    function() {
                        jQuery(PageRater.formSelector).ajaxSubmit(
                            PageRater.getFormOptions()
                        );
                        return false;
                    }
                )

            }
        }
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
    },

    getFormOptions: function() {
        return {
            target: PageRater.formSelector,
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
        };
    }

}

}
