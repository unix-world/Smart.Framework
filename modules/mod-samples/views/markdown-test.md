# '[###TITLE|html###]' (<H1> "Heading 1" &reg; &#169; &#169a;)

&copy;&nbsp;&nbsp;2015-2021&nbsp;unix-world.org
Extended Markdown Test :: v.20210617

## H2 (Unicode <Test>) ăĂîÎâÂşŞţŢ &quot;&nbsp;&quot; &lt;&quot;&gt;

### H3 (Alternate Unicode <Test>) ăĂîÎâÂșȘțȚ

#### H4 {H:.myClass #myID}

##### H5 {H:@data-id=h-5}

###### H6 {H:@style=display:inline-block}

::: .operation_info
```javascript
var test = true;
```
:::

::: .operation_notice .test_class_notice #test-div-notice
This is a
notice <a>bc
with an icon ![ICON](SFI-ICON "sfi sfi-leaf sfi-2x")
:::

::: .operation_important .test_class_important #test-div-important
This is important
:::

::: @article-div
This is an article DIV
:::

::: @section-div
This is a section DIV
:::

Alternatively, for H1 and H2, an underline-ish style:

Alt-<H1>
======

Alt-<H2>
------

<Line1 <i>> 1
&Line2 &copy; <i>& 2\
`Line3 <i>` 3 <a>
\
<Line4> 4
\
\
"Line5 <i> 5" <a>
 &Line6

[Go To <anchor>](#anchor<x>)

Emphasis, aka <italics>, with *asterisks* or _underscores_.

~~~~
This is a pre-"formated" <text>
and this is another line ...
~~~~

PHP code (PHP Start-Tag and PHP End-Tag must be removed ...)
```php
<?php

// php sample code
function test() {
	echo 'this is <a> "test" &copy; ...';
}

test();

?>
```

##### Test code injection:
![alt text](https://github.com/adam-p/markdown-here/raw/master/src/common/images/icon48.png?#time123456&now98765&a=<"> "Logo Title Text 1")
<http://#inline.url.tag?#time123456&now98765&c="&a=<">>
<http://#inline.url.tag2>
<">

---

<%
href="#&quot;">Test
%>
<?
test('<a href="#">Test</a>');
?>
	<%
	href="#">Test
	%>
	<?
	test('<a href="#">Test</a>');
	?>

\

Strong emphasis, aka bold, with **asterisks** or __underscores__.

Combined emphasis with **asterisks and _underscores_**.

<a href="#">Strikethrough</a> uses two tildes. ~~Scratch this.~~

Subscript is just ~this~ ...
Superscript is ^this^ ...
Syntax:
CO~2~ for subscript
E=mc^2^ for superscript

- - -
some text aligned on left (default)

|{T:@class=hidden}|
| :---: |
| some centered text |

|{T: @class=hidden}|
| ---: |
| some text aligned on right |

- - -

1. First ordered <list> "item"
```
This is some code
```
~~~
	This is preformat inside li
~~~
		And this is another preformat
	::: @div-paragraphs
		This is a div with paragraphs ...
	:::
::: @div-divs
This is a div with divs ...
:::\

2. Another <"item">
	* Unordered sub-list.
	- Another
3. Actual numbers don't matter, just that it's a number ```li with code ...```
	1. Ordered sub-list
	1. Another
4. And another item.
Continuation on next line
New Line
	And another new line here
		You can have "properly" <indented> preformat within list items. Notice the blank line above, and the leading spaces (at least one, but we'll use three here to also align the raw Markdown).

		To have a line break without a paragraph, you will need to use two trailing spaces.⋅⋅

        Note that this line is separate, but within the same paragraph.
		(This is contrary to the typical GFM line break behaviour, where trailing spaces are not required.)

* Unordered <list> can
	use "**asterisks**"
- Or minuses
+ Or pluses
* ###### H6 {H:@style=display:inline-block}

Some Paragraph
	Some Preformat area
	Line ...
		Line ...

[Link with attributes link](http://netbsd.org) {L: .primary9 #link .Upper-Case @data-smart=open.modal$700$300}

my email is <me@example.com>

[I'm an inline-style link](https://www.google.com)

[Inline link ](?#!/url-(parentheses)) with parentheses in URL
[Inline email link ](mailto:test@local)

[I'm an inline-style link with title](https://www.google.com "Google's Homepage")
[I'm an inline-style link with title](https://www.google.com "Google's <span>Homepage<span>")

[I'm a reference-style link][Arbitrary case-insensitive reference text]

[`I'm a relative reference to a repository file`](lib/license_bsd.txt)

[You can use numbers for reference-style link definitions][1]

Or leave it empty and use the [link text itself]

[![Logo](lib/framework/img/sf-logo.svg "Slimbox"){I:@width=256 @height=256}](lib/framework/img/sf-logo.svg){L:@data-slimbox=slimbox}
[![Logo](lib/framework/img/sf-logo.svg) icon with text](http://demo.unix-world.org/smart-framework)

Some text to show that the reference links can follow later.

[arbitrary case-insensitive reference text]: https://www.mozilla.org
[1]: http://slashdot.org
[link text itself]: http://www.reddit.com

Here's our logo (hover to see the title text):

Inline-style:
![alt text](https://parsedown.org/md.png "Logo Title Text 1") {I: @width=100 @style=box-shadow:$10px$10px$5px$#888888;}

Reference-style:
![alt text][logo] {I: @width=50}

Refered Link: [logo]
[logo]: //parsedown.org/md.png "Logo Title Text 2"

Inline `code` has `back-ticks around` it.

```javascript
// javascript sample code
var s = "JavaScript syntax highlighting";
alert(s);
```

```python
# python sample code
s = "Python syntax highlighting"
print s
```

```html
<!-- HTML sample code -->
<img src="some-image.svg">
```

```plaintext
This is a
plain text
with no highlight and some <tag>Tag</tag> ...
```

```
No language indicated, so no syntax highlighting (fallback to PlainText).
But let's throw in a <b>tag</b>.
```

| One {T:@class=bordered}     | Two {T:@class=bordered}        | Three {T: @class=bordered}   | Four {T: @class=bordered}         |
| ------------- |-------------| ---------| ------------- |
| One {T: @class=bordered}     | Two {T: @class=bordered}        | Three {T: @class=bordered}   | Four {T: @class=bordered}         |

| One     | Two        | Three   | Four          |
| ------------- |-------------| ---------| ------------- |
| Span Across |||a {T: @colspan=3}|

|          Grouping {T: @colspan=3 @class=bordered}            |  First Header {T: @class=bordered}  | Second Header {T: @class=bordered} | Third Header {T: @class=bordered} |
 ------------ | :-----------: | :-----------: | :---------: | :---------: | ---------:
Content {T: @rowspan=2 @class=bordered}  | *Long Cell* {T: @colspan=5 @class=bordered} ||
**Cell** {T: @colspan=3 @class=bordered} |  Cell {T: @colspan=2 @class=bordered}        |
One {T: @class=bordered} |two {T: @class=bordered} |three {T: @class=bordered} |four {T: @class=bordered} |five {T: @class=bordered} |six {T: @class=bordered}

Colons can be used to align columns.

| Stripped Tables {T: @class=stripped$bordered} | Centered {T: @class=stripped$bordered} | Right aligned {T: @class=stripped$bordered} |
| --------------------------------- |:--------------------------:| -------------------------------:|
| Zebra ăĂîÎâÂşŞţŢșȘțȚ {T: @class=stripped}           | c1.2 {T: @class=stripped}     | $1600 {T: @class=stripped}         |
| Stripes {T: @class=stripped}         | c2.2 {T: @class=stripped}     |   $12 {T: @class=stripped}         |
| zebra stripes {T: @class=stripped$bordered}   | c2.3 {T: @class=stripped$bordered}     |    $1 {T: @class=stripped$bordered}         |

First Header  | Second Header
------------- | -------------
Content Cell  | Content Cell
Content Cell  | Content Cell

|{!DEF!=AUTO-WIDTH;.dbordered;.stripped;#tbl-one} Name   | Description     |
| ------------- | -----------------------  |
| Help          | Display the help window. |
| Close         | Closes a window          |

| Name | Description |
| ----- | ----- |
| Action ^Help^ | ~~Display the ăĂîÎâÂşŞţŢșȘțȚ~~ help **window**.|
| Action ~Close~ | _Closes_ a window |

| Left-Aligned {T: @class=dbordered}  | Center Aligned {T: @class=dbordered}  | Right Aligned {T: @class=dbordered} |
| :------------ |:---------------:| -----:|
| col 3 is {T: @class=dbordered}      | some wordy text {T: @class=dbordered} | $1600 {T: @class=dbordered} |
| col 2 is {T: @class=dbordered}      | centered {T: @class=dbordered}        |   $12 {T: @class=dbordered} |
| zebra stripes {T: @class=dbordered} | are neat {T: @class=dbordered}        |    $1 {T: @class=dbordered} |

The outer pipes (|) are optional, and you don't need to make the raw Markdown line up prettily. You can also use inline Markdown.

Markdown {T: @class=dbordered} | Less {T: @class=dbordered} | Pretty {T: @class=dbordered}
--- | --- | ---
*Still* {T: @class=dbordered} | `renders` {T: @class=dbordered} | **nicely** {T: @class=dbordered}
1 {T: @class=dbordered} | 2 {T: @class=dbordered} | 3 {T: @class=dbordered}

> Blockquotes are very handy in email to emulate reply text.
> This line is part of the same quote.
> ###### H6
> ``` javascript
> let a = 2;
> const b = 3;
> var a = 4;
> class A {
>   constructor() {
>   }
> }
> ```

Quote break.

> This is a very long line that will still be quoted properly when it wraps. Oh boy let's keep writing to make sure this is long enough to actually wrap for everyone. Oh, you can *put* **Markdown** into a blockquote.
>> This is 2nd level
>>> and this is 3rd

### Use Hypens, Asterisks and Underscores

\-\-\-
\_\_\_
\*\*\*

### Horizontal Rules

- - -
Hyphens
---

***
Asterisks
* * *

Underscores
___

Here's a line for us to start with.

This line is separated from the one above by two newlines, so it will be a *separate paragraph*.

This line is also a separate paragraph, but...
This line is only separated by a single newline, so it's a separate line in the *same paragraph*.

![Inline Base64 Image](data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiB2aWV3Qm94PSIwIDAgNzIuMjQ4ODkzIDcyLjI0ODg5MyIgaWQ9InNpZ24taW5mbyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkIj4gPGRlZnMgaWQ9ImRlZnM0Ij4gIDxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyIgaWQ9InN0eWxlNiIgLz4gPC9kZWZzPiA8ZyBpZD0idGV4dDM3ODEiIHN0eWxlPSJmaWxsOiNmZmZmZmY7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7Ij4gIDxnIGlkPSJnMzAwOSI+ICAgPHBhdGggZD0ibSA2Mi45MzA1NzMsMzYuMTI0NDQ2IGEgMjYuODA2MTI4LDI2LjgwNjEyOCAwIDEgMSAtNTMuNjEyMjUzNSwwIDI2LjgwNjEyOCwyNi44MDYxMjggMCAxIDEgNTMuNjEyMjUzNSwwIHoiIGlkPSJwYXRoMzAxMSIgc3R5bGU9ImZpbGw6IzNiNTk5ODtmaWxsLW9wYWNpdHk6MTtmaWxsLXJ1bGU6ZXZlbm9kZDtzdHJva2U6IzNiNTk5ODtzdHJva2Utd2lkdGg6MTQuMTIxMDg1MTc7c3Ryb2tlLW9wYWNpdHk6MSIgLz4gICA8cGF0aCBkPSJtIDQwLjA1Mjk3OSwyOS44NjU4ODYgLTEwLjQwMzg0LDAgMCwxLjMwMDQ4IGMgMi4zODQyMTEsMC40ODc2NzkgMi44NzE4OTMsMC45NzUzNjIgMi44NzE4OTMsMy4xOTcwMTMgbCAwLDE1LjkzMDg4IGMgMCwyLjIyMTY1MiAtMC4zNzkzMDksMi42NTUxNDggLTIuODcxODkzLDMuMjUxMjAxIGwgMCwxLjMwMDQ4IDEyLjk1MDYxNCwwIDAsLTEuMzAwNDggYyAtMS44OTY1MzIsLTAuMjcwOTMzIC0yLjU0Njc3NCwtMS4wMjk1NDkgLTIuNTQ2Nzc0LC0zLjA4ODY0MSBsIDAsLTIwLjU5MDkzMyBNIDM2LjI1OTkxMiwxNy40MDI5NTIgYyAtMi4zMzAwMjQsMCAtNC4yMjY1NiwxLjg5NjUzNSAtNC4yMjY1Niw0LjE3MjM3MyAwLDIuNDM4Mzk4IDEuNzg4MTYzLDQuMjI2NTYgNC4xNzIzNzQsNC4yMjY1NiAyLjM4NDIxMSwwIDQuMjI2NTYsLTEuNzg4MTYyIDQuMjI2NTYsLTQuMTcyMzczIDAsLTIuMzMwMDI1IC0xLjg0MjM0OSwtNC4yMjY1NiAtNC4xNzIzNzQsLTQuMjI2NTYiIGlkPSJwYXRoMzAwNiIgc3R5bGU9ImZpbGw6I2ZmZmZmZjsiIC8+ICA8L2c+IDwvZz48L3N2Zz4= "Inline Base64 Image")

![Alternate Text](https://www.gstatic.com/webp/gallery/1.sm.jpg "Sample Alternate Image: Jpeg and Webp with Unveil"){I: .testClass1 .testClass2 #testID @data-test=Sample$Image %unveil %alternate=https://www.gstatic.com/webp/gallery/1.sm.webp$image/webp}

[![IMAGE ALT TEXT HERE](//img.youtube.com/vi/4rUrYN4cnGs/0.jpg) {I:%unveil}](//www.youtube.com/watch?v=4rUrYN4cnGs "Sample Video Preview Image with Unveil"){L:.video-link #link .Extra-Class @target=_blank}

Fenced code blocks

```javascript
function test() {
  console.log("notice the blank line before this function?");
}
```

Set in stone
------------

Preformatted blocks are useful for ASCII art:

```
             ,-.
    ,     ,-.   ,-.
   / \   (   )-(   )
   \ |  ,.>-(   )-<
    \|,' (   )-(   )
     Y ___`-'   `-'
     |/__/   `-'
     |
     |
     |    -hrr-
  ___|_____________
```

External linking action
--------------------

The top search engines are: [Google] [1] and also
[Bing] [2] or [Yandex] [3].

  [1]: https://google.com/        "Google"
  [2]: //www.bing.com/            "Bing"
  [3]: http://yandex.com/         "Yandex"


[](#) {L: @id=anchor}

## Sample Data Definition

```html
<dl>
  <dt>Definition list</dt>
  <dd>Is something people use sometimes.</dd>

  <dt>Markdown in HTML</dt>
  <dd>Does *not* work **very** well. Use HTML <em>tags</em>.</dd>
</dl>
```

**Below should be rendered as plain HTML (HTML tags are disabled in this Markdown Parser ...)**
<!-- the code below should be escaped -->
<dl><!-- this is a comment -->
  <!-- this is a nother comment -->
  <dt>Definition list</dt>
  <dd>Is something people use sometimes.</dd>
<!-- and another
multiline
comment -->
  <dt>Markdown in HTML</dt>
  <dd>Does *not* work **very** well. Use HTML <em>tags</em>.</dd>
</dl>