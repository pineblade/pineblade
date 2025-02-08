<button @click="increment(...)" dusk="increment">Increment</button>
<span x-text="$counter" dusk="count"></span>

@code({
    public $counter = 0;

    public function increment()
    {
        $this->counter++;
        if ($this->counter === 1) {
            $this->counter = 10;
        }
    }
})
