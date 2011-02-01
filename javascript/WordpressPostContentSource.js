;(function($) {
	$("#Form_EditForm_ImportMedia").live("click", function() {
		if ($(this).is(":checked")) {
			$("#MediaPath").show();
		} else {
			$("#MediaPath").hide();
		}
	});
})(jQuery);