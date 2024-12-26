/* English initialisation for the jQuery time picker plugin. */
/* Written by Radu Ilies (iradu@unix-world.org) */
jQuery(function($){
	$.timepicker.regional['en'] = {
				hourText: 'Hours',
				minuteText: 'Minutes',
				amPmText: ['AM', 'PM'],
				closeButtonText: 'Close',
				nowButtonText: 'Now',
				deselectButtonText: 'Deselect' }
	$.timepicker.setDefaults($.timepicker.regional['en']);
});