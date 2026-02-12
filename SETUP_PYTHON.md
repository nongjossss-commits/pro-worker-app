# AI Face Enhancement Setup Guide

The "Enhance Photo" feature in this application uses AI (Python) to clarify images. For this feature to work, Python and specific libraries must be installed on your server or local machine.

## Prerequisites

- **Python 3.8 or newer** (Python 3.10+ recommended)
- **Pip** (Python Package Installer)

## Step 1: Install Python

### Windows

1. Download the latest Python installer from [python.org/downloads](https://www.python.org/downloads/).
2. Run the installer.
3. **Important:** Check the box **"Add Python to PATH"** before clicking Install.
4. Verify installation by opening Command Prompt (cmd) and running:
   ```cmd
   python --version
   ```
   If it shows a version number (e.g., Python 3.10.x), you are ready.

### Linux (Ubuntu/Debian)

Run the following commands:
```bash
sudo apt update
sudo apt install python3 python3-pip python3-venv
```

### macOS

Install via Homebrew:
```bash
brew install python
```

---

## Step 2: Install Dependencies

Once Python is installed, run the provided script to install required AI libraries (`gfpgan`, `basicsr`, `opencv-python-headless`).

### Windows

Double-click or run the following file in Command Prompt:
```cmd
scripts/install_python_deps.bat
```

Alternatively, you can manually install the packages:
```cmd
pip install gfpgan basicsr opencv-python-headless
```

### Linux / macOS

Run the shell script:
```bash
sh scripts/install_python_deps.sh
```

Alternatively:
```bash
pip3 install gfpgan basicsr opencv-python-headless
```

---

## Troubleshooting

- **Error: "Python is not installed or configured correctly"**
  - Make sure you checked "Add Python to PATH" during installation.
  - Restart your computer after installing Python.

- **Error: "ModuleNotFoundError: No module named 'gfpgan'"**
  - Run the dependency installation script again.
  - Ensure you are using the correct pip for your Python installation (`pip` vs `pip3`).

- **Slow Processing**
  - The first time you run the enhancement, it may download a large model file (~300MB). This can take a few minutes depending on your internet connection. Subsequent runs will be faster.
