{{--
    photo-editor-tools.blade.php

    Client-side image-adjustment helpers for the employee photo editor.
    All logic is pure Canvas / ImageData — no AI, no external packages.

    Consumed by _edit_scripts.blade.php which wires the buttons/sliders
    exposed by cropper-modal.blade.php to these helpers.
--}}
<script>
    window.photoEditorTools = (function () {

        // ---------- Canvas helpers ----------

        function fileToImage(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = URL.createObjectURL(file);
            });
        }

        function imageToFile(canvas, mime = 'image/jpeg', quality = 0.95, name = 'edited.jpg') {
            return new Promise((resolve) => {
                canvas.toBlob((blob) => {
                    if (!blob) { resolve(null); return; }
                    resolve(new File([blob], name, { type: mime, lastModified: Date.now() }));
                }, mime, quality);
            });
        }

        function canvasFromImage(img) {
            const c = document.createElement('canvas');
            c.width = img.width; c.height = img.height;
            c.getContext('2d').drawImage(img, 0, 0);
            return c;
        }

        // ---------- Beauty adjustments ----------

        // Apply a set of adjustments to a File, return a new File.
        //   brightness: -100..+100    (multiplicative, 0 = no change)
        //   contrast:   -100..+100
        //   saturation: -100..+100
        //   warmth:     -100..+100    (positive = warmer/yellow, negative = cooler/blue)
        //   sharpness:  0..100        (0 = off, 100 = strong)
        //   skinSmooth: 0..100        (0 = off, 100 = strong)
        async function applyAdjustments(file, adjust, mime = 'image/jpeg') {
            const img = await fileToImage(file);
            const canvas = canvasFromImage(img);
            const ctx = canvas.getContext('2d');
            const data = ctx.getImageData(0, 0, canvas.width, canvas.height);

            _adjustPixels(data, adjust);
            ctx.putImageData(data, 0, 0);

            if (adjust.sharpness && adjust.sharpness > 0) {
                _sharpenCanvas(canvas, adjust.sharpness / 100);
            }
            if (adjust.skinSmooth && adjust.skinSmooth > 0) {
                _smoothCanvas(canvas, adjust.skinSmooth / 100);
            }

            return await imageToFile(canvas, mime, 0.95, file.name || 'edited.jpg');
        }

        function _adjustPixels(imageData, adjust) {
            const d = imageData.data;
            const brightness = 1 + (adjust.brightness || 0) / 100;   // 0..2
            const contrast = 1 + (adjust.contrast || 0) / 100;       // 0..2
            const satFactor = 1 + (adjust.saturation || 0) / 100;    // 0..2
            const warmth = (adjust.warmth || 0);                     // -100..100

            // Precompute contrast offset so it pivots around 128.
            const contrastOffset = 128 * (1 - contrast);

            // Warmth: nudge R up + B down when positive; opposite when negative
            const warmR = warmth * 0.4;
            const warmB = -warmth * 0.4;

            for (let i = 0; i < d.length; i += 4) {
                let r = d[i], g = d[i + 1], b = d[i + 2];

                // 1) Brightness (multiplicative)
                r *= brightness; g *= brightness; b *= brightness;

                // 2) Contrast (pivot around 128)
                r = r * contrast + contrastOffset;
                g = g * contrast + contrastOffset;
                b = b * contrast + contrastOffset;

                // 3) Warmth (linear shift on R/B)
                r += warmR;
                b += warmB;

                // 4) Saturation (rec.601 luma-preserving)
                if (satFactor !== 1) {
                    const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                    r = lum + (r - lum) * satFactor;
                    g = lum + (g - lum) * satFactor;
                    b = lum + (b - lum) * satFactor;
                }

                d[i]     = _clamp(r);
                d[i + 1] = _clamp(g);
                d[i + 2] = _clamp(b);
            }
        }

        function _clamp(v) { return v < 0 ? 0 : v > 255 ? 255 : v; }

        // 3x3 unsharp mask. Strength 0..1.
        function _sharpenCanvas(canvas, strength) {
            const ctx = canvas.getContext('2d');
            const w = canvas.width, h = canvas.height;
            const src = ctx.getImageData(0, 0, w, h);
            const dst = ctx.createImageData(w, h);
            const s = src.data, o = dst.data;

            // Unsharp mask kernel scaled by strength
            //   [  0  -k   0 ]
            //   [ -k  1+4k -k ]
            //   [  0  -k   0 ]
            const k = strength;

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const p = (y * w + x) * 4;
                    for (let c = 0; c < 3; c++) {
                        const cur = s[p + c];
                        const up  = y > 0     ? s[p - w * 4 + c] : cur;
                        const dn  = y < h - 1 ? s[p + w * 4 + c] : cur;
                        const lt  = x > 0     ? s[p - 4 + c]     : cur;
                        const rt  = x < w - 1 ? s[p + 4 + c]     : cur;
                        const v = cur * (1 + 4 * k) - k * (up + dn + lt + rt);
                        o[p + c] = _clamp(v);
                    }
                    o[p + 3] = s[p + 3];
                }
            }
            ctx.putImageData(dst, 0, 0);
        }

        // Simple bilateral-like blur for skin smoothing. Strength 0..1.
        // Uses CSS filter blur() + partial overlay to preserve edges cheaply.
        function _smoothCanvas(canvas, strength) {
            const ctx = canvas.getContext('2d');
            const w = canvas.width, h = canvas.height;
            const originalData = ctx.getImageData(0, 0, w, h);

            const blurred = document.createElement('canvas');
            blurred.width = w; blurred.height = h;
            const bctx = blurred.getContext('2d');
            bctx.filter = `blur(${1 + strength * 3}px)`;
            bctx.drawImage(canvas, 0, 0);

            // Overlay blurred with strength * 60% opacity, then restore edges from original
            ctx.save();
            ctx.globalAlpha = 0.6 * strength;
            ctx.drawImage(blurred, 0, 0);
            ctx.restore();

            // Edge preservation: sharpen back the high-frequency detail
            const finalData = ctx.getImageData(0, 0, w, h);
            const df = finalData.data;
            const od = originalData.data;
            // Blend original edges back based on local variance (cheap proxy)
            for (let i = 0; i < df.length; i += 4) {
                // If the original pixel differs a lot from the average, treat it as an edge
                // and pull back toward the original.
                const diff = Math.abs(od[i] - df[i]) + Math.abs(od[i + 1] - df[i + 1]) + Math.abs(od[i + 2] - df[i + 2]);
                if (diff > 40) {
                    df[i]     = od[i];
                    df[i + 1] = od[i + 1];
                    df[i + 2] = od[i + 2];
                }
            }
            ctx.putImageData(finalData, 0, 0);
        }

        // ---------- Auto Beauty preset ----------

        function autoBeautyPreset() {
            return {
                brightness: 15,
                contrast:   10,
                saturation: 8,
                warmth:     5,
                sharpness:  15,
                skinSmooth: 20,
            };
        }

        // ---------- Auto Level (histogram stretch) ----------

        async function autoLevel(file, mime = 'image/jpeg') {
            const img = await fileToImage(file);
            const canvas = canvasFromImage(img);
            const ctx = canvas.getContext('2d');
            const data = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const d = data.data;

            // Build luminance histogram, find 1st and 99th percentile so
            // stray specular highlights / shadows don't dominate.
            const hist = new Uint32Array(256);
            for (let i = 0; i < d.length; i += 4) {
                const lum = (0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]) | 0;
                hist[lum]++;
            }
            const total = (d.length / 4);
            const lowCut = total * 0.01;
            const highCut = total * 0.99;
            let low = 0, high = 255, acc = 0;
            for (let v = 0; v < 256; v++) { acc += hist[v]; if (acc >= lowCut) { low = v; break; } }
            acc = 0;
            for (let v = 255; v >= 0; v--) { acc += hist[v]; if (acc >= (total - highCut)) { high = v; break; } }
            if (high <= low + 1) { high = low + 1; } // avoid divide-by-zero

            const scale = 255 / (high - low);
            for (let i = 0; i < d.length; i += 4) {
                d[i]     = _clamp((d[i]     - low) * scale);
                d[i + 1] = _clamp((d[i + 1] - low) * scale);
                d[i + 2] = _clamp((d[i + 2] - low) * scale);
            }
            ctx.putImageData(data, 0, 0);
            return await imageToFile(canvas, mime, 0.95, file.name || 'auto-level.jpg');
        }

        // ---------- Face-center crop (native FaceDetector where available) ----------

        // Returns { x, y, width, height } in image pixel coords or null.
        async function detectFace(file) {
            if (typeof FaceDetector === 'undefined') return null;
            try {
                const img = await fileToImage(file);
                const canvas = canvasFromImage(img);
                const detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
                const faces = await detector.detect(canvas);
                if (!faces || !faces.length) return null;
                return faces[0].boundingBox; // {x,y,width,height}
            } catch (e) {
                console.warn('FaceDetector failed:', e);
                return null;
            }
        }

        // Auto-crop centered on face with 2:2.4 aspect ratio (150x180 ID photo).
        // Returns a new File. Falls back to center-crop if no face found.
        async function faceCenterCrop(file, aspect = 150 / 180, mime = 'image/jpeg') {
            const img = await fileToImage(file);
            const face = await detectFace(file);

            const iw = img.width, ih = img.height;
            let cx, cy;
            if (face) {
                cx = face.x + face.width / 2;
                cy = face.y + face.height / 2 + face.height * 0.25; // shift down to include neck/shoulder
            } else {
                cx = iw / 2;
                cy = ih / 2;
            }

            // Target box height = face height * 3 (typical ID-photo framing),
            // clamped to image bounds.
            let boxH = face ? Math.min(ih, face.height * 3) : Math.min(iw, ih);
            let boxW = boxH * aspect;
            if (boxW > iw) { boxW = iw; boxH = boxW / aspect; }

            let sx = _clampRange(cx - boxW / 2, 0, iw - boxW);
            let sy = _clampRange(cy - boxH / 2, 0, ih - boxH);

            const out = document.createElement('canvas');
            out.width = Math.round(boxW);
            out.height = Math.round(boxH);
            out.getContext('2d').drawImage(img, sx, sy, boxW, boxH, 0, 0, out.width, out.height);
            return await imageToFile(out, mime, 0.95, file.name || 'face-crop.jpg');
        }

        function _clampRange(v, min, max) { return v < min ? min : v > max ? max : v; }

        // Public API
        return {
            applyAdjustments,
            autoBeautyPreset,
            autoLevel,
            faceCenterCrop,
            detectFace,
            fileToImage,
            imageToFile,
            canvasFromImage,
        };
    })();
</script>
