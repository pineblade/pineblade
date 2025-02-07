@props(['name' => 'test'])
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
</head>
<body>
<div
    @code({
        #[Inject]
        public $name;
    })
>
  <span x-text="$name" dusk="name"></span>
</div>

@pinebladeScripts
</body>
</html>
