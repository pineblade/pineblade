<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
</head>
<body>
<div
    @data({
        public $counter = 0;

        public function increment()
        {
            $this->counter++;
            if ($this->counter === 1) {
                $this->counter = 10;
            }
        }
    })
>
  <button @click="increment(...)" dusk="increment">Increment</button>
  <span x-text="$counter" dusk="count"></span>
</div>
@pinebladeScripts
</body>
</html>
