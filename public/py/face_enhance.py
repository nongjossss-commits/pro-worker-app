import argparse
import sys
import os
import shutil
import time

# --- Setup Instructions ---
# To enable AI Face Restoration, you must install the following dependencies on your server:
# pip install gfpgan basicsr opencv-python-headless
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
        print(f"Error: Input file not found at {input_path}")
        sys.exit(1)

    # --- Try to Import GFPGAN ---
    try:
        import cv2
        from gfpgan import GFPGANer

        # Check if model exists (optional, or let GFPGANer handle it/download it)
        # For this script, we assume the user has set it up or GFPGANer will try to download to default location.
        # Default model path usually: experiments/pretrained_models/GFPGANv1.4.pth

        # Initialize GFPGANer
        restorer = GFPGANer(
            model_path='https://github.com/TencentARC/GFPGAN/releases/download/v1.3.0/GFPGANv1.4.pth', # Auto-download
            upscale=args.upscale,
            arch='clean',
            channel_multiplier=2,
            bg_upsampler=None # Disable background upsampler for speed/simplicity if not needed, or use 'realesrgan'
        )

        # Read image
        img = cv2.imread(input_path, cv2.IMREAD_COLOR)

        # Restore
        # cropped_faces, restored_faces, restored_img
        _, _, restored_img = restorer.enhance(img, has_aligned=False, only_center_face=False, paste_back=True)

        # Save output
        if restored_img is not None:
            cv2.imwrite(output_path, restored_img)
            print(f"Success: Image restored and saved to {output_path}")
        else:
            print("Error: Restoration returned None.")
            shutil.copy(input_path, output_path)

    except ImportError as e:
        # --- Fallback Mode ---
        sys.stderr.write(f"Warning: AI dependencies not found ({e}). Using fallback mode (copy).\n")
        sys.stderr.write("To enable AI, install: pip install gfpgan basicsr opencv-python-headless\n")

        # Just copy the file so the web app flow completes
        try:
            shutil.copy(input_path, output_path)
            print(f"Fallback: Image copied to {output_path}")
        except Exception as copy_err:
            print(f"Error copying file: {copy_err}")
            sys.exit(1)

    except Exception as e:
        sys.stderr.write(f"Error during restoration: {e}\n")
        # Attempt fallback on crash
        try:
            shutil.copy(input_path, output_path)
            print(f"Fallback (after error): Image copied to {output_path}")
        except:
            sys.exit(1)

if __name__ == '__main__':
    main()
