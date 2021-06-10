# Prism: languages - Documentation

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

languages
=========

[Prism](Prism.html). languages
------------------------------

Source:

*   [prism-core.js](prism-core.js.html), [line 257](prism-core.js.html#line257)

This namespace contains all currently loaded languages and the some helper functions to create and modify languages.

### Methods

#### (static) extend(id, redef) → {[Grammar](global.html#Grammar)}

Source:

*   [prism-core.js](prism-core.js.html), [line 286](prism-core.js.html#line286)

Creates a deep copy of the language with the given id and appends the given tokens.

If a token in `redef` also appears in the copied language, then the existing token in the copied language will be overwritten at its original position.

Best practices
--------------

Since the position of overwriting tokens (token in `redef` that overwrite tokens in the copied language) doesn't matter, they can technically be in any order. However, this can be confusing to others that trying to understand the language definition because, normally, the order of tokens matters in Prism grammars.

Therefore, it is encouraged to order overwriting tokens according to the positions of the overwritten tokens. Furthermore, all non-overwriting tokens should be placed after the overwriting ones.

##### Example

    Prism.languages['css-with-colors'] = Prism.languages.extend('css', {
        // Prism.languages.css already has a 'comment' token, so this token will overwrite CSS' 'comment' token
        // at its original position
        'comment': { ... },
        // CSS doesn't have a 'color' token, so this token will be appended
        'color': /\b(?:red|green|blue)\b/
    });

##### Parameters:

Name

Type

Description

`id`

string

The id of the language to extend. This has to be a key in `Prism.languages`.

`redef`

[Grammar](global.html#Grammar)

The new tokens to append.

##### Returns:

The new language created.

Type

[Grammar](global.html#Grammar)

#### (static) insertBefore(inside, before, insert, rootopt) → {[Grammar](global.html#Grammar)}

Source:

*   [prism-core.js](prism-core.js.html), [line 371](prism-core.js.html#line371)

Inserts tokens _before_ another token in a language definition or any other grammar.

Usage
-----

This helper method makes it easy to modify existing languages. For example, the CSS language definition not only defines CSS highlighting for CSS documents, but also needs to define highlighting for CSS embedded in HTML through `<style>` elements. To do this, it needs to modify `Prism.languages.markup` and add the appropriate tokens. However, `Prism.languages.markup` is a regular JavaScript object literal, so if you do this:

    Prism.languages.markup.style = {
        // token
    };


then the `style` token will be added (and processed) at the end. `insertBefore` allows you to insert tokens before existing tokens. For the CSS example above, you would use it like this:

    Prism.languages.insertBefore('markup', 'cdata', {
        'style': {
            // token
        }
    });


Special cases
-------------

If the grammars of `inside` and `insert` have tokens with the same name, the tokens in `inside`'s grammar will be ignored.

This behavior can be used to insert tokens after `before`:

    Prism.languages.insertBefore('markup', 'comment', {
        'comment': Prism.languages.markup.comment,
        // tokens after 'comment'
    });


Limitations
-----------

The main problem `insertBefore` has to solve is iteration order. Since ES2015, the iteration order for object properties is guaranteed to be the insertion order (except for integer keys) but some browsers behave differently when keys are deleted and re-inserted. So `insertBefore` can't be implemented by temporarily deleting properties which is necessary to insert at arbitrary positions.

To solve this problem, `insertBefore` doesn't actually insert the given tokens into the target object. Instead, it will create a new object and replace all references to the target object with the new one. This can be done without temporarily deleting properties, so the iteration order is well-defined.

However, only references that can be reached from `Prism.languages` or `insert` will be replaced. I.e. if you hold the target object in a variable, then the value of the variable will not change.

    var oldMarkup = Prism.languages.markup;
    var newMarkup = Prism.languages.insertBefore('markup', 'comment', { ... });

    assert(oldMarkup !== Prism.languages.markup);
    assert(newMarkup === Prism.languages.markup);


##### Parameters:

Name

Type

Attributes

Description

`inside`

string

The property of `root` (e.g. a language id in `Prism.languages`) that contains the object to be modified.

`before`

string

The key to insert before.

`insert`

[Grammar](global.html#Grammar)

An object containing the key-value pairs to be inserted.

`root`

Object.<string, any>

<optional>

The object containing `inside`, i.e. the object that contains the object to be modified.

Defaults to `Prism.languages`.

##### Returns:

The new grammar object.

Type

[Grammar](global.html#Grammar)
