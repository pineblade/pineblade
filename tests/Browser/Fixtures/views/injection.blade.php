@props(['name' => 'test'])
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
</head>
<body>
<div
    @data({
        public $date;
        public $jsonEncode;
        public $arr;
        #[Inject]
        public $name;

        public function __construct()
        {
            $this->date = ${now()->toDateString()};
            $this->jsonEncode = ${json_encode(['name' => 'json'])};
            $this->arr = ${['arr' => 'arr']};
        }
    })
>
  <span x-text="$date" dusk="date"></span>
  <span x-text="$jsonEncode" dusk="json"></span>
  <span x-text="$arr->arr" dusk="array"></span>
  <span x-text="$name" dusk="name"></span>
</div>

@pinebladeScripts
</body>
</html>
