#!/bin/bash
echo "Checking for Python..."
if command -v python3 &> /dev/null; then
    echo "Python 3 found: $(python3 --version)"
    PYTHON_CMD=python3
elif command -v python &> /dev/null; then
    echo "Python found: $(python --version)"
    PYTHON_CMD=python
else
    echo "Error: Python is not installed. Please install Python 3.8+."
    exit 1
fi

echo "Installing dependencies..."
$PYTHON_CMD -m pip install -r requirements.txt

echo "Done."
