<button @click="increment(...)" dusk="increment">
    Increment
</button>
<span x-text="$counter" dusk="count"></span>

@code({
    public $counter = 0;

    function increment()
    {
        $this->counter++;
    }
})
