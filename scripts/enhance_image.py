#!/usr/bin/env python3
"""
AI Image Enhancement Script
Uses GFPGAN (face restoration) with Real-ESRGAN (background upscaling)
Auto-detects GPU availability - uses CUDA if available, otherwise CPU
"""

import sys
import os
import argparse
import cv2
import numpy as np
from pathlib import Path

def enhance_image(input_path, output_path, mode='auto', upscale=2):
    """
    Enhance an image using AI super-resolution.

    Args:
        input_path: Path to input image
        output_path: Path to save enhanced image
        mode: 'face' (GFPGAN), 'general' (Real-ESRGAN), or 'auto' (detect face)
        upscale: Upscale factor (2 or 4)
    """
    import torch

    # Auto-detect GPU
    use_gpu = torch.cuda.is_available()
    device = 'cuda' if use_gpu else 'cpu'
    print(f"[INFO] Using device: {device}" + (f" ({torch.cuda.get_device_name(0)})" if use_gpu else " (CPU mode)"), flush=True)

    # Read image
    img = cv2.imread(input_path, cv2.IMREAD_UNCHANGED)
    if img is None:
        print(f"[ERROR] Cannot read image: {input_path}", file=sys.stderr)
        sys.exit(1)

    h, w = img.shape[:2]
    print(f"[INFO] Input: {w}x{h}", flush=True)

    # Auto-detect mode: try face detection
    if mode == 'auto':
        # Simple face detection using OpenCV
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if len(img.shape) == 3 else img
        face_cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(face_cascade_path)
        faces = face_cascade.detectMultiScale(gray, 1.1, 4, minSize=(30, 30))
        mode = 'face' if len(faces) > 0 else 'general'
        print(f"[INFO] Auto-detected mode: {mode} (faces found: {len(faces)})", flush=True)

    result = None

    if mode == 'face':
        try:
            print("[INFO] Loading GFPGAN model...", flush=True)
            from gfpgan import GFPGANer

            # Model path - download automatically if not exists
            model_path = os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'ai_models')
            os.makedirs(model_path, exist_ok=True)

            restorer = GFPGANer(
                model_path='https://github.com/TencentARC/GFPGAN/releases/download/v1.3.0/GFPGANv1.4.pth',
                upscale=upscale,
                arch='clean',
                channel_multiplier=2,
                bg_upsampler=_get_bg_upsampler(upscale, use_gpu),
                device=torch.device(device)
            )

            print("[INFO] Processing with GFPGAN...", flush=True)
            _, _, result = restorer.enhance(
                img,
                has_aligned=False,
                only_center_face=False,
                paste_back=True
            )

        except Exception as e:
            print(f"[WARN] GFPGAN failed: {e}, falling back to Real-ESRGAN", flush=True)
            mode = 'general'

    if mode == 'general' or result is None:
        try:
            print("[INFO] Loading Real-ESRGAN model...", flush=True)
            from realesrgan import RealESRGANer
            from basicsr.archs.rrdbnet_arch import RRDBNet

            model = RRDBNet(num_in_ch=3, num_out_ch=3, num_feat=64, num_block=23, num_grow_ch=32, scale=4)

            upsampler = RealESRGANer(
                scale=4,
                model_path='https://github.com/xinntao/Real-ESRGAN/releases/download/v0.1.0/RealESRGAN_x4plus.pth',
                model=model,
                tile=400 if not use_gpu else 0,  # Tile for CPU to save memory
                tile_pad=10,
                pre_pad=0,
                half=use_gpu,  # FP16 only on GPU
                device=torch.device(device)
            )

            print("[INFO] Processing with Real-ESRGAN...", flush=True)
            result, _ = upsampler.enhance(img, outscale=upscale)

        except Exception as e:
            print(f"[ERROR] Real-ESRGAN failed: {e}", file=sys.stderr)
            sys.exit(1)

    if result is not None:
        # Save result
        rh, rw = result.shape[:2]
        print(f"[INFO] Output: {rw}x{rh}", flush=True)

        # Save as JPEG with high quality
        ext = Path(output_path).suffix.lower()
        if ext in ['.jpg', '.jpeg']:
            cv2.imwrite(output_path, result, [cv2.IMWRITE_JPEG_QUALITY, 95])
        elif ext == '.png':
            cv2.imwrite(output_path, result, [cv2.IMWRITE_PNG_COMPRESSION, 6])
        else:
            cv2.imwrite(output_path, result)

        print(f"[OK] Saved to: {output_path}", flush=True)
    else:
        print("[ERROR] Enhancement produced no result", file=sys.stderr)
        sys.exit(1)


def _get_bg_upsampler(upscale, use_gpu):
    """Create background upsampler for GFPGAN."""
    try:
        from realesrgan import RealESRGANer
        from basicsr.archs.rrdbnet_arch import RRDBNet
        import torch

        model = RRDBNet(num_in_ch=3, num_out_ch=3, num_feat=64, num_block=23, num_grow_ch=32, scale=4)

        return RealESRGANer(
            scale=4,
            model_path='https://github.com/xinntao/Real-ESRGAN/releases/download/v0.1.0/RealESRGAN_x4plus.pth',
            model=model,
            tile=400 if not use_gpu else 0,
            tile_pad=10,
            pre_pad=0,
            half=use_gpu,
            device=torch.device('cuda' if use_gpu else 'cpu')
        )
    except Exception as e:
        print(f"[WARN] Could not create background upsampler: {e}", flush=True)
        return None


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='AI Image Enhancement')
    parser.add_argument('input', help='Input image path')
    parser.add_argument('output', help='Output image path')
    parser.add_argument('--mode', choices=['auto', 'face', 'general'], default='auto',
                        help='Enhancement mode (default: auto)')
    parser.add_argument('--upscale', type=int, choices=[2, 4], default=2,
                        help='Upscale factor (default: 2)')

    args = parser.parse_args()
    enhance_image(args.input, args.output, args.mode, args.upscale)
