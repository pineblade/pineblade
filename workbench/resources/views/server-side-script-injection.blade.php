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
<div @code({
    public $num;
    public $str;

    #[Async]
    public function __construct()
    {
        $this->num = @$this->getNum();

        $this->str = @$this->getStr('test');
    }

    #[Server]
    public function getNum(): Promise
    {
        return 1234;
    }

    #[Server]
    public function getStr($input): Promise
    {
        return "$input-test";
    }
})>
  <span x-text="$num" dusk="num"></span>
  <span x-text="$str" dusk="str"></span>
</div>

@pinebladeScripts
</body>
</html>
