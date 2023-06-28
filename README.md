<p align="center"><img src="docs/img/header-logo.svg" alt=""></p>

# Pineblade
######  (Don't use this in production)

## PHP frontend for Laravel, powered by Alpine.js.

Writing reactive front-end with 100% PHP+Blade? Yes, take a look:

```html
<div @data({ public $counter = 0; })>
    <button @click="$counter++">Increment</button>
    <br>
    <br>
    Value: <span x-text="$counter"></span>
</div>
```
The code above produces:
<br>
<br>
![counter-example.gif](docs%2Fimg%2Fcounter-example.gif)

## Installation
- Add this repo as a git repo in your composer.json file
```json
"repositories": [
    {
        "url": "https://github.com/pineblade/pineblade.git",
        "type": "git"
    }
]
```
- Run `composer require pineblade/pineblade` to install the package.
- Run `php artisan vendor:publish --tag=pineblade-scripts` to publish the scripts.
- Add the `@pinebladeScripts` at the end of the body tag in your html file.
## How it works?
The PHP code is transpiled to javascript. The reactivity is achieved with the lightweight Alpine.js.

## Wait, did you say the PHP code is compiled into javascript code?
Yes. I used the nikic/php-parser to parse the code inside the block, and then i wrote a simple translator that outputs javascript from whatever php code is given.

## Special Variable variables syntax
You probably know the PHP Variable variables syntax, right?
Here we use this syntax to resolve expressions server-side. This can be useful to inject variable values, or any expression results into the client-side code.
```html
@props([
    'users' => collect([
        ['id' => 1, 'name' => 'Mario'],
        ['id' => 2, 'name' => 'Luigi'],
    ])
])

<div
  @data({
      #[Inject]
      public $users; // The "Inject" attribute for injecting server props into the code block.
  
      public $currentDate;
      
      public function __construct()
      {
          // Also, there is the ${} expression block that can be any php expression. It will be evaluated server-side.
          $this->currentDate = ${now()->toDateTimeString()};
      }
  })
>
    @text($currentDate)
</div>
```

## Important!
Keep in mind that the client-side php code is just a translation. We can't use array_* functions or whatever any other php classes or functions we have in the server-side. Instead, you must use the client-side Objects/functions/methods/etc...

To map through an array, you should use the javascript ->map() method:
```php
// valid client-side code:
$array = [1, 2, 3];

$result = $array->map(fn ($val) => $val * 2);
```
**REMEMBER, IT IS THE JAVASCRIPT CONTEXT.**

## Custom blade directives
- @text()
  - It's a shorthand for the alpine `x-text` directive. It will just place a `<span>` tag with the `x-text`.
- @xforeach() / @xendforeach
  - Shorthand for `x-for` directive. 
- @xif() / @xendif
  - Shorthand for `x-if` directive.


## Alpine tag attributes
All alpine tags will be compiled to javascript. The contents of any `x-*` or `@*`, needs to be valid php code. It is possible to disable this behaviour in the `pineblade` config file:
