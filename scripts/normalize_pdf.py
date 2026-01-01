import sys
import os
from pypdf import PdfReader, PdfWriter

def normalize(input_path, output_path):
    try:
        reader = PdfReader(input_path)
        writer = PdfWriter()

        for page in reader.pages:
            writer.add_page(page)

        # Write the normalized PDF
        with open(output_path, "wb") as f:
            writer.write(f)

        # Verify file creation
        if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
            print("Success")
            sys.exit(0)
        else:
            print("Error: Output file not created or empty")
            sys.exit(1)

    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python normalize_pdf.py <input> <output>")
        sys.exit(1)

    normalize(sys.argv[1], sys.argv[2])
