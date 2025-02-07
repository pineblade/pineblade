<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
</head>
<body>
<div @code({ public $counter = 0; })>
  <button @click="$counter++" id="increment">Increment</button>
  <span x-text="$counter" id="count"></span>
</div>
@pinebladeScripts
</body>
</html>
