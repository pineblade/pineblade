<?php

use Pineblade\Pineblade\Javascript\Compiler\Compiler;
use Pineblade\Pineblade\Javascript\Compiler\Exceptions\UnsupportedSyntaxException;

function compile(string $input): string
{
    return app(Compiler::class)
        ->compileString("<?php $input;");
}

test('must compile statement', function (string $php, string $js) {
    expect(compile($php))
        ->toBe($js);
})->with('syntax-features');

test('traits are disabled', function () {
    expect(fn() => compile('new class () {use Foo;}'))
        ->toThrow(UnsupportedSyntaxException::class);
});

dataset('syntax-features', [
    'variable declaration and assignment' => [
        "\$test = 'a';",
        "let test = 'a'",
    ],
    'integers' => [
        '1',
        '1'
    ],
    'float' => [
        '1.1',
        '1.1'
    ],
    'simple array' => [
        '[1, 2, 3]',
        '[1, 2, 3]'
    ],
    'array with variable key' => [
        '[$key => true]',
        '{[key]: true}'
    ],
    'associative array (objects)' => [
        "['a' => 1, 'b' => 'c']",
        "{a: 1, b: 'c'}"
    ],
    'spread array operation' => [
        "[...\$foo, 'b' => 'c']",
        "{...foo, b: 'c'}"
    ],
    'boolean true' => [
        'true',
        'true'
    ],
    'boolean false' => [
        'false',
        'false'
    ],
    'null value' => [
        'null',
        'null',
    ],
    'empty object with anonymous class' => [
        'new class () {}',
        '{}'
    ],
    'anonymous class with props' => [
        'new class () { public $foo = "test"; public $bar; }',
        "{foo: 'test',bar: null}"
    ],
    'inject attribute' => [
        'new class { #[Inject] public $foo; }',
        '{foo: {{ \Js::from($foo) }}}'
    ],
    'property fetch' => [
        '$foo->name',
        'foo.name'
    ],
    'left-right operations' => [
        '1 === 1;1 == 1;1 != 1;1 > 1;1 >= 1;1 < 1;1 <= 1;1 || 1;1 && 1;1 + 1;1 - 1;1 * 1;1 / 1;1 ** 1;1 & 1;1 | 1;1 ^ 1;1 ?? 1;1 % 1;1 <=> 1;1 << 1;1 >> 1;1 and 1;1 or 1;1 xor 1',
        '1 === 1;1 == 1;1 != 1;1 > 1;1 >= 1;1 < 1;1 <= 1;1 || 1;1 && 1;1 + 1;1 - 1;1 * 1;1 / 1;1 ** 1;1 & 1;1 | 1;1 ^ 1;1 ?? 1;1 % 1;1 <=> 1;1 << 1;1 >> 1;1 && 1;1 || 1;1 ^ 1',
    ],
    'bitwise not' => [
        '~1',
        '~1'
    ],
    'constant fetch' => [
        'foo == 1',
        'foo == 1'
    ],
    'object casting with empty array' => [
        '(object) []',
        '[]'
    ],
    'function declaration' => [
        'function foo() {}',
        'foo() {}'
    ],
    'variadic function declaration' => [
        'function foo($a = 0, ...$b) {}',
        'foo(a = 0, ...b) {}'
    ],
    'async function declaration' => [
        '#[Async] function foo() {}',
        'async foo() {}'
    ],
    'anonymous constructor method property promotion' => [
        'new class (2) { public function __construct(public $foo = 1) {} }',
        '(() => { const obj = ({constructor(foo = 1) {this.foo = foo}}); obj.constructor(2); return obj; })()'
    ],
    'new object' => [
        'new Foo(1)',
        'new Foo(1)'
    ],
    'first-class callable declaration' => [
        '$test = function () {}',
        'let test = () => {}'
    ],
    'static first class callable declaration' => [
        '$test = static function () {}',
        'let test = () => {}'
    ],
    'arrow function' => [
        'fn() => null',
        '() => null'
    ],
    'function call' => [
        'foo(1, 2, 3)',
        'foo(1, 2, 3)'
    ],
    'method call' => [
        '$foo->foo(1, 2, 3)',
        'foo.foo(1, 2, 3)'
    ],
    'function reference' => [
        'foo(...)',
        'foo'
    ],
    'await operation' => [
        '@foo()',
        'await foo()'
    ],
    'yield operation' => [
        'fn() => yield foo()',
        '() => yield foo()'
    ],
    'encapsed string' => [
        '"$foo bar baz"',
        '`${foo} bar baz`'
    ],
    'while loop' => [
        'while(0) {}',
        'while(0) {}',
    ],
    'do while loop' => [
        'do {} while(0)',
        'do {} while(0)',
    ],
    'try-catch-finally' => [
        'try {} catch (Exception $e) {} finally {}',
        'try {} catch (e) {} finally {}',
    ],
    'array access' => [
        "\$foo['test']",
        "foo['test']"
    ],
    'return statement' => [
        'return 0',
        'return 0',
    ],
    'const declaration' => [
        'const FOO = 1',
        'const FOO = 1'
    ],
    'class creation' => [
        'class Foo { public function __construct() {} }',
        'class Foo {constructor() {}}'
    ],
    'class creation with inheritance' => [
        'class Foo extends Bar {}',
        'class Foo extends Bar {}'
    ],
    'server method' => [
        'new class { #[Server] public function i() { return \Date::now(); } }',
        "{i(...args) {return this.\$s3i('99a78173ef5f620889c35ba8a8c2f513', args)}}"
    ],
    'arrow function iife call' => [
        '(fn() => null)()',
        '(() => null)()'
    ],
    'closure iife call' => [
        '(function () {})()',
        '(() => {})()'
    ],
    'static method call' => [
        'Foo::bar(1, 2, 3)',
        'Foo.bar(1, 2, 3)'
    ],
    'if statement' => [
        'if (true || false) {$foo = 1;} elseif (true || false) {$foo = 2;} else {$foo = 3;}',
        'if (true || false) {let foo = 1} else if (true || false) {let foo = 2} else {let foo = 3}'
    ],
    'ternary statement' => [
        'true ? false : true',
        'true ? false : true'
    ],
    'for loop' => [
        'for ($i = 0; $i < 10; $i++) {$foo = 1;}',
        'for (let i = 0; i < 10; i++) {let foo = 1}'
    ],
    'post decrement' => [
        '$i--',
        'i--',
    ],
    'pre increment' => [
        '++$i',
        '++i',
    ],
    'pre decrement' => [
        '--$i',
        '--i',
    ],
    'foreach loop' => [
        'foreach ($var as $k => $v) {}',
        'for (let k in var) {let v = var[k];}'
    ],
    'match expression' => [
        'match ($var) {1,2,3 => true, default => false}',
        '((__val)=>{switch(__val){case 1:case 2:case 3:return true;default: return false;}})(var)'
    ],
    'method getter' => [
        'new class { #[Get] public function foo() {}}',
        '{get foo() {}}'
    ],
    'method setter' => [
        'new class { #[Set] public function foo() {}}',
        '{set foo() {}}'
    ]
]);
