<p align="center"><img src="docs/img/header-logo.svg" alt=""></p>

# Pineblade

###### (Don't use this in production, this is just a personal project.)

## PHP frontend for Laravel.

Writing reactive front-end with 100% PHP+Blade? Yes, take a look:

![simple-counter.png](docs%2Fimg%2Freadme-snaps%2Fsimple-counter.png)

The code above produces:
<br>
<br>
![counter-example.gif](docs%2Fimg%2Fcounter-example.gif)

## Installation

- Run to install the package with:

```sh
composer require pineblade/pineblade
```

- Publish the scripts:

```sh
php artisan vendor:publish --tag=pineblade-scripts
```

- Add the `@pinebladeScripts` at the end of the body tag in your html file.

## How it works?

The PHP code is converted to javascript. The reactivity is achieved with the lightweight Alpine.js.
Under the hood, it is just Alpine.js.

## Wait, did you say the PHP code is converted to javascript?

Yes. I used the nikic/php-parser to parse the code inside the block, and then I wrote a simple translator that outputs javascript from whatever php code is given. Check the source code, for more details.

## The `#[Async]` attribute, and the `@` operator:

In PHP, this operator is used to suppress errors. Here, it means `await`. It is used to wait asynchronous function calls.

![async-await.png](docs%2Fimg%2Freadme-snaps%2Fasync-await.png)

## The `#[Inject]` attribute:

Used to inject blade variables with the same name as @code property values.

![inject-1.png](docs%2Fimg%2Freadme-snaps%2Finject-1.png)

## The `server()` function:

This is a special function, that only exists at compile time.
With this function, you can create a closure that will be executed at server side and return the contents to the client side. Take a look:
![vv-2.png](docs%2Fimg%2Freadme-snaps%2Fvv-2.png)
Also there is a `#[Server]` attribute that you can use to annotate the methods declared inside `@data`. 

## Important!

Keep in mind that the client-side php code is just a direct translation. We can't use array\_\* functions or whatever any other php classes or functions we have in the server-side. Instead, you must use the client-side Objects/functions/methods/etc...

To map through an array, you should use the javascript ->map() method:

```php
// valid client-side code:
$array = [1, 2, 3];

$result = $array->map(fn ($val) => $val * 2);
```

**REMEMBER, IT IS THE JAVASCRIPT CONTEXT.**
You are writing javascript code, but with the PHP syntax.

## Custom blade directives

- @text()
  - It's a shorthand for the alpine `x-text` directive. It will just place a `<span>` tag with the `x-text`.
- @xforeach() / @xendforeach
  - Shorthand for `x-for` directive.
- @xif() / @xendif
  - Shorthand for `x-if` directive.

## Alpine directives

All Alpine directives needs to be written in php. The contents of any `x-*` or `@*`, will be transpiled to php. Example:

```html
<button @click="increment(...)">Increment</button>
```

In the `@click` attribute, we used the php first-class callable syntax.
