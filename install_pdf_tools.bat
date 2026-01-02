@echo off
chcp 65001 >nul
echo ========================================================
echo   Auto-Install PDF Tools for Jules System
echo   (การติดตั้งเครื่องมือ PDF อัตโนมัติ)
echo ========================================================
echo.
echo This script will install Ghostscript, which is required
echo to fix PDF version issues automatically.
echo.
echo ระบบกำลังจะติดตั้ง Ghostscript เพื่อแก้ปัญหาไฟล์ PDF อัตโนมัติ
echo.

WHERE winget >nul 2>nul
IF %ERRORLEVEL% NEQ 0 (
    echo [ERROR] 'winget' command not found.
    echo Please install Ghostscript manually from: https://www.ghostscript.com/releases/gsdnld.html
    echo.
    echo ไม่พบคำสั่ง winget กรุณาดาวน์โหลดและติดตั้ง Ghostscript ด้วยตนเอง
    pause
    exit /b 1
)

echo [INFO] Found winget. Installing Ghostscript...
echo [INFO] กำลังติดตั้ง Ghostscript...
echo.

winget install -e --id Ghostscript.Ghostscript

IF %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo   INSTALLATION SUCCESSFUL / ติดตั้งสำเร็จ
    echo ========================================================
    echo.
    echo You may need to restart your terminal or VS Code.
    echo กรุณาปิดและเปิด Terminal หรือ VS Code ใหม่เพื่อให้ระบบรับทราบการเปลี่ยนแปลง
) ELSE (
    echo.
    echo [ERROR] Installation failed.
    echo Please try installing manually or run this script as Administrator.
    echo การติดตั้งล้มเหลว กรุณาลองใหม่ในฐานะผู้ดูแลระบบ (Run as Administrator)
)

pause
