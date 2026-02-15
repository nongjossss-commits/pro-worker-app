import argparse
import sys
import os
import shutil
import time

# --- Setup Instructions ---
# To enable AI Face Restoration, you must install the following dependencies on your server:
# pip install gfpgan basicsr opencv-python-headless torch torchvision
#
# You also need to download the GFPGAN model weights:
# 1. Create a directory 'experiments/pretrained_models' in this folder or configure the path.
# 2. Download GFPGANv1.4.pth from https://github.com/TencentARC/GFPGAN/releases/download/v1.3.4/GFPGANv1.4.pth
# 3. Place it in the correct location or update the model_path variable below.

def main():
    parser = argparse.ArgumentParser(description='AI Face Restoration using GFPGAN')
    parser.add_argument('--input', type=str, required=True, help='Path to input image')
    parser.add_argument('--output', type=str, required=True, help='Path to output image')
    parser.add_argument('--upscale', type=int, default=2, help='The final upsampling scale of the image')
    parser.add_argument('--bg_upsampler', type=str, default='realesrgan', help='background upsampler')

    args = parser.parse_args()

    input_path = args.input
    output_path = args.output

    if not os.path.exists(input_path):
        sys.stderr.write(f"Error: Input file not found at {input_path}\n")
        sys.exit(1)

    # --- Monkey Patch for torchvision compatibility (Fixes basicsr crash) ---
    try:
        import torchvision
        from torchvision.transforms import functional as F

        # Patch 1: Add to sys.modules so 'import torchvision.transforms.functional_tensor' works
        if 'torchvision.transforms.functional_tensor' not in sys.modules:
            sys.modules['torchvision.transforms.functional_tensor'] = F

        # Patch 2: Add as attribute to transforms so 'from torchvision.transforms import functional_tensor' works
        if hasattr(torchvision, 'transforms') and not hasattr(torchvision.transforms, 'functional_tensor'):
            setattr(torchvision.transforms, 'functional_tensor', F)

    except ImportError as e:
        # Log warning but continue; main import block will catch if critical
        sys.stderr.write(f"Warning: Failed to apply torchvision monkey patch: {e}\n")
    except Exception as e:
        sys.stderr.write(f"Warning: Unexpected error applying torchvision monkey patch: {e}\n")

    # --- Try to Import Dependencies ---
    try:
        import cv2
        import torch
        from gfpgan import GFPGANer

        # Initialize GFPGANer
        # We use 'clean' arch for GFPGANv1.4
        # upscale=2 is default, but can be increased via args
        # bg_upsampler=None prevents background enhancement (faster but less sharp background)
        # If user wants full sharpness, we could enable realesrgan, but it's CPU intensive.
        # We stick to None for speed unless specifically requested.

        # Check if CUDA is available for speed
        device = 'cuda' if torch.cuda.is_available() else 'cpu'
        if device == 'cpu':
             sys.stderr.write("Warning: Running on CPU. This might be slow.\n")

        restorer = GFPGANer(
            model_path='https://github.com/TencentARC/GFPGAN/releases/download/v1.3.0/GFPGANv1.4.pth', # Auto-download
            upscale=args.upscale,
            arch='clean',
            channel_multiplier=2,
            bg_upsampler=None, # Keep None for performance
            device=torch.device(device)
        )

        # Read image
        img = cv2.imread(input_path, cv2.IMREAD_COLOR)
        if img is None:
             sys.stderr.write(f"Error: Failed to read image at {input_path}\n")
             sys.exit(1)

        # Restore
        # cropped_faces, restored_faces, restored_img
        _, _, restored_img = restorer.enhance(img, has_aligned=False, only_center_face=False, paste_back=True)

        # Save output
        if restored_img is not None:
            cv2.imwrite(output_path, restored_img)
            # Verify file exists
            if os.path.exists(output_path):
                print(f"Success: Image restored and saved to {output_path}")
            else:
                sys.stderr.write("Error: Output file failed to write.\n")
                sys.exit(1)
        else:
            sys.stderr.write("Error: Restoration returned None.\n")
            sys.exit(1)

    except ImportError as e:
        # Include exception type so PHP can detect it
        sys.stderr.write(f"Error: Missing Python dependencies: {type(e).__name__}: {e}\n")
        sys.stderr.write("Please run: pip install gfpgan basicsr opencv-python-headless torch torchvision\n")
        sys.exit(1)

    except Exception as e:
        sys.stderr.write(f"Error during restoration: {e}\n")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()
