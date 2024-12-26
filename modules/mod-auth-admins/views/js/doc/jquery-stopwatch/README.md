# Simple count-up jQuery plugin

## Summary

A jQuery plugin that renders a count-up clock from a defined start time. Supports `start`, `stop`, `toggle`, `reset`, and custom 'tick' event.

## Usage

* Initialise and start a default timer

```js
$('').stopwatch().stopwatch('start')
```


* Initialise and bind start/stop to click

```js
$('').stopwatch().click(function(){
    $(this).stopwatch('toggle')
})
```

* Bind to <code>tick</code> event and reset when 10 seconds has elapsed

```js
$('').stopwatch().bind('tick.stopwatch', function(e, elapsed){
    if (elapsed >= 10000) {
        $(this).stopwatch('reset');
    }
}).stopwatch('start')
```

* Start at non-zero elapsed time

```js
$('').stopwatch({startTime: 10000000}).stopwatch('start')
```

### Custom formatter

A formatter function can be supplied as `formatter` in options. It receives `milliseconds` and
`options` and must return a string.

## Licence

Copyright (c) 2012 Rob Cowie. Licensed under the MIT license.