import argparse
import sys
import os
import shutil
import json

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

    result = {
        'status': 'error',
        'message': 'Unknown error',
        'output_path': output_path
    }

    if not os.path.exists(input_path):
        result['message'] = f"Input file not found at {input_path}"
        print(json.dumps(result))
        sys.exit(1)

    # --- Try to Import GFPGAN ---
    try:
        import cv2
        from gfpgan import GFPGANer

        # Initialize GFPGANer
        # Note: GFPGANer will attempt to download the model if not found locally.
        # Ideally, we should handle this explicitly or ensure the model is pre-downloaded.
        restorer = GFPGANer(
            model_path='https://github.com/TencentARC/GFPGAN/releases/download/v1.3.0/GFPGANv1.4.pth', # Auto-download
            upscale=args.upscale,
            arch='clean',
            channel_multiplier=2,
            bg_upsampler=None
        )

        # Read image
        img = cv2.imread(input_path, cv2.IMREAD_COLOR)
        if img is None:
             raise ValueError("Could not read input image")

        # Restore
        # cropped_faces, restored_faces, restored_img
        _, _, restored_img = restorer.enhance(img, has_aligned=False, only_center_face=False, paste_back=True)

        # Save output
        if restored_img is not None:
            cv2.imwrite(output_path, restored_img)
            result['status'] = 'success'
            result['message'] = 'Image restored successfully'
        else:
            # Fallback if enhancement returns None (rare)
            shutil.copy(input_path, output_path)
            result['status'] = 'fallback'
            result['message'] = 'Restoration returned None, copied original'

    except ImportError as e:
        # --- Fallback Mode ---
        # Just copy the file so the web app flow completes
        try:
            shutil.copy(input_path, output_path)
            result['status'] = 'fallback'
            result['message'] = f"AI dependencies not found ({str(e)}). Copied original."
        except Exception as copy_err:
            result['message'] = f"Error copying file: {str(copy_err)}"
            print(json.dumps(result))
            sys.exit(1)

    except Exception as e:
        # Attempt fallback on crash
        try:
            shutil.copy(input_path, output_path)
            result['status'] = 'fallback'
            result['message'] = f"Error during restoration: {str(e)}. Copied original."
        except Exception as copy_err:
             result['message'] = f"Error copying file (after crash): {str(copy_err)}"
             print(json.dumps(result))
             sys.exit(1)

    # Output JSON result
    print(json.dumps(result))

if __name__ == '__main__':
    main()
