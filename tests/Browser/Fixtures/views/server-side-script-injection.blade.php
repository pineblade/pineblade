<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>
<div @data({
    public $date;
    public $num;
    public $str;

    #[Async]
    public function __construct()
    {
        $this->date = @server(now()->toDateString());

        $getNum = server(fn () => 1234);
        $this->num = @$getNum();

        $getStr = server(function ($input) {
            return "$input-test";
        });
        $this->str = @$getStr('test');
    }
})>
  <span x-text="$date" dusk="date"></span>
  <span x-text="$num" dusk="num"></span>
  <span x-text="$str" dusk="str"></span>
</div>

@pinebladeScripts
</body>
</html>
