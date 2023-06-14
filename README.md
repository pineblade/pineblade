# Pineblade (Don't use this in production)

## True frontend for Laravel.

Writing reactive html code with 100% PHP+Blade? Yes, take a look:

```html
<!-- [resources/views/components/pineblade/counter.blade.php] -->

<div>
    <button @click="increment(...)">Increment</button>
    <br>
    <br>
    Value: @text($this->counter)
</div>

@code({
    public $counter = 0;
    
    public function increment()
    {
        $this->counter++;
    }
})
```
The code above produces this:
<br>
<br>
![counter-example.gif](img%2Fcounter-example.gif)

## Installation
Add this repo as a git repo in your composer.json file
```json
"repositories": [
    {
        "url": "https://github.com/ErickJMenezes/pineblade.git",
        "type": "git"
    }
]
```
Run `composer require erickjmenezes/pineblade` in the server-side.

Install `alpinejs` in the client-side. You can do this via CDN script tag, or `npm`.

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

<div>
    @text($this->now)
</div>


@code({
    public $users;
    public $currentDate;
  
    public function __construct()
    {
        // the expression inside the ${} block can be any server-side php expression.
        $this->users = ${$users};
        $this->currentDate = ${now()->toDateTimeString()};
    }
}) 
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
*The contents of any `x-*` needs to be valid php code. The compiler will handle this for you. It is possible to disable this behaviour and use plain javascript by doing this in you `AppServiceProvider` boot method:*
```php
\Pineblade\Pineblade\Facades\Pineblade::compileAlpineAttributes(false);
```
