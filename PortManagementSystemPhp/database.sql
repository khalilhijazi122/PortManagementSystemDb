
USE master;
GO



-- add ship
IF OBJECT_ID('sp_AddShip', 'P') IS NOT NULL DROP PROCEDURE sp_AddShip;
GO
CREATE PROCEDURE sp_AddShip
    @SHIP_IMO VARCHAR(50),
    @SHIPNAME NVARCHAR(200),
    @COMPANY NVARCHAR(200)
AS
BEGIN
    INSERT INTO SHIPS (SHIP_IMO, SHIPNAME, COMPANY)
    VALUES (@SHIP_IMO, @SHIPNAME, @COMPANY);
END
GO

-- rigistration of arrival
IF OBJECT_ID('sp_RegisterArrival', 'P') IS NOT NULL DROP PROCEDURE sp_RegisterArrival;
GO
CREATE PROCEDURE sp_RegisterArrival
    @ARRIVAL_REF VARCHAR(50),
    @SHIP_IMO VARCHAR(50),
    @ARRIVALDATE DATE
AS
BEGIN
    INSERT INTO ARRIVALS (ARRIVAL_REF, SHIP_IMO, ARRIVALDATE)
    VALUES (@ARRIVAL_REF, @SHIP_IMO, @ARRIVALDATE);
END
GO

-- allocate berth
IF OBJECT_ID('sp_AllocateBerth', 'P') IS NOT NULL DROP PROCEDURE sp_AllocateBerth;
GO
CREATE PROCEDURE sp_AllocateBerth
    @ALLOC_CODE VARCHAR(50),
    @ARRIVAL_REF VARCHAR(50),
    @BERTH_CODE VARCHAR(50)
AS
BEGIN
    INSERT INTO BERTH_ALLOCATIONS (ALLOC_CODE, ARRIVAL_REF, BERTH_CODE)
    VALUES (@ALLOC_CODE, @ARRIVAL_REF, @BERTH_CODE);
    
    UPDATE BERTHS SET STATUS = N'reserved' WHERE BERTH_CODE = @BERTH_CODE;
END
GO

-- add container
IF OBJECT_ID('sp_AddContainer', 'P') IS NOT NULL DROP PROCEDURE sp_AddContainer;
GO
CREATE PROCEDURE sp_AddContainer
    @CONTAINER_NO VARCHAR(50),
    @ARRIVAL_REF VARCHAR(50),
    @TYPE NVARCHAR(50),
    @STATUS NVARCHAR(50)
AS
BEGIN
    INSERT INTO CONTAINERS (CONTAINER_NO, ARRIVAL_REF, TYPE, STATUS)
    VALUES (@CONTAINER_NO, @ARRIVAL_REF, @TYPE, @STATUS);
END
GO

-- check-out of ship
IF OBJECT_ID('sp_RegisterDeparture', 'P') IS NOT NULL DROP PROCEDURE sp_RegisterDeparture;
GO
CREATE PROCEDURE sp_RegisterDeparture
    @ARRIVAL_REF VARCHAR(50),
    @DEPARTUREDATE DATE
AS
BEGIN
    UPDATE ARRIVALS SET DEPARTUREDATE = @DEPARTUREDATE WHERE ARRIVAL_REF = @ARRIVAL_REF;
    
    DECLARE @BERTH VARCHAR(50);
    SELECT @BERTH = BERTH_CODE FROM BERTH_ALLOCATIONS WHERE ARRIVAL_REF = @ARRIVAL_REF;
    
    IF @BERTH IS NOT NULL
        UPDATE BERTHS SET STATUS = N'available' WHERE BERTH_CODE = @BERTH;
END
GO

-- ==========================================
-- Functions
-- ==========================================

-- calculate time for ship
IF OBJECT_ID('fn_GetShipDays', 'FN') IS NOT NULL DROP FUNCTION fn_GetShipDays;
GO
CREATE FUNCTION fn_GetShipDays(@ARRIVAL_REF VARCHAR(50))
RETURNS INT
AS
BEGIN
    DECLARE @Days INT;
    DECLARE @Arrival DATE, @Departure DATE;
    
    SELECT @Arrival = ARRIVALDATE, @Departure = DEPARTUREDATE 
    FROM ARRIVALS WHERE ARRIVAL_REF = @ARRIVAL_REF;
    
    IF @Departure IS NULL
        SET @Days = DATEDIFF(DAY, @Arrival, GETDATE());
    ELSE
        SET @Days = DATEDIFF(DAY, @Arrival, @Departure);
    
    RETURN @Days;
END
GO

-- count containers
IF OBJECT_ID('fn_CountContainers', 'FN') IS NOT NULL DROP FUNCTION fn_CountContainers;
GO
CREATE FUNCTION fn_CountContainers(@ARRIVAL_REF VARCHAR(50))
RETURNS INT
AS
BEGIN
    DECLARE @Count INT;
    SELECT @Count = COUNT(*) FROM CONTAINERS WHERE ARRIVAL_REF = @ARRIVAL_REF;
    RETURN ISNULL(@Count, 0);
END
GO

-- available
IF OBJECT_ID('fn_AvailableBerths', 'FN') IS NOT NULL DROP FUNCTION fn_AvailableBerths;
GO
CREATE FUNCTION fn_AvailableBerths()
RETURNS INT
AS
BEGIN
    DECLARE @Count INT;
    SELECT @Count = COUNT(*) FROM BERTHS WHERE STATUS = N'available';
    RETURN ISNULL(@Count, 0);
END
GO

-- ==========================================
-- Triggers
-- ==========================================

-- update arrival statuys when allocating
IF OBJECT_ID('trg_BerthAlloc', 'TR') IS NOT NULL DROP TRIGGER trg_BerthAlloc;
GO
CREATE TRIGGER trg_BerthAlloc
ON BERTH_ALLOCATIONS
AFTER INSERT
AS
BEGIN
    UPDATE BERTHS SET STATUS = N'reserved'
    WHERE BERTH_CODE IN (SELECT BERTH_CODE FROM INSERTED);
END
GO
--Automatic recording of movement when a container is added
IF OBJECT_ID('trg_ContainerAdd', 'TR') IS NOT NULL DROP TRIGGER trg_ContainerAdd;
GO
CREATE TRIGGER trg_ContainerAdd
ON CONTAINERS
AFTER INSERT
AS
BEGIN
    INSERT INTO CONTAINER_MOVEMENTS (CONTAINER_NO, MOVEMENTTYPE, LOCATION)
    SELECT CONTAINER_NO, N'arrival', N'Unloading area'
    FROM INSERTED;
END
GO

-- ==========================================
-- Cursor Procedure - reports ships
-- ==========================================
IF OBJECT_ID('sp_ShipsReport', 'P') IS NOT NULL DROP PROCEDURE sp_ShipsReport;
GO
CREATE PROCEDURE sp_ShipsReport
AS
BEGIN
    DECLARE @IMO VARCHAR(50), @Name NVARCHAR(200), @Company NVARCHAR(200);
    DECLARE @ArrivalRef VARCHAR(50), @ArrivalDate DATE;
    
    CREATE TABLE #Report (
        SHIP_IMO VARCHAR(50),
        SHIPNAME NVARCHAR(200),
        COMPANY NVARCHAR(200),
        ARRIVAL_REF VARCHAR(50),
        ARRIVALDATE DATE,
        DAYS INT,
        CONTAINERS INT
    );
    
    DECLARE ship_cursor CURSOR FOR
    SELECT s.SHIP_IMO, s.SHIPNAME, s.COMPANY, a.ARRIVAL_REF, a.ARRIVALDATE
    FROM SHIPS s, ARRIVALS a
    WHERE s.SHIP_IMO = a.SHIP_IMO AND a.DEPARTUREDATE IS NULL;
    
    OPEN ship_cursor;
    FETCH NEXT FROM ship_cursor INTO @IMO, @Name, @Company, @ArrivalRef, @ArrivalDate;
    
    WHILE @@FETCH_STATUS = 0
    BEGIN
        INSERT INTO #Report
        SELECT @IMO, @Name, @Company, @ArrivalRef, @ArrivalDate,
               dbo.fn_GetShipDays(@ArrivalRef),
               dbo.fn_CountContainers(@ArrivalRef);
        
        FETCH NEXT FROM ship_cursor INTO @IMO, @Name, @Company, @ArrivalRef, @ArrivalDate;
    END
    
    CLOSE ship_cursor;
    DEALLOCATE ship_cursor;
    
    SELECT * FROM #Report;
    DROP TABLE #Report;
END
GO



-- ships
IF NOT EXISTS (SELECT * FROM SHIPS WHERE SHIP_IMO = 'IMO-1111111')
    INSERT INTO SHIPS VALUES ('IMO-1111111', N'beirut ship', N'east company');

IF NOT EXISTS (SELECT * FROM SHIPS WHERE SHIP_IMO = 'IMO-2222222')
    INSERT INTO SHIPS VALUES ('IMO-2222222', N'dubai ship', N'khalij company');

-- berths
IF NOT EXISTS (SELECT * FROM BERTHS WHERE BERTH_CODE = 'B-01')
    INSERT INTO BERTHS VALUES ('B-01', N'b Berth', N'available');

IF NOT EXISTS (SELECT * FROM BERTHS WHERE BERTH_CODE = 'B-02')
    INSERT INTO BERTHS VALUES ('B-02', N'Berth', N'available');

IF NOT EXISTS (SELECT * FROM BERTHS WHERE BERTH_CODE = 'B-03')
    INSERT INTO BERTHS VALUES ('B-03', N'Berth', N'available');

go