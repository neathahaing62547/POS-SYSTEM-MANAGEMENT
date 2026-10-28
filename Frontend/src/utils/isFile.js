export const isFile = (value) => {
    return typeof File !== 'undefined' && value instanceof File
}
