# launchpad
Mac OSX launchpad style javascript library

## Installation
Using bower
```
bower install launchpad
```
## Usage

### Basic usage
Construct a JavaScript array to specify the data of the apps in your launchpad.
For each app, an icon, a link, and a label can be specified.
```html
<script>
    launchpad.setData([
        {
            icon:"http://images.apple.com/v/osx/c/images/better-apps/icon_photos_large_2x.png",
            link: "http://www.apple.com/osx/apps/#numbers",
            label: "Photos"
        },
        {
            icon:"http://images.apple.com/v/mac/shared/built-in-apps/e/images/numbers_icon_large_2x.png",
            link: "http://www.apple.com/osx/apps/#numbers",
            label: "Numbers"
        },
        {
            icon:"http://images.apple.com/v/osx/c/images/better-apps/icon_photos_large_2x.png",
            link: "http://www.apple.com/osx/photos/",
            label: "Photos"
        }
    ]);
</script>
```
[Demo](http://linkorb.github.io/launchpad/ "Simple launchpad")

### Make app groups
Apps can also be grouped. Simply make a group element in the data array, specifying ```group``` (the label of the group) and ```apps``` (the apps array).
```html
<script>

    launchpad.setData([
        {
            group: 'Entertainment',
            apps: [
                {
                    icon:"http://images.apple.com/v/osx/c/images/better-apps/icon_photos_large_2x.png",
                    link: "http://www.apple.com/osx/apps/#numbers",
                    label: "Photos"
                },
                {
                    icon:"http://images.apple.com/v/mac/shared/built-in-apps/e/images/numbers_icon_large_2x.png",
                    link: "http://www.apple.com/osx/apps/#numbers",
                    label: "Numbers"
                },
            ]
        },
        {
            group: 'Work',
            apps: [
                {
                    icon:"http://images.apple.com/v/mac/shared/built-in-apps/e/images/numbers_icon_large_2x.png",
                    link: "http://www.apple.com/osx/apps/#numbers",
                    label: "Numbers"
                },
                {
                    icon:"http://images.apple.com/v/mac/shared/built-in-apps/e/images/keynote_icon_large_2x.png",
                    link: "http://www.apple.com/mac/keynote/",
                    label: "Keynote"
                },
            ]
        },
        {
            icon:"http://images.apple.com/v/mac/shared/built-in-apps/e/images/numbers_icon_large_2x.png",
            link: "http://www.apple.com/osx/apps/#numbers",
            label: "Numbers"
        },
        {
            icon:"http://images.apple.com/v/mac/shared/built-in-apps/e/images/keynote_icon_large_2x.png",
            link: "http://www.apple.com/mac/keynote/",
            label: "Keynote"
        }
    ]);

</script>
```
[Demo](http://linkorb.github.io/launchpad/ "Grouped launchpad")
