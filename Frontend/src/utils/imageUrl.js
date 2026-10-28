import api from '@/services/api'

const storageBaseUrl = () => {
    const apiUrl = new URL(api.defaults.baseURL, window.location.origin)
    const apiPath = apiUrl.pathname.replace(/\/api\/?$/, '')

    return `${apiUrl.origin}${apiPath}/storage`
}

export const resolveImageUrl = (image) => {
    if (!image || typeof image !== 'string') return ''
    if (/^(https?:|data:|blob:)/i.test(image)) return image
    if (image.startsWith('/storage/')) return new URL(image, storageBaseUrl()).href

    const path = image.replace(/^\/+/, '').split('/').map(encodeURIComponent).join('/')
    return `${storageBaseUrl()}/${path}`
}
