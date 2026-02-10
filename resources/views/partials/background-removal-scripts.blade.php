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
        async process(file, colorType, onProgress, cancellationToken) {
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
                    const removeBackgroundFn = await this.waitForLibrary(cancellationToken);

                    if (cancellationToken && cancellationToken.cancelled) {
                        throw new Error('Cancelled by user');
                    }

                    // --- OPTIMIZATION: Resize large images ---
                    // Downscaling to 1200px max dimension significantly speeds up processing (e.g. 10x faster for 12MP photos)
                    // while retaining enough quality for ID cards.
                    if (onProgress) onProgress(true, 'Optimizing image size...');
                    const resizedFile = await this.resizeImage(file, 1200);

                    if (onProgress) onProgress(true, 'Removing background (this may take a moment)...');

                    // Configuration for better quality and progress tracking
                    const config = {
                        debug: true,
                        device: 'gpu', // Hint to use GPU if available
                        progress: (key, current, total) => {
                            if (onProgress) {
                                const percent = Math.round((current / total) * 100);
                                onProgress(true, `Processing: ${percent}%`);
                            }
                        },
                        // model: 'medium', // Default (isnet_fp16) is accurate. We rely on resizing for speed.
                        output: {
                            format: 'image/png',
                            quality: 0.95
                        }
                    };

                    // Execute removal with timeout and cancellation race
                    // Use resizedFile instead of original file
                    const processPromise = removeBackgroundFn(resizedFile, config);

                    const timeoutPromise = new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Processing timed out (60s). Please check your connection.')), 60000)
                    );

                    const cancellationPromise = new Promise((_, reject) => {
                        if (cancellationToken) {
                            cancellationToken.onCancel = () => reject(new Error('Cancelled by user'));
                            if (cancellationToken.cancelled) reject(new Error('Cancelled by user'));
                        }
                    });

                    transparentBlob = await Promise.race([processPromise, timeoutPromise, cancellationPromise]);

                    this.cache.transparentBlob = transparentBlob;
                } catch (error) {
                    console.error('Background removal failed:', error);
                    // Provide user-friendly error
                    if (error.message === 'Cancelled by user') {
                        throw error; // Propagate cancellation
                    }
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
                // Check cancellation before compositing
                if (cancellationToken && cancellationToken.cancelled) throw new Error('Cancelled by user');

                const color = this.colors[colorType] || '#FFFFFF';
                return await this.compositeBackground(transparentBlob, color);
            } finally {
                if (onProgress) onProgress(false);
            }
        },

        // Helper to resize image
        resizeImage(file, maxDimension) {
            return new Promise((resolve, reject) => {
                if (!file.type.match(/image.*/)) {
                    resolve(file); // Not an image, return original
                    return;
                }

                const reader = new FileReader();
                reader.onload = (readerEvent) => {
                    const image = new Image();
                    image.onload = () => {
                        let width = image.width;
                        let height = image.height;

                        if (width <= maxDimension && height <= maxDimension) {
                            resolve(file); // No need to resize
                            return;
                        }

                        if (width > height) {
                            if (width > maxDimension) {
                                height *= maxDimension / width;
                                width = maxDimension;
                            }
                        } else {
                            if (height > maxDimension) {
                                width *= maxDimension / height;
                                height = maxDimension;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(image, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            if (!blob) {
                                resolve(file); // Fallback
                                return;
                            }
                            resolve(new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now(),
                            }));
                        }, file.type, 0.95);
                    };
                    image.onerror = () => resolve(file); // Fallback
                    image.src = readerEvent.target.result;
                };
                reader.onerror = () => resolve(file); // Fallback
                reader.readAsDataURL(file);
            });
        },

        // Helper to wait for the ESM module to load
        waitForLibrary(cancellationToken) {
            return new Promise((resolve, reject) => {
                if (window.imglyRemoveBackground) {
                    resolve(window.imglyRemoveBackground);
                    return;
                }

                let retries = 0;
                const interval = setInterval(() => {
                    if (cancellationToken && cancellationToken.cancelled) {
                        clearInterval(interval);
                        reject(new Error('Cancelled by user'));
                        return;
                    }
                    if (window.imglyRemoveBackground) {
                        clearInterval(interval);
                        resolve(window.imglyRemoveBackground);
                    }
                    retries++;
                    if (retries > 100) { // 10 seconds
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
