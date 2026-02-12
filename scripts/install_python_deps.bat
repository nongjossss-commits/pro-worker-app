@echo off
setlocal EnableDelayedExpansion

pushd "%~dp0"

set PYTHON_CMD=python

where python >nul 2>nul
if !errorlevel! neq 0 (
    where py >nul 2>nul
    if !errorlevel! neq 0 (
        echo Error: Python is not installed.
        popd
        exit /b 1
    )
    set PYTHON_CMD=py
)

echo Using Python command: !PYTHON_CMD!

!PYTHON_CMD! -m pip install --upgrade pip
!PYTHON_CMD! -m pip install -r ..\requirements.txt

if !errorlevel! neq 0 (
    echo Error installing dependencies.
    popd
    exit /b !errorlevel!
)

echo Dependencies installed successfully.
popd
pause
