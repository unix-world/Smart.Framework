# [%%%IF:TITLE:==;%%%]... ==Title is empty== ...[%%%ELSE:TITLE%%%][###TITLE|html###][%%%/IF:TITLE%%%]

&copy;&nbsp;&nbsp;unix-world.org
Extended Markdown v.II (smart flavour + markers + compatibility) // Test :: r.20250118

# (H1) Markdown v2 (smart flavour) comes with many new features and optimizations ; however few old "bad" features have been dropped, and others supported ==only in compatibility mode== ...

## (H2) "Unicode" &reg; <Test> ăĂîÎâÂşŞţŢ &quot;&nbsp;&quot; &lt;&quot;&gt;

### (H3) 'Alternate Unicode' <Test> ăĂîÎâÂșȘțȚ

#### H4 {H:.myClass #myID}

##### H5 {H:@data-id=h-5}

###### H6 {H:@style=display:inline-block}

::: .operation_info
```javascript
var test = true;
```
:::

::: .operation_notice .test_class_notice #test-div-notice
**This is a**
**==notice==** <a>bc
with an icon ![ICON](SFI-ICON "sfi sfi-leaf sfi-2x")
and a button with icon
[![A button link with icon](SFI-ICON "sfi sfi-leaf")](/#==test== "Title ..."){L:@target=test @data-smart=open.popup .ux-button .ux-button-dark}
:::

::: .operation_important .test_class_important #test-div-important
This is ==important==
:::
\
::: @article-div
This is an article DIV &nbsp; [A Button Link](/#==test== ""){L: .ux-button @=_blank}
:::

::: @section-div
This is a section DIV
:::
\
Safe html escapes \\, below:
<Line1 <i>> 1
&Line2 &copy; <i>& 2
`Line3 <i>` $$variable$$ as: \$\$variable\$\$ or if variable contains dollar sign: $$\$3$$ as \$\$\\$3\$\$ <a>
\
<Line4> 4
"Line5 <i> 5" <a>
 &Line6

[Go To <anchor>](#anchor)

ABC ```abc``` \`\`\`zzz\`\`\` ```yy\!y```
DEF ``def`` \`\`www\`\` ``qq\!q``
```ABCD```
CITE ??cite term?? as: \?\?cite term\?\? or alternate, with HTML entities: &quest;?cite term?&quest;


A math formula
::: .math-latex
\mathtt{\operatorname{Math.sinh(x)}} = \frac{e^x - e^{-x}}{2}
:::


A preformat:
~~~~
This is a pre-"formated" <text>
and this is another line ...
~~~~

Empty pre, below:
~~~
~~~

```
```

```
```

PHP code (PHP Start-Tag and PHP End-Tag must be removed ...)
```php
<?php

// php sample code
function test() {
	$escape = 'htmlspecialchars'; // !! this is a comment !!
	echo $escape('this is $$ <a> $$ "test\\," &copy; ...'."\n");
}

test();

?>
```


##### ==Alternate Style== Heading 1 with test, to avoid parse as # headers**
======

###### __Alternate Style__ Heading 2 with **test**, to avoid **parse** <b>as</b> # headers
------


**Strong emphasis**, aka \*\*bold\*\*, with asterisks.
==Emphasis==, aka \=\=italics\=\= with equals ; but also supports old behaviour such as \_italic\_ _italic_
Combined **emphasis** (**bold**) with ==italics== as: **bolded ==italic==** or ==italic **bolded**==

The same with \_\_underlines\_\_ as __underlines__ with underdashes
This syntax break compatibility with v1, in v1 double underscores are used for bold with the same functionality like double stars ; thus in v1 \*\*bold\*\* is the same like \_\_bold\_\_
But in v2 \*\*bold\*\* is bold and \_\_underline\_\_ is underline ...

Strikethrough uses \~\~tildes\~\~ like ~~Example StrikeThrough~~


Syntax was extended in v2 by: Inserts, Deletions, Highlights
--deletion-- is \-\-delete\-\-
++insert++ is \+\+insert\+\+
``highlight`` is \`\`highlight\`\`


This is a !!Subscript!! as \!\!this\!\! ... ; but also supports old syntax (v1) such as CO~2~ as CO\~2\~
And this is a ^^Superscript^^ as \^\^this\^\^ ... ; but also supports old syntax (v1) such as E=mc^2^ as E=mc\^2\^
Examples:
CO!!2!! for subscript as CO\!\!2\!\!
E=mc^^2^^ for superscript as E=mc\^\^2\^\^

,,A quotation,, as \,\,Quote\,\,

- - -
some text **aligned on left ``==(default)==``**

|{T:@class=hidden}|
| :---: |
| some **centered** text ; ``in v2 this can be done also by using div with a class`` |

|{T: @class=hidden}|
| ---: |
| some text **aligned on right** ; ``in v2 this can be done also by using div with a class`` |
\
|{!DEF!=.dbordered} ||
| :--- | :--- |
| #### Heading inside !!table cell!! | ##### Another \| Heading inside ^^table cell^^ |

- - -
## Lists
* * *

1. a
	4. **aa**
	3. ==ab==
	2. __ac__
	1. ~~ac~~
1. b
	- x
		* x1
			- xx^^1^^
			- xx!!2!!
			- xx3
		- x2
			1. xx\^\^a\^\^
			1. xx\!\!b\!\!
			1. xxc
		+ x3
			1) x3``**a**``
			1) x3b ```var z = 2;```
			1) x3c
			1) x3d
	+ y
	* z
1. c

1. a
	1. aa
	2. ab
	3. ac
1. b
	+ x
		- x1
			* xx1
			* xx2
			* xx3
		- x2
			1. xxa
			1. xxb
			1. xxc
		- x3
			1) x3a
			1) x3b
			1) x3c
			1) x3dd
1. cc

1. a
	1. aa
	2. ab
	3. ac
1. b
	1. x
		- x1
			- xx1
			- xx2
			- xx3
		- x2
			1. xxa
			1. xxb
			1. xxc
				1. z1
				1. z2
				1. z3
		- x3
			1) x3a
			1) x3b
			1) x3c
			1) x3dd
				+ y3a
				+ y3b
				+ y3cc
1. cc

1) alt1
	+ one
	+ two
1) alt2
1) alt3

1) aalt1
2) aalt2
3) aalt3

* one
* two
* three

1. First ordered <list> "item"
#### This is a header inside list
![Alt Txt](https://www.gstatic.com/webp/gallery/1.sm.jpg "Sample Img"){I: @width=100}
```
This is some code inside list
```
~~~
	This is preformat inside list
~~~
::: @div-divs .operation_info
This is a div  inside list
:::
		``And this is an old version another preformat, inside li (space/tab indented: unsupported)``
	::: @div-paragraphs
		``This is an old style div inside preformat, inside li (space/tab indented: unsupported)``
	:::
<<<
BlockQuote inside List
<<<
2. Another <"item">
	+ Unordered sub-list.
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

+ Unordered <list> can
	use "**asterisks**"
- Or minuses
- ###### OR H6 {H:@style=display:inline-block}
####### This is a span{H: @id=mySpan}
######## This is a data tag{H: @data-value=myData}

Some Paragraph
	Some Preformat area
	Line ...
		Line ...

[Link with attributes link](http://netbsd.org) {L: .primary9 #link .Upper-Case @data-smart=open.modal$700$300}

my email is <me@example.com>

[I'm an inline-style ```link```](https://www.google.com)

[Inline link ](?#!/url-(parentheses)) with parentheses in URL
[Inline email link ](mailto:test@local)

[I'm an inline-style link with title](https://www.google.com "Google's Homepage")
[I'm an inline-style link with title](https://www.google.com "Google's <span>Homepage<span>")

[I'm a reference-style link][Arbitrary case-insensitive reference text]

[`I'm a relative reference to a repository file`](lib/license_bsd.txt)

[You can use numbers for reference-style link definitions][1]

Or leave it empty and use the [link text itself]

[![Logo](lib/framework/img/sf-logo.svg "Slimbox"){I:@width=256 @height=256}](lib/framework/img/sf-logo.svg){L:@data-slimbox=slimbox}
[![Logo](lib/framework/img/sf-logo.svg)](http://demo.unix-world.org/smart-framework "icon with text")

Some text to show that the reference links can follow later.

[arbitrary case-insensitive reference text]: https://www.mozilla.org
[1]: http://slashdot.org
[link text itself]: http://www.reddit.com

Here's our logo (hover to see the title text):

Inline-style:
![use the alt text also for title to avoid duplicate](https://parsedown.org/md.png "=@.") {I: @width=100 @style=box-shadow:$10px$10px$5px$#888888;}

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
\
| {!DEF!=NO-TABLE-HEAD;ALIGN-HEAD-CENTER} One {T:@class=bordered}     | Two {T:@class=bordered}        | Three {T: @class=bordered}   | Four {T: @class=bordered}         |
| ------------- |-------------| ---------| ------------- |
| One {T: @class=bordered}     | Two {T: @class=bordered}        | Three {T: @class=bordered}   | Four {T: @class=bordered}         |

| One     | Two        | Three   | Four          |
| ------------- |-------------| ---------| ------------- |
| Span Across |||a {T: @colspan=3}|

| {!DEF!=.dbordered} One     | Two        | Three   | Four          |
| ------------- |-------------| ---------| ------------- |
| Span Across simple |a {T: @colspan=3}|

|          Grouping {T: @colspan=3 @class=bordered}            |  First Header {T: @class=bordered}  | Second Header {T: @class=bordered} | Third Header {T: @class=bordered} |
| ------------ | :-----------: | :-----------: | :---------: | :---------: | ---------: |
| ``Content`` {T: @rowspan=2 @class=bordered}  | ==Long Cell== {T: @colspan=5 @class=bordered} |||
| **Cell** {T: @colspan=3 @class=bordered} |  ~~Cell~~ {T: @colspan=2 @class=bordered}        | |
| ++One++ {T: @class=bordered} |--two-- {T: @class=bordered} |__three__ {T: @class=bordered} |four^^4^^ {T: @class=bordered} |five!!5!! {T: @class=bordered} |six ```// have some code``` {T: @class=bordered} |

Colons can be used to align columns.

| Stripped Tables {T: @class=stripped$bordered} | Centered {T: @class=stripped$bordered} | Right aligned {T: @class=stripped$bordered} |
| --------------------------------- |:--------------------------:| -------------------------------:|
| Zebra ┆ ăĂîÎâÂşŞţŢșȘțȚ {T: @class=stripped}           | c1.2 {T: @class=stripped}     | $1600 {T: @class=stripped}         |
| Stripes {T: @class=stripped}         | c2.2 {T: @class=stripped}     |   $12 {T: @class=stripped}         |
| zebra stripes {T: @class=stripped$bordered}   | c2.3 {T: @class=stripped$bordered}     |    $1 {T: @class=stripped$bordered}         |

| First Header  | Second Header |
| ------------- | ------------- |
| Content Cell  | Content Cell  |
| Content Cell  | Content Cell  |

|{!DEF!=AUTO-WIDTH;.dbordered;.stripped;#tbl-one} Name   | Description     |
| ------------- | -----------------------  |
| Help          | Display the help&BREAK;window. |
| Close         | Closes a window          |

| Name | Description |
| ----- | ----- |
| Action ^Help^ | ~~Display the ăĂîÎâÂşŞţŢșȘțȚ~~ help **window**.|
| Action ~Close~ | _Closes_ a window |

| Left-Aligned {T: @class=pbordered}  | Center Aligned {T: @class=pbordered}  | Right Aligned {T: @class=pbordered} |
| :------------ |:---------------:| -----:|
| col 3 is {T: @class=pbordered}      | some wordy text {T: @class=pbordered} | $1600 {T: @class=pbordered} |
| col 2 is {T: @class=pbordered}      | centered {T: @class=pbordered}        |   $12 {T: @class=pbordered} |
| zebra stripes {T: @class=pbordered} | are neat {T: @class=pbordered}        |    $1 {T: @class=pbordered} |

You can also use inline Markdown in tables.

| Markdown {T: @class=dbordered} | Less {T: @class=dbordered} | Pretty {T: @class=dbordered} |
| --- | --- | --- |
| *Still* {T: @class=dbordered} | `renders` {T: @class=dbordered} | **nicely** {T: @class=dbordered} |
| 1 {T: @class=dbordered} | 2 {T: @class=dbordered} | 3 {T: @class=dbordered} |

> # Blockquotes are very handy in email to emulate reply text.
> This line is part of the same quote.
> ###### H6
> ``` javascript
> let a = 2;
> const b = 3;
> var x = 4;
> class A {
>   constructor() {
>   }
> }
> ```

> # Another block quote
> This is **the end** of ==2nd quote==
> |{!DEF!=.dbordered} with a table | within |
> | and another table line | ... |
> .

And another Quote example, inside a div

::: .operation_info
> This is a very long line that will still be quoted properly when it wraps. Oh boy let's keep writing to make sure this is long enough to actually wrap for everyone. Oh, you can *put* **Markdown** into a blockquote.
>> This is 2nd level
>>> and this is 3rd
:::

## And a v2 Quote

<<<
::: .operation_important
This is a Markdown v2 Div inside a v2 BlockQuote
``The blockquote v2 supports all the syntax ^^inside^^ !``
==Althought in Markdown v2 also the v1 Blockquote supports all the syntax inside but is much harder to write it !!inside!! ;-)==
:::
# Line 1
## Line 2
### Line 3
#### Line 4
##### Line 5
###### Line 6

1) alt1
	+ one
	+ two
1) alt2
1) alt3

* a list
	- within
	+ \+\+\+\+\+\+
And some code below
### some header
```javascript
let a = 2;
const b = 3;
var x = 4;
class A {
  constructor() {
  }
}
```
\
|{!DEF!=AUTO-WIDTH;.dbordered;.stripped;#tbl-two} Name   | Description     |
| ------------- | -----------------------  |
| Help          | Display the help window. |
| Close         | Closes a window          |

~~~
This is a preformat inside blockquote
pre line 2 ++++/----

[**Test** Link with Syntax &#039;<a>&#039; &lt;br&gt; &copy; &#039;OK&#039;](http://w3soft.org "=@."){L: %blank}
![Alt Txt](https://www.gstatic.com/webp/gallery/1.sm.jpg "Sample Img"){I: @width=100}
~~~
<<<

### Horizontal Rules

---
Hyphens
-\-\-
___
Underscores
\_\_\_
***
Asterisks
\*\*\*

- - -
Hyphens with spaces
\- \- \-

* * *
Asterisks wit spaces
\* \* \*

\
;;;
:::
Div start
::::
... and Sub-Div start
####### Span TAG
######## Dfn TAG
... Sub-Div #end
::::
Div End
:::


This line is separated from the one above by two newlines, so it will be a breakline.
;;;;
This line is also a separate paragraph, but...
This line is only separated by a single newline, so it's a separate line in the *same paragraph*.
::: .flexbox
Some **text** before image ![Inline Base64 Image](data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiB2aWV3Qm94PSIwIDAgNzIuMjQ4ODkzIDcyLjI0ODg5MyIgaWQ9InNpZ24taW5mbyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkIj4gPGRlZnMgaWQ9ImRlZnM0Ij4gIDxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyIgaWQ9InN0eWxlNiIgLz4gPC9kZWZzPiA8ZyBpZD0idGV4dDM3ODEiIHN0eWxlPSJmaWxsOiNmZmZmZmY7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7Ij4gIDxnIGlkPSJnMzAwOSI+ICAgPHBhdGggZD0ibSA2Mi45MzA1NzMsMzYuMTI0NDQ2IGEgMjYuODA2MTI4LDI2LjgwNjEyOCAwIDEgMSAtNTMuNjEyMjUzNSwwIDI2LjgwNjEyOCwyNi44MDYxMjggMCAxIDEgNTMuNjEyMjUzNSwwIHoiIGlkPSJwYXRoMzAxMSIgc3R5bGU9ImZpbGw6IzNiNTk5ODtmaWxsLW9wYWNpdHk6MTtmaWxsLXJ1bGU6ZXZlbm9kZDtzdHJva2U6IzNiNTk5ODtzdHJva2Utd2lkdGg6MTQuMTIxMDg1MTc7c3Ryb2tlLW9wYWNpdHk6MSIgLz4gICA8cGF0aCBkPSJtIDQwLjA1Mjk3OSwyOS44NjU4ODYgLTEwLjQwMzg0LDAgMCwxLjMwMDQ4IGMgMi4zODQyMTEsMC40ODc2NzkgMi44NzE4OTMsMC45NzUzNjIgMi44NzE4OTMsMy4xOTcwMTMgbCAwLDE1LjkzMDg4IGMgMCwyLjIyMTY1MiAtMC4zNzkzMDksMi42NTUxNDggLTIuODcxODkzLDMuMjUxMjAxIGwgMCwxLjMwMDQ4IDEyLjk1MDYxNCwwIDAsLTEuMzAwNDggYyAtMS44OTY1MzIsLTAuMjcwOTMzIC0yLjU0Njc3NCwtMS4wMjk1NDkgLTIuNTQ2Nzc0LC0zLjA4ODY0MSBsIDAsLTIwLjU5MDkzMyBNIDM2LjI1OTkxMiwxNy40MDI5NTIgYyAtMi4zMzAwMjQsMCAtNC4yMjY1NiwxLjg5NjUzNSAtNC4yMjY1Niw0LjE3MjM3MyAwLDIuNDM4Mzk4IDEuNzg4MTYzLDQuMjI2NTYgNC4xNzIzNzQsNC4yMjY1NiAyLjM4NDIxMSwwIDQuMjI2NTYsLTEuNzg4MTYyIDQuMjI2NTYsLTQuMTcyMzczIDAsLTIuMzMwMDI1IC0xLjg0MjM0OSwtNC4yMjY1NiAtNC4xNzIzNzQsLTQuMjI2NTYiIGlkPSJwYXRoMzAwNiIgc3R5bGU9ImZpbGw6I2ZmZmZmZjsiIC8+ICA8L2c+IDwvZz48L3N2Zz4= "Inline Base64 Image") and some ``text`` after
:::
![Alternate Text](https://www.gstatic.com/webp/gallery/1.sm.jpg "Sample Alternate Image: Jpeg and Webp with Unveil"){I: .testClass1 .testClass2 #testID @data-test=Sample$Image %lazyload=unveil %alternate=https://www.gstatic.com/webp/gallery/1.sm.webp$image/webp}
;;;;

;;;


### Sample Video
[![IMAGE ALT TEXT HERE](//img.youtube.com/vi/4rUrYN4cnGs/0.jpg) {I:%lazyload=unveil}](//www.youtube.com/watch?v=4rUrYN4cnGs "Sample Video Preview Image with Unveil"){L:.video-link #link-video .Extra-Class @target=_blank}
\
![Sample Video OGG](https://www.w3schools.com/html/mov_bbb.ogg){I: #video-1 %video=ogg @width=320 @height=176 @controls=none}
![Sample Video Webm/MP4](https://www.w3schools.com/html/mov_bbb.webm$https://www.w3schools.com/html/mov_bbb.mp4){I: #video-2 %video=webm$mp4 @width=320 @height=176 @preload=none @poster=https://www.w3schools.com/images/w3html5.gif}
\
### Sample Audio
![Sample Audio OGG/MP3](https://www.w3schools.com/html/horse.ogg$https://www.w3schools.com/html/horse.mp3){I: #audio-1 %audio=ogg$mpeg}


## Fenced code blocks

```javascript
function test() {
  console.log("notice the blank line before this function?");
}
```


## Set in stone

Preformatted blocks are useful for ASCII art:

~~~~
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
~~~~
\
## External linking action

The top search engines are: [Google] [1] and also
[Bing] [2] or [Yandex] [3].

  [1]: https://google.com/        "Google"
  [2]: //www.bing.com/            "Bing"
  [3]: http://yandex.com/         "Yandex"


[](#) {L: @id=anchor}
\
## Sample Data Definition

```html
<dl>
  <dt>Definition list</dt>
  <dd>Is something people use sometimes.</dd>

  <dt>Markdown in HTML</dt>
  <dd>Does *not* work **very** well. Use HTML <em>tags</em>.</dd>
</dl>
```


### Below should be rendered as plain HTML (HTML tags are disabled in this Markdown Parser ...)
~~~
<!-- the content in preformats should not be escaped, like in code, everything is preserved exactly as it is, there is no inline parsing here -->
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
~~~

* * *
\
##### Test code injection:
![alt text](https://github.com/adam-p/markdown-here/raw/master/src/common/images/icon48.png?#time123456&now98765&a=<"> "Logo Title Text 1")
<http://#inline.url.tag?#time123456&now98765&c="&a=<">> // **no more supported in v2**
<http://#inline.url.tag2> // **no more supported in v2**
<">

- - -

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

- - -


|||
| --- | --: |
|# END| (test for last line flushing ...) |