<div>
    <button @click="increment(...)" dusk="increment">
        Increment
    </button>
    <span x-text="$counter" dusk="count"></span>
</div>

@code({
    public $counter = 0;

    function increment()
    {
        $this->counter++;
    }
})
