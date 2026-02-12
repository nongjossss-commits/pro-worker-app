@echo off
setlocal

echo Checking for Python...
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: 'python' command not found. Please install Python and add it to your PATH.
    pause
    exit /b 1
)

python --version
echo.

echo Installing dependencies...
python -m pip install -r requirements.txt
if %errorlevel% neq 0 (
    echo Error installing dependencies.
    pause
    exit /b 1
)

echo.
echo Done. You can close this window.
pause
