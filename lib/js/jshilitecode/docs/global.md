# Prism: Global - Documentation

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

Global
======

### Type Definitions

#### Grammar

Source:

*   [prism-core.js](prism-core.js.html), [line 1171](prism-core.js.html#line1171)

##### Properties:

Name

Type

Attributes

Description

`rest`

[Grammar](global.html#Grammar)

<optional>

An optional grammar object that will be appended to this grammar.

##### Type:

*   Object.<string, (RegExp|[GrammarToken](global.html#GrammarToken)|Array.<(RegExp|[GrammarToken](global.html#GrammarToken))>)>

#### GrammarToken

Source:

*   [prism-core.js](prism-core.js.html), [line 1150](prism-core.js.html#line1150)

##### Properties:

Name

Type

Attributes

Default

Description

`pattern`

RegExp

The regular expression of the token.

`lookbehind`

boolean

<optional>

`false`

If `true`, then the first capturing group of `pattern` will (effectively) behave as a lookbehind group meaning that the captured text will not be part of the matched text of the new token.

`greedy`

boolean

<optional>

`false`

Whether the token is greedy.

`alias`

string | Array.<string>

<optional>

An optional alias or list of aliases.

`inside`

[Grammar](global.html#Grammar)

<optional>

The nested grammar of this token.

The `inside` grammar will be used to tokenize the text value of each token of this kind.

This can be used to make nested and even recursive language definitions.

Note: This can cause infinite recursion. Be careful when you embed different languages or even the same language into each another.

The expansion of a simple `RegExp` literal to support additional properties.

#### HighlightCallback(element) → {void}

Source:

*   [prism-core.js](prism-core.js.html), [line 1179](prism-core.js.html#line1179)

A function which will invoked after an element was successfully highlighted.

##### Parameters:

Name

Type

Description

`element`

Element

The element successfully highlighted.

##### Returns:

Type

void

#### HookCallback(env) → {void}

Source:

*   [prism-core.js](prism-core.js.html), [line 1189](prism-core.js.html#line1189)

##### Parameters:

Name

Type

Description

`env`

Object.<string, any>

The environment variables of the hook.

##### Returns:

Type

void

#### TokenStream

Source:

*   [prism-core.js](prism-core.js.html), [line 758](prism-core.js.html#line758)

A token stream is an array of strings and [`Token`](Token.html) objects.

Token streams have to fulfill a few properties that are assumed by most functions (mostly internal ones) that process them.

1.  No adjacent strings.

2.  No empty strings.

    The only exception here is the token stream that only contains the empty string and nothing else.


##### Type:

*   Array.<(string|[Token](Token.html))>
