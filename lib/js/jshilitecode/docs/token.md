# Prism: Token - Documentation

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

Token
=====

Token
-----

#### new Token(type, content, aliasopt, matchedStropt)

Source:

*   [prism-core.js](prism-core.js.html), [line 726](prism-core.js.html#line726)

Creates a new token.

##### Parameters:

Name

Type

Attributes

Default

Description

`type`

string

See [`type`](Token.html#type)

`content`

string | [TokenStream](global.html#TokenStream)

See [`content`](Token.html#content)

`alias`

string | Array.<string>

<optional>

The alias(es) of the token.

`matchedStr`

string

<optional>

`""`

A copy of the full string this token was created from.

### Members

#### alias :string|Array.<string>

Source:

*   [prism-core.js](prism-core.js.html), [line 753](prism-core.js.html#line753)

See:

*   [GrammarToken](global.html#GrammarToken)

The alias(es) of the token.

##### Type:

*   string | Array.<string>

#### content :string|[TokenStream](global.html#TokenStream)

Source:

*   [prism-core.js](prism-core.js.html), [line 745](prism-core.js.html#line745)

The strings or tokens contained by this token.

This will be a token stream if the pattern matched also defined an `inside` grammar.

##### Type:

*   string | [TokenStream](global.html#TokenStream)

#### type :string

Source:

*   [prism-core.js](prism-core.js.html), [line 736](prism-core.js.html#line736)

See:

*   [GrammarToken](global.html#GrammarToken)

The type of the token.

This is usually the key of a pattern in a [`Grammar`](global.html#Grammar).

##### Type:

*   string
