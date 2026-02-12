#!/bin/bash

# Navigate to script directory to resolve relative paths
cd "$(dirname "$0")"

# Determine python command
PYTHON_CMD="python3"
if ! command -v python3 &> /dev/null; then
    if command -v python &> /dev/null; then
        PYTHON_CMD="python"
    else
        echo "Error: Python is not installed."
        exit 1
    fi
fi

echo "Using Python command: $PYTHON_CMD"

# Upgrade pip
$PYTHON_CMD -m pip install --upgrade pip

# Install dependencies
$PYTHON_CMD -m pip install -r ../requirements.txt

echo "Dependencies installed successfully."
