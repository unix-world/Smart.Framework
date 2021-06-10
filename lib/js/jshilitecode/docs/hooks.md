# Prism: hooks - Documentation

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

hooks
=====

[Prism](Prism.html). hooks
--------------------------

Source:

*   [prism-core.js](prism-core.js.html), [line 660](prism-core.js.html#line660)

### Methods

#### (static) add(name, callback)

Source:

*   [prism-core.js](prism-core.js.html), [line 675](prism-core.js.html#line675)

Adds the given callback to the list of callbacks for the given hook.

The callback will be invoked when the hook it is registered for is run. Hooks are usually directly run by a highlight function but you can also run hooks yourself.

One callback function can be registered to multiple hooks and the same hook multiple times.

##### Parameters:

Name

Type

Description

`name`

string

The name of the hook.

`callback`

[HookCallback](global.html#HookCallback)

The callback function which is given environment variables.

#### (static) run(name, env)

Source:

*   [prism-core.js](prism-core.js.html), [line 692](prism-core.js.html#line692)

Runs a hook invoking all registered callbacks with the given environment variables.

Callbacks will be invoked synchronously and in the order in which they were registered.

##### Parameters:

Name

Type

Description

`name`

string

The name of the hook.

`env`

Object.<string, any>

The environment variables of the hook passed to all callbacks registered.
