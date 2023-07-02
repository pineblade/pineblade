import Alpine from 'alpinejs'

window.Alpine = Alpine

window.addEventListener('alpine:init', () => {
  const s3iUrl = document.head.querySelector(`meta[name="pineblade-s3i-url"]`).getAttribute('content')
  const csrfToken = document.head.querySelector(`meta[name="pineblade-csrf-token"]`).getAttribute('content')
  Alpine.magic('s3i', () => async (action, params = []) => {
    const response = await fetch(s3iUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'Application/json',
        'Accept': 'Application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ action, params })
    })
    const json = await response.json()
    return json.payload
  })
})

Alpine.start()
