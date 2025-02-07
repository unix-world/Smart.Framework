How To Use

Step 1: together with JQuery, include jquery.simplePagination.js in your page:

<script src="path_to/jquery.js"></script>
<script src="path_to/jquery.simplePagination.js"></script>

JQuery 1.7.2 or later is recommended. Older versions might work as well, but they are not tested.

Step 2: OPTIONAL - include the CSS file with the 3 default themes

<link type="text/css" rel="stylesheet" href="path_to/jquery.simplePagination.css"/>

If you skip this step, you will need to define your own styles or use Bootstrap.

Step 3: call the function on your pagination placeholder:

$(function() {
    $(selector).pagination({
        items: 100,
        itemsOnPage: 10,
        cssStyle: 'light-theme'
    });
});

If necessary, you can specify the number of pages directly, via "pages" parameter, instead of "items" and "itemsOnPage" which are used by the plugin to automatically calculate the number of pages.
Available options
items 	Integer 	Default: 1 	Total number of items that will be used to calculate the pages.
itemsOnPage 	Integer 	Default: 1 	Number of items displayed on each page.
pages 	Integer 	Optional 	If specified, items and itemsOnPage will not be used to calculate the number of pages.
displayedPages 	Integer 	Default: 5 	How many page numbers should be visible while navigating.
Minimum allowed: 3 (previous, current & next)
edges 	Integer 	Default: 2 	How many page numbers are visible at the beginning/ending of the pagination.
currentPage 	Integer 	Default: 1 	Which page will be selected immediately after init.
hrefTextPrefix 	String 	Default: "#page-" 	A string used to build the href attribute, added before the page number.
hrefTextSuffix 	String 	Default: empty string 	Another string used to build the href attribute, added after the page number.
prevText 	String 	Default: "Prev" 	Text to be display on the previous button.
nextText 	String 	Default: "Next" 	Text to be display on the next button.
labelMap 	Array 	Default: empty array 	A collection of labels that will be used to render the pagination items, replacing the numbers.
cssStyle 	String 	Default: "light-theme" 	The class of the CSS theme.
selectOnClick 	Boolean 	Default: true 	Set to false if you don't want to select the page immediately after click.
onPageClick(pageNumber, event) 	Function 	Optional 	Function to call when a page is clicked.
Page number and event are optional parameters.
onInit 	Function 	Optional 	Function to call when the pagination is initialized.
Available methods

selectPage - Select a page based on page number.

$(function() {
    $(selector).pagination('selectPage', pageNumber);
});

prevPage - Selects the previous page.

$(function() {
    $(selector).pagination('prevPage');
});

nextPage - Select the next page.

$(function() {
    $(selector).pagination('nextPage');
});

getPagesCount - Returns the total number of pages.

$(function() {
    $(selector).pagination('getPagesCount');
});

getCurrentPage - Returns the current page number.

$(function() {
    $(selector).pagination('getCurrentPage');
});

disable - Disables pagination functionality.

$(function() {
    $(selector).pagination('disable');
});

enable - Enables the pagination after it was previously disabled.

$(function() {
    $(selector).pagination('enable');
});

destroy - Visually destroys the pagination, any existing settings are kept.

$(function() {
    $(selector).pagination('destroy');
});

redraw - The pagination is drawn again using the existing settings. (useful after you have destroyed a pagination for example)

$(function() {
    $(selector).pagination('redraw');
});

updateItems - allows to dynamically change how many items are rendered by the pagination

$(function() {
    $(selector).pagination('updateItems', 100);
});

updateItemsOnPage - allows to dynamically change how many items are rendered on each page

$(function() {
    $(selector).pagination('updateItemsOnPage', 20);
});

drawPage - takes a page number as a parameter and it sets the "currentPage" value to the given page number and draws the pagination

$(function() {
    $(selector).pagination('drawPage', 5);
});

#END