use master
GO


CREATE DATABASE PortManagementSystemDb
ON
( NAME = PortManagementSystem_dat ,
FILENAME = 'C:\Database.prj-2025\data\PortManagementSystem.mdf')
LOG ON
( NAME = 'PortManagementSystem_log' ,
FILENAME = 'C:\Database.prj-2025\log\PortManagementSystem.ldf')
go