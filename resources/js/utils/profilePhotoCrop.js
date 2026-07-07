/**
 * Export a square crop from Cropper.js as a circular JPEG blob.
 *
 * @param {import('cropperjs').default} cropper
 * @param {number} size
 * @returns {Promise<Blob>}
 */
export function exportCircularCrop(cropper, size = 512) {
    const squareCanvas = cropper.getCroppedCanvas({
        width: size,
        height: size,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    const roundCanvas = document.createElement('canvas');
    roundCanvas.width = size;
    roundCanvas.height = size;

    const context = roundCanvas.getContext('2d');

    if (!context) {
        throw new Error('Não foi possível preparar a imagem.');
    }

    context.beginPath();
    context.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
    context.closePath();
    context.clip();
    context.drawImage(squareCanvas, 0, 0, size, size);

    return new Promise((resolve, reject) => {
        roundCanvas.toBlob(
            (blob) => {
                if (!blob) {
                    reject(new Error('Não foi possível salvar a imagem recortada.'));

                    return;
                }

                resolve(blob);
            },
            'image/jpeg',
            0.92,
        );
    });
}

/**
 * @param {Blob} blob
 * @param {string} filename
 * @returns {File}
 */
export function blobToJpegFile(blob, filename = 'profile-photo.jpg') {
    return new File([blob], filename, {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });
}
