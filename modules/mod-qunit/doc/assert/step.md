---
layout: default
title: step
description: A marker for progress in a given test.
categories:
  - assert
---

## `step( [ message ] )`

A marker for progress in a given test.

| name               | description                          |
|--------------------|--------------------------------------|
| `message` (string) | Message to display for the step      |

### Description

The `step()` assertion registers a passing assertion with a provided message. This makes it easy to check that specific portions of code are being executed, especially in asynchronous test cases and when used with `verifySteps()`. A step will always pass unless a message is not provided or is a non-string value.

Together with the `verifySteps()` method, `step()` assertions give you an easy way to verify both the count and order of code execution.

### Example

```js
QUnit.test( "step test", function( assert ) {
  assert.expect( 1 );
  obj.hook = function() {
    assert.step('Hook is called!');
  };
  obj.invokeHookIndirectly();
});
```

_Note: See the [`verifySteps`](./verifySteps) entry for more detailed examples._
