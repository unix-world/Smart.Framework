SimpleTree
https://github.com/innoq/simpletree

a simple jQuery plugin for collapsing nested lists, with support for checkbox
auto-selection

adhering to the principles of progressive enhancement, the tree is initialized
upon existing HTML/DOM structures

* demo: http://innoq.github.io/simpletree/
* test suite: http://innoq.github.io/simpletree/test/


Example
-------

    <ul class="hierarchy">
        <li>hello</li>
        <li>
            world
            <ul>
                <li>foo</li>
                <li>
                    bar
                    <ul>
                        <li>lorem</li>
                        <li>ipsum</li>
                    </ul>
                </li>
                <li>baz</li>
            </ul>
        </li>
    </ul>

<!-- -->

    jQuery("ul.hierarchy").simpletree();

The [demo](http://innoq.github.io/simpletree/) provides an example including
checkboxes.


Alternatives
------------

* [jqTree](http://mbraak.github.io/jqTree/)
* [Fancytree](https://github.com/mar10/fancytree)
* [aciTree](http://acoderinsights.ro/en/aciTree-tree-view-with-jQuery)
