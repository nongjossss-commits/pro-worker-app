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
        async process(file, colorType, onProgress, cancellationToken, skipAi = false) {
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

            // If we are skipping AI (e.g. manually edited image), treat the file as the transparent source
            if (skipAi && !transparentBlob) {
                transparentBlob = file;
                this.cache.transparentBlob = file;
            }

            if (!transparentBlob) {
                try {
                    if (onProgress) onProgress(true, 'Initializing AI Model...');

                    // Check if library is loaded (Wait up to 10 seconds)
                    const removeBackgroundFn = await this.waitForLibrary(cancellationToken);

                    if (cancellationToken && cancellationToken.cancelled) {
                        throw new Error('Cancelled by user');
                    }

                    // --- OPTIMIZATION: Resize large images ---
                    // Downscaling speeds up processing significantly (e.g.
                    // much faster for 12MP+ phone photos) while still being
                    // far more than an ID card needs. Raised from 1200 to
                    // 1600px — 1200 was cutting away fine hair/edge detail
                    // the AI model could otherwise have used, making cutouts
                    // look rougher than necessary; 1600 keeps meaningfully
                    // more of that detail for a modest cost, without
                    // approaching the multi-second-per-megapixel territory
                    // that would hurt users already on weaker/GPU-less
                    // machines (see the GPU/CPU fallback right below).
                    if (onProgress) onProgress(true, 'Optimizing image size...');
                    const resizedFile = await this.resizeImage(file, 1600);

                    if (onProgress) onProgress(true, 'Removing background (this may take a moment)...');

                    // Base Configuration
                    const baseConfig = {
                        debug: true,
                        // device: 'gpu', // Set dynamically in fallback logic
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

                    // Function to execute with GPU -> CPU fallback
                    const runRemovalWithFallback = async () => {
                        try {
                            console.log('Attempting Background Removal with GPU...');
                            // Try GPU first
                            return await removeBackgroundFn(resizedFile, { ...baseConfig, device: 'gpu' });
                        } catch (gpuError) {
                            console.warn('GPU removal failed. Retrying with CPU...', gpuError);

                            if (onProgress) onProgress(true, 'GPU unavailable. Switching to CPU mode (slower)...');

                            // Check cancellation before retry
                            if (cancellationToken && cancellationToken.cancelled) {
                                throw new Error('Cancelled by user');
                            }

                            // Fallback to CPU
                            try {
                                return await removeBackgroundFn(resizedFile, { ...baseConfig, device: 'cpu' });
                            } catch (cpuError) {
                                console.error('CPU removal also failed:', cpuError);
                                throw new Error('Background removal failed on both GPU and CPU. Please try a different image.');
                            }
                        }
                    };

                    // Execute removal with timeout and cancellation race
                    const processPromise = runRemovalWithFallback();

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
                // "Remove BG" used to return the AI model's raw mask
                // untouched — the edge refinement below (which fixes hair
                // wisps / fine detail, see _refineMask) only ever ran for
                // the colored-background options. Run it here too so
                // Remove BG gets the same clean edges as White/Light Blue BG.
                if (onProgress) onProgress(true, 'Refining edges...');
                try {
                    if (cancellationToken && cancellationToken.cancelled) throw new Error('Cancelled by user');
                    return await this.refineTransparent(transparentBlob);
                } finally {
                    if (onProgress) onProgress(false);
                }
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

        // Helper to draw blob on colored canvas.
        //
        // The old version simply painted the color and drew the transparent
        // image on top. But @imgly returns a soft alpha mask (0-255) so
        // pixels at hair/collar edges have partial alpha — those get blended
        // with the new background color, which visibly washes shirt/skin
        // colors when the background is light blue.
        //
        // The improved version:
        //   1. Alpha threshold: pixels with alpha ≥ opaqueCutoff become fully
        //      opaque so foreground RGB is preserved 100%.
        //   2. Erosion (1px): shrink the mask inward by one pixel so the
        //      leaked background color from the ORIGINAL photo (present in
        //      partial-alpha ring) is discarded instead of blended.
        //   3. Soft feather in the transition band so the cut looks natural.
        //
        // opts: { opaqueCutoff:200, transparentCutoff:50, erode:1, feather:true }
        compositeBackground(imageBlob, colorHex, opts = {}) {
            const {
                opaqueCutoff = 200,
                transparentCutoff = 50,
                erode = 1,
                feather = true,
            } = opts;

            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    const w = img.width, h = img.height;

                    // 1) Draw the transparent foreground onto a working canvas
                    const fgCanvas = document.createElement('canvas');
                    fgCanvas.width = w; fgCanvas.height = h;
                    const fgCtx = fgCanvas.getContext('2d');
                    fgCtx.drawImage(img, 0, 0);
                    const fgData = fgCtx.getImageData(0, 0, w, h);

                    // 2) Refine mask (threshold + erosion)
                    const refined = this._refineMask(fgData, {
                        opaqueCutoff, transparentCutoff, erode, feather,
                    });
                    fgCtx.putImageData(refined, 0, 0);

                    // 3) Composite: paint bg color, then draw refined fg over it
                    const outCanvas = document.createElement('canvas');
                    outCanvas.width = w; outCanvas.height = h;
                    const outCtx = outCanvas.getContext('2d');
                    outCtx.fillStyle = colorHex;
                    outCtx.fillRect(0, 0, w, h);
                    outCtx.drawImage(fgCanvas, 0, 0);

                    outCanvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.95);
                };
                img.onerror = reject;
                img.src = URL.createObjectURL(imageBlob);
            });
        },

        // Same edge refinement as compositeBackground(), but outputs a
        // transparent PNG instead of compositing over a color — used by
        // the "Remove BG" option so it gets the same clean edges as
        // White/Light Blue BG instead of the AI model's raw, unrefined mask.
        refineTransparent(imageBlob, opts = {}) {
            const {
                opaqueCutoff = 200,
                transparentCutoff = 50,
                erode = 1,
                feather = true,
            } = opts;

            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    const w = img.width, h = img.height;
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    const data = ctx.getImageData(0, 0, w, h);
                    const refined = this._refineMask(data, { opaqueCutoff, transparentCutoff, erode, feather });
                    ctx.putImageData(refined, 0, 0);
                    canvas.toBlob((blob) => resolve(blob), 'image/png', 0.95);
                };
                img.onerror = reject;
                img.src = URL.createObjectURL(imageBlob);
            });
        },

        // Threshold + morphological erosion + optional edge feathering on the
        // alpha channel only. RGB is left untouched so foreground colors stay
        // vivid.
        _refineMask(imageData, { opaqueCutoff, transparentCutoff, erode, feather }) {
            const w = imageData.width, h = imageData.height;
            const src = imageData.data;

            // Build a binary "solid interior" map (0 = not solid, 1 = solid)
            // — only pixels at or above opaqueCutoff are candidates. This
            // feeds the erosion step below, whose job is to strip the
            // background-color-bled ring immediately around genuinely
            // solid foreground (see erosion comment further down).
            const inside = new Uint8Array(w * h);
            for (let i = 0, p = 0; i < src.length; i += 4, p++) {
                inside[p] = src[i + 3] >= opaqueCutoff ? 1 : 0;
            }

            // Erosion: a pixel stays "inside" only if all its 4-neighbours
            // are also inside. Iterate `erode` times.
            let eroded = inside;
            for (let iter = 0; iter < erode; iter++) {
                const next = new Uint8Array(w * h);
                for (let y = 0; y < h; y++) {
                    for (let x = 0; x < w; x++) {
                        const p = y * w + x;
                        if (!eroded[p]) { next[p] = 0; continue; }
                        const up = y > 0 ? eroded[p - w] : 1;
                        const dn = y < h - 1 ? eroded[p + w] : 1;
                        const lt = x > 0 ? eroded[p - 1] : 1;
                        const rt = x < w - 1 ? eroded[p + 1] : 1;
                        next[p] = (up && dn && lt && rt) ? 1 : 0;
                    }
                }
                eroded = next;
            }

            // Apply back to alpha channel:
            //   core solid, surrounded by core solid  -> 255 (crisp)
            //   core solid, adjacent to non-solid      -> 180 (soft edge)
            //   was solid but stripped by erosion       -> 0 (the
            //     background-color-bled ring the erosion pass exists to
            //     discard — unchanged from before)
            //   NEW — genuinely soft pixel per the AI's own mask (hair
            //   wisps, glasses rims, motion-blurred fringes: alpha below
            //   opaqueCutoff, so never a "solid" candidate at all) ->
            //   alpha scaled linearly between transparentCutoff and
            //   opaqueCutoff, preserving the gradient. BUG FIX: this whole
            //   band used to be force-zeroed too (transparentCutoff was
            //   declared but never actually used), which discarded the
            //   AI's soft-edge estimate entirely and produced hard,
            //   jagged cutouts around hair/fine detail.
            const range = Math.max(1, opaqueCutoff - transparentCutoff);
            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const p = y * w + x;
                    const i = p * 4;

                    if (eroded[p]) {
                        if (!feather) { src[i + 3] = 255; continue; }
                        const up = y > 0 ? eroded[p - w] : 1;
                        const dn = y < h - 1 ? eroded[p + w] : 1;
                        const lt = x > 0 ? eroded[p - 1] : 1;
                        const rt = x < w - 1 ? eroded[p + 1] : 1;
                        src[i + 3] = (up && dn && lt && rt) ? 255 : 180;
                        continue;
                    }

                    if (inside[p]) {
                        // Solid per the threshold, but stripped by erosion.
                        src[i + 3] = 0;
                        continue;
                    }

                    const origAlpha = src[i + 3];
                    if (origAlpha <= transparentCutoff) {
                        src[i + 3] = 0;
                    } else {
                        const t = (origAlpha - transparentCutoff) / range;
                        src[i + 3] = Math.round(t * 255);
                    }
                }
            }
            return imageData;
        },
    };
</script>
