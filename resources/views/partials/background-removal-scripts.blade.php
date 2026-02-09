<!-- @imgly/background-removal CDN via ESM (Modern) -->
<script type="module">
    import { removeBackground } from 'https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.7.0/+esm';
    window.imglyRemoveBackground = removeBackground;
    console.log('Background Removal Library (ESM) loaded.');
</script>

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
                    if (onProgress) onProgress(true, 'Initializing AI Model...');

                    // Check if library is loaded (Wait up to 10 seconds)
                    const removeBackgroundFn = await this.waitForLibrary();

                    if (onProgress) onProgress(true, 'Removing background (this may take a moment)...');

                    // Configuration for better quality and progress tracking
                    const config = {
                        progress: (key, current, total) => {
                            if (onProgress) {
                                const percent = Math.round((current / total) * 100);
                                onProgress(true, `Processing: ${percent}%`);
                            }
                        },
                        model: 'medium', // Use medium model for balance of quality/speed
                        output: {
                            format: 'image/png',
                            quality: 0.8
                        }
                    };

                    // Execute removal
                    transparentBlob = await removeBackgroundFn(file, config);

                    this.cache.transparentBlob = transparentBlob;
                } catch (error) {
                    console.error('Background removal failed:', error);
                    // Provide user-friendly error
                    if (error.message && error.message.includes('fetch')) {
                        throw new Error('Failed to download AI model. Please check your internet connection.');
                    }
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

        // Helper to wait for the ESM module to load
        waitForLibrary() {
            return new Promise((resolve, reject) => {
                if (window.imglyRemoveBackground) {
                    resolve(window.imglyRemoveBackground);
                    return;
                }

                let retries = 0;
                const interval = setInterval(() => {
                    if (window.imglyRemoveBackground) {
                        clearInterval(interval);
                        resolve(window.imglyRemoveBackground);
                    }
                    retries++;
                    if (retries > 50) { // 5 seconds
                        clearInterval(interval);
                        reject(new Error('Background Removal Library failed to load. Please refresh the page.'));
                    }
                }, 100);
            });
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

                    // Export as JPEG (since no transparency needed)
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
