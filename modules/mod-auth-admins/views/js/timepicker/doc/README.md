jQuery UI Timepicker by François Gélinas
========================================

What
----
This is a jQuery UI time picker plugin build to match with other official jQuery UI widgets.
Based on the existing date picker, it will blend nicely with your form and use your selected jQuery UI theme.
The plugin is very easy to integrate in your form for you time (hours / minutes) inputs.

Why
---
I built this plugin because I could not find a plugin that did what I needed.

Doc
---
Examples are available on the main page at [http://fgelinas.com/code/timepicker](http://fgelinas.com/code/timepicker)
Most option parameters are documented at [http://fgelinas.com/code/timepicker/#usage](http://fgelinas.com/code/timepicker/#usage)

```javascript
jQuery('#myTimePicker').timepicker({
    // Options
    timeSeparator: ':',           // The character to use to separate hours and minutes. (default: ':')
    showLeadingZero: true,        // Define whether or not to show a leading zero for hours < 10.
                                     (default: true)
    showMinutesLeadingZero: true, // Define whether or not to show a leading zero for minutes < 10.
                                     (default: true)
    showPeriod: false,            // Define whether or not to show AM/PM with selected time. (default: false)
    showPeriodLabels: true,       // Define if the AM/PM labels on the left are displayed. (default: true)
    periodSeparator: ' ',         // The character to use to separate the time from the time period.
    altField: '#alternate_input', // Define an alternate input to parse selected time to
    defaultTime: '12:34',         // Used as default time when input field is empty or for inline timePicker
                                  // (set to 'now' for the current time, '' for no highlighted time,
                                     default value: now)

    // trigger options
    showOn: 'focus',              // Define when the timepicker is shown.
                                  // 'focus': when the input gets focus, 'button' when the button trigger element is clicked,
                                  // 'both': when the input gets focus and when the button is clicked.
    button: null,                 // jQuery selector that acts as button trigger. ex: '#trigger_button'

    // Localization
    hourText: 'Hour',             // Define the locale text for "Hours"
    minuteText: 'Minute',         // Define the locale text for "Minute"
    amPmText: ['AM', 'PM'],       // Define the locale text for periods

    // Position
    myPosition: 'left top',       // Corner of the dialog to position, used with the jQuery UI Position utility if present.
    atPosition: 'left bottom',    // Corner of the input to position

    // Events
    beforeShow: beforeShowCallback, // Callback function executed before the timepicker is rendered and displayed.
    onSelect: onSelectCallback,   // Define a callback function when an hour / minutes is selected.
    onClose: onCloseCallback,     // Define a callback function when the timepicker is closed.
    onHourShow: onHourShow,       // Define a callback to enable / disable certain hours. ex: function onHourShow(hour)
    onMinuteShow: onMinuteShow,   // Define a callback to enable / disable certain minutes. ex: function onMinuteShow(hour, minute)

    // custom hours and minutes
    hours: {
        starts: 0,                // First displayed hour
        ends: 23                  // Last displayed hour
    },
    minutes: {
        starts: 0,                // First displayed minute
        ends: 55,                 // Last displayed minute
        interval: 5,              // Interval of displayed minutes
        manual: []                // Optional extra entries for minutes
    },
    rows: 4,                      // Number of rows for the input tables, minimum 2, makes more sense if you use multiple of 2
    showHours: true,              // Define if the hours section is displayed or not. Set to false to get a minute only dialog
    showMinutes: true,            // Define if the minutes section is displayed or not. Set to false to get an hour only dialog

    // Min and Max time
    minTime: {                    // Set the minimum time selectable by the user, disable hours and minutes
        hour: minHour,            // previous to min time
        minute: minMinute
    },
    maxTime: {                    // Set the minimum time selectable by the user, disable hours and minutes
        hour: maxHour,            // after max time
        minute: maxMinute
    },


    // buttons
    showCloseButton: false,       // shows an OK button to confirm the edit
    closeButtonText: 'Done',      // Text for the confirmation button (ok button)
    showNowButton: false,         // Shows the 'now' button
    nowButtonText: 'Now',         // Text for the now button
    showDeselectButton: false,    // Shows the deselect time button
    deselectButtonText: 'Deselect' // Text for the deselect button

});
```

Requirements
------------
Work with jQuery 1.5.1 and more, also require jQuery UI core.
There is a legacy version of the plugin made to work with older jQuery 1.2.6 and UI 1.6 at [http://fgelinas.com/code/timepicker/#get_timepicker](http://fgelinas.com/code/timepicker/#get_timepicker)

Licenses
--------
The plugin is licensed under the [MIT](https://github.com/fgelinas/timepicker/blob/master/MIT-LICENSE.txt) and [GPL](https://github.com/fgelinas/timepicker/blob/master/GPL-LICENSE.txt) licenses.

Other Stuff
-----------
There is a jsFiddle page [here](http://jsfiddle.net/fgelinas/R6jLt/) with basic implementation for testing.