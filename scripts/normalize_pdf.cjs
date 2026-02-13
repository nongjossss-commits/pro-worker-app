const { PDFDocument } = require('pdf-lib');
const fs = require('fs');

async function normalize() {
  try {
    const inputPath = process.argv[2];
    const outputPath = process.argv[3];

    if (!inputPath || !outputPath) {
      console.error("Usage: node normalize_pdf.js <input> <output>");
      process.exit(1);
    }

    const pdfBytes = fs.readFileSync(inputPath);
    // Load and save - this essentially normalizes the PDF structure
    const pdfDoc = await PDFDocument.load(pdfBytes, { ignoreEncryption: true });

    // Convert to standard PDF structure
    // Disable object streams for FPDI compatibility (FPDI free version does not support PDF 1.5+ compressed object streams)
    const pdfBytesSaved = await pdfDoc.save({ useObjectStreams: false });

    fs.writeFileSync(outputPath, pdfBytesSaved);
    console.log("PDF normalized successfully");
  } catch (e) {
    console.error("Error normalizing PDF:", e);
    process.exit(1);
  }
}

normalize();
