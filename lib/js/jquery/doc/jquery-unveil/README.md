# unveil.js
## A very lightweight plugin to lazy load images for jQuery
### THIS IS A MODIFIED VERSION ; contains fixes by unixman

Most of us are familiar with the [Lazy Load](http://www.appelsiini.net/projects/lazyload) plugin by [Mika Tuupola](http://www.appelsiini.net/).
This plugin is very useful and it boosts performance delaying loading of images in long web pages because images outside of viewport (visible part of web page) won't be loaded until the user scrolls to them.
Lazy Load has some cool options such as custom effects, container, events or data attribute. If you're not gonna use any of them you can reduce the file size by leaving just the essential code to show the images.
That's what I did and this is my lightweight version of Lazy Load - less than 1k.

Visit unveil's [project page](http://luis-almeida.github.com/unveil/) to read the documentation and see the demo.


### Browser support
Compatible with All Browsers and IE7+.


### License
Unveil is licensed under the [MIT license](http://opensource.org/licenses/MIT).

# Documentation
Most of us are familiar with the Lazy Load plugin by Mika Tuupola.
This plugin is very useful and it boosts performance delaying loading of images in long web pages because images outside of viewport (visible part of web page) won't be loaded until the user scrolls to them.
Lazy Load has some cool options such as custom effects, container, events or data attribute. If you're not gonna use any of them you can reduce the file size by leaving just the essential code to show the images.
That's what I did and this is my lightweight version of Lazy Load with support for serving high-resolution images to devices with retina displays - less than 1k.

## Usage
Use a placeholder image in the src attribute - something to be displayed while the original image loads - and include the actual image source in a "data-src" attribute.
<img src="blank.png" data-src="img1.jpg">
If you care about users without javascript enabled, you can include the original image inside a <noscript> tag:
<noscript>
  <img src="img1.jpg">
</noscript>

Run the script on document ready:
$(document).ready(function() {
  $("img").unveil();
});

## Threshold
By default, images are only loaded and "unveiled" when the user scrolls to them and they became visible on the screen.
<img class="unveil" src="" data-src="img1.jpg" data-src-alt="img1.webp">
If you want your images to load earlier than that, lets say 200px before they appear on the screen, you just have to:
$("img.unveil").unveil({
	threshold: 200, // unveil if 200px above image
	attribute: 'data-src-alt' // if this is not present will fallback to 'data-src' attribute
});

## Callback
As a second parameter you can also specify a callback function that will fire after an image has been "unveiled".
Inside the callback function this refers to the image's DOM node, so with the help of CSS3 (or jQuery animations) and by attaching a simple load event you can easily add some fancy entrance animation to your images:
img {
  opacity: 0;
  transition: opacity .3s ease-in;
}
$("img").unveil({}, function() {
  $(this).load(function() {
    this.style.opacity = 1;
  });
});

## Trigger
You can still trigger image loading whenever you need.
All you have to do is select the images you want to "unveil" and trigger the event:
$("img").trigger("unveil");

## Lookup
It is also possible to lookup for images in the viewport that haven't been "unveiled" yet.
This can be useful, for instance, in case of a tabbed layout.
$(window).trigger("lookup"); // this should be also used with dynamic components that contain unveil images

## Cancel
You can remove all the "unveil" event handlers from "window":
$(window).off("unveil");

### Using srcset
<picture>
	<source type="image/webp" class="unveil" data-unveil="srcset" data-src="image.webp" srcset="">
	<img class="unveil" data-src="image.jpg" src="">
</picture>
