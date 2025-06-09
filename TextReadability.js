$(function() {

	$(document).on('trResultsToggle', function(event, $icon) {
		const $inputfield = $icon.closest('.Inputfield');
		$icon.addClass('tr-header-action-icon');
		$inputfield.toggleClass('tr-show-readability-results');
	});

});
