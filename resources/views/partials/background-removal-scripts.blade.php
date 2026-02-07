<!-- @imgly/background-removal CDN -->
<script src="https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.3.0/dist/imgly-background-removal.min.js"></script>

<script>
    window.backgroundRemoval = {
        cache: {
            originalFile: null,
            transparentBlob: null
        },

        // Colors
        colors: {
            'white': '#FFFFFF',
            'blue': '#65a5ff' // Standard ID card blue-ish
        },

        // Main function to process image
        async process(file, colorType, onProgress) {
            // 1. Reset cache if file changed
            if (this.cache.originalFile !== file) {
                this.cache.originalFile = file;
                this.cache.transparentBlob = null;
            }

            // 2. Handle 'original'
            if (colorType === 'original') {
                return file;
            }

            // 3. Get Transparent Blob (Cached or New)
            let transparentBlob = this.cache.transparentBlob;

            if (!transparentBlob) {
                try {
                    if (onProgress) onProgress(true, 'Removing background...');

                    // imgly.removeBackground returns a Promise<Blob>
                    // We need to configure it to load assets from CDN correctly if needed,
                    // but usually default works if simple.
                    // However, for better reliability, we can specify publicPath if issues arise.
                    // For now, let's try default.
                    transparentBlob = await imgly.removeBackground(file, {
                        progress: (key, current, total) => {
                             // console.log(`Downloading ${key}: ${current} of ${total}`);
                        }
                    });

                    this.cache.transparentBlob = transparentBlob;
                } catch (error) {
                    console.error('Background removal failed:', error);
                    throw error;
                } finally {
                    if (onProgress) onProgress(false);
                }
            }

            // 4. Return based on color type
            if (colorType === 'transparent') {
                return transparentBlob;
            }

            // 5. Composite for colors
            if (onProgress) onProgress(true, 'Applying background color...');
            try {
                const color = this.colors[colorType] || '#FFFFFF';
                return await this.compositeBackground(transparentBlob, color);
            } finally {
                if (onProgress) onProgress(false);
            }
        },

        // Helper to draw blob on colored canvas
        compositeBackground(imageBlob, colorHex) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');

                    // Fill background
                    ctx.fillStyle = colorHex;
                    ctx.fillRect(0, 0, canvas.width, canvas.height);

                    // Draw image
                    ctx.drawImage(img, 0, 0);

                    // Export
                    canvas.toBlob((blob) => {
                        resolve(blob);
                    }, 'image/jpeg', 0.95);
                };
                img.onerror = reject;
                img.src = URL.createObjectURL(imageBlob);
            });
        }
    };
</script>
