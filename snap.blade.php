@code({
    #[Async]
    public function getData()
    {
        /* ... */
    }

    public function otherMethod()
    {
        $data = @$this->getData(); // await this.getData();
    }
})