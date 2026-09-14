@echo off
rem Tutte le prove del motore: quelle PHP e quelle del JavaScript.
rem Uscita 1 se una qualunque e' rossa.
setlocal
set ERR=0
"C:\Program Files\PHP\php.exe" "%~dp0Esegui.php" || set ERR=1
echo.
node "%~dp0prove.mjs" || set ERR=1
exit /b %ERR%
