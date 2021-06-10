# Prism - Documentation

[GitHub](https://github.com/PrismJS/prism)
------------------------------------------

### Classes

*   [Token](Token.html)

### Namespaces

*   [Prism](Prism.html)

    *   [manual](Prism.html#.manual)

    *   [highlight](Prism.html#.highlight)
    *   [highlightAll](Prism.html#.highlightAll)
    *   [highlightAllUnder](Prism.html#.highlightAllUnder)
    *   [highlightElement](Prism.html#.highlightElement)
    *   [tokenize](Prism.html#.tokenize)
*   [hooks](Prism.hooks.html)
    *   [add](Prism.hooks.html#.add)
    *   [run](Prism.hooks.html#.run)
*   [languages](Prism.languages.html)
    *   [extend](Prism.languages.html#.extend)
    *   [insertBefore](Prism.languages.html#.insertBefore)

### Global

*   [Grammar](global.html#Grammar)
*   [GrammarToken](global.html#GrammarToken)
*   [HighlightCallback](global.html#HighlightCallback)
*   [HookCallback](global.html#HookCallback)
*   [TokenStream](global.html#TokenStream)

Prism
=====

Prism
-----

Source:

*   [prism-core.js](prism-core.js.html), [line 19](prism-core.js.html#line19)

Author:

*   Lea Verou <https://lea.verou.me>

License:

*   MIT

Prism: Lightweight, robust, elegant syntax highlighting

### Namespaces

[hooks](Prism.hooks.html)

[languages](Prism.languages.html)

### Members

#### (static) manual :boolean

Source:

*   [prism-core.js](prism-core.js.html), [line 48](prism-core.js.html#line48)

Default Value:

*   false

By default, Prism will attempt to highlight all code elements (by calling [`Prism.highlightAll`](Prism.html#.highlightAll)) on the current page after the page finished loading. This might be a problem if e.g. you wanted to asynchronously load additional languages or plugins yourself.

By setting this value to `true`, Prism will not automatically highlight all code elements on the page.

You obviously have to change this value before the automatic highlighting started. To do this, you can add an empty Prism object into the global scope before loading the Prism script like this:

    window.Prism = window.Prism || {};
    Prism.manual = true;
    // add a new <script> to load Prism's script


##### Type:

*   boolean

### Methods

#### (static) highlight(text, grammar, language) → {string}

Source:

*   [prism-core.js](prism-core.js.html), [line 601](prism-core.js.html#line601)

Low-level function, only use if you know what you’re doing. It accepts a string of text as input and the language definitions to use, and returns a string with the HTML produced.

The following hooks will be run:

1.  `before-tokenize`
2.  `after-tokenize`
3.  `wrap`: On each [`Token`](Token.html).

##### Example

    Prism.highlight('var foo = true;', Prism.languages.javascript, 'javascript');

##### Parameters:

Name

Type

Description

`text`

string

A string with the code to be highlighted.

`grammar`

[Grammar](global.html#Grammar)

An object containing the tokens to use.

Usually a language definition like `Prism.languages.markup`.

`language`

string

The name of the language definition passed to `grammar`.

##### Returns:

The highlighted HTML.

Type

string

#### (static) highlightAll(asyncopt, callbackopt)

Source:

*   [prism-core.js](prism-core.js.html), [line 448](prism-core.js.html#line448)

This is the most high-level function in Prism’s API. It fetches all the elements that have a `.language-xxxx` class and then calls [`Prism.highlightElement`](Prism.html#.highlightElement) on each one of them.

This is equivalent to `Prism.highlightAllUnder(document, async, callback)`.

##### Parameters:

Name

Type

Attributes

Default

Description

`async`

boolean

<optional>

`false`

Same as in [`Prism.highlightAllUnder`](Prism.html#.highlightAllUnder).

`callback`

[HighlightCallback](global.html#HighlightCallback)

<optional>

Same as in [`Prism.highlightAllUnder`](Prism.html#.highlightAllUnder).

#### (static) highlightAllUnder(container, asyncopt, callbackopt)

Source:

*   [prism-core.js](prism-core.js.html), [line 467](prism-core.js.html#line467)

Fetches all the descendants of `container` that have a `.language-xxxx` class and then calls [`Prism.highlightElement`](Prism.html#.highlightElement) on each one of them.

The following hooks will be run:

1.  `before-highlightall`
2.  `before-all-elements-highlight`
3.  All hooks of [`Prism.highlightElement`](Prism.html#.highlightElement) for each element.

##### Parameters:

Name

Type

Attributes

Default

Description

`container`

ParentNode

The root element, whose descendants that have a `.language-xxxx` class will be highlighted.

`async`

boolean

<optional>

`false`

Whether each element is to be highlighted asynchronously using Web Workers.

`callback`

[HighlightCallback](global.html#HighlightCallback)

<optional>

An optional callback to be invoked on each element after its highlighting is done.

#### (static) highlightElement(element, asyncopt, callbackopt)

Source:

*   [prism-core.js](prism-core.js.html), [line 513](prism-core.js.html#line513)

Highlights the code inside a single element.

The following hooks will be run:

1.  `before-sanity-check`
2.  `before-highlight`
3.  All hooks of [`Prism.highlight`](Prism.html#.highlight). These hooks will be run by an asynchronous worker if `async` is `true`.
4.  `before-insert`
5.  `after-highlight`
6.  `complete`

Some the above hooks will be skipped if the element doesn't contain any text or there is no grammar loaded for the element's language.

##### Parameters:

Name

Type

Attributes

Default

Description

`element`

Element

The element containing the code. It must have a class of `language-xxxx` to be processed, where `xxxx` is a valid language identifier.

`async`

boolean

<optional>

`false`

Whether the element is to be highlighted asynchronously using Web Workers to improve performance and avoid blocking the UI when highlighting very large chunks of code. This option is [disabled by default](https://prismjs.com/faq.html#why-is-asynchronous-highlighting-disabled-by-default).

Note: All language definitions required to highlight the code must be included in the main `prism.js` file for asynchronous highlighting to work. You can build your own bundle on the [Download page](https://prismjs.com/download.html).

`callback`

[HighlightCallback](global.html#HighlightCallback)

<optional>

An optional callback to be invoked after the highlighting is done. Mostly useful when `async` is `true`, since in that case, the highlighting is done asynchronously.

#### (static) tokenize(text, grammar) → {[TokenStream](global.html#TokenStream)}

Source:

*   [prism-core.js](prism-core.js.html), [line 637](prism-core.js.html#line637)

This is the heart of Prism, and the most low-level function you can use. It accepts a string of text as input and the language definitions to use, and returns an array with the tokenized code.

When the language definition includes nested tokens, the function is called recursively on each of these tokens.

This method could be useful in other contexts as well, as a very crude parser.

##### Example

    let code = `var foo = 0;`;
    let tokens = Prism.tokenize(code, Prism.languages.javascript);
    tokens.forEach(token => {
        if (token instanceof Prism.Token && token.type === 'number') {
            console.log(`Found numeric literal: ${token.content}`);
        }
    });

##### Parameters:

Name

Type

Description

`text`

string

A string with the code to be highlighted.

`grammar`

[Grammar](global.html#Grammar)

An object containing the tokens to use.

Usually a language definition like `Prism.languages.markup`.

##### Returns:

An array of strings and tokens, a token stream.

Type

[TokenStream](global.html#TokenStream)

