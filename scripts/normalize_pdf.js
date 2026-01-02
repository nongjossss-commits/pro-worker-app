const { PDFDocument } = require('pdf-lib');
const fs = require('fs').promises;

async function normalizePdf() {
    const args = process.argv.slice(2);
    if (args.length < 2) {
        console.error('Usage: node normalize_pdf.js <input_path> <output_path>');
        process.exit(1);
    }

    const inputPath = args[0];
    const outputPath = args[1];

    try {
        const pdfBytes = await fs.readFile(inputPath);

        // Load the PDF document
        const pdfDoc = await PDFDocument.load(pdfBytes);

        // Save the PDF with useObjectStreams: false to maximize compatibility (v1.4-like structure)
        const pdfBytesNormalized = await pdfDoc.save({ useObjectStreams: false });

        await fs.writeFile(outputPath, pdfBytesNormalized);

        console.log('PDF normalized successfully');
        process.exit(0);
    } catch (error) {
        console.error('Error normalizing PDF:', error);
        process.exit(1);
    }
}

normalizePdf();
