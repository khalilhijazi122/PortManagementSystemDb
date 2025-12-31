@echo off
REM ===============================
REM Configuration
REM ===============================

SET LAB=C:\Database.prj-2025
SET SERV=-S WIRE777\SQLEXPRESS -E
SET DBApp=PortManagementSystem2025

SET SQLDIR=%LAB%\sql
SET LOGDIR=%LAB%\log


IF NOT EXIST "%LOGDIR%" (
    mkdir "%LOGDIR%"
)

echo =====================================
echo Beginning database setup...
echo =====================================

REM ===============================
REM Create Database
REM ===============================
osql -S %SERV% -i "%SQLDIR%\%DBApp%-createDB.sql" -o "%LOGDIR%\%DBApp%-createDB.log"
osql -S %SERV% -i "%SQLDIR%\%DBApp%-tables.sql" -o "%LOGDIR%\%DBApp%-tables.log"
osql -S %SERV% -i "%SQLDIR%\%DBApp%-triggers.sql" -o "%LOGDIR%\%DBApp%-triggers.log"
osql -S %SERV% -i "%SQLDIR%\%DBApp%-indexes.sql" -o "%LOGDIR%\%DBApp%-indexes.log"
osql -S %SERV% -i "%SQLDIR%\%DBApp%-Procedures.sql" -o "%LOGDIR%\%DBApp%-Procedures.log"
osql -S %SERV% -i "%SQLDIR%\%DBApp%-Privileges.sql" -o "%LOGDIR%\%DBApp%-Privileges.log"
osql -S %SERV% -i "%SQLDIR%\%DBApp%-Views.sql" -o "%LOGDIR%\%DBApp%-Views.log"

echo =====================================
echo Database setup completed.
echo =====================================

pause
