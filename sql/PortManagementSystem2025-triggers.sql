/*==============================================================*/
/* DBMS name:      Microsoft SQL Server 2016                    */
/* Created on:     12/21/2025 1:30:15 PM                        */
/*==============================================================*/
use [PortManagementSystemDb]
go



create trigger TD_CONTAINERS on CONTAINERS for delete as
begin
    declare
       @numrows  int,
       @errno    int,
       @errmsg   varchar(255)

    select  @numrows = @@rowcount
    if @numrows = 0
       return

    /*  Cannot delete parent "CONTAINERS" if children still exist in "CONTAINERSMOVEMENTS"  */
    if exists (select 1
               from   CONTAINERSMOVEMENTS t2, deleted t1
               where  t2.CONTAINER_NO = t1.CONTAINER_NO)
       begin
          select @errno  = 50006,
                 @errmsg = 'Children still exist in "CONTAINERSMOVEMENTS". Cannot delete parent "CONTAINERS".'
          goto error
       end


    return

/*  Errors handling  */
error:
   --raiserror @errno @errmsg
    RAISERROR (@errmsg, @errno, -1)
    rollback  transaction
end
go


create trigger TU_CONTAINERS on CONTAINERS for update as
begin
   declare
      @numrows  int,
      @numnull  int,
      @errno    int,
      @errmsg   varchar(255)

      select  @numrows = @@rowcount
      if @numrows = 0
         return

      /*  Cannot modify parent code in "CONTAINERS" if children still exist in "CONTAINERSMOVEMENTS"  */
      if update(CONTAINER_NO)
      begin
         if exists (select 1
                    from   CONTAINERSMOVEMENTS t2, inserted i1, deleted d1
                    where  t2.CONTAINER_NO = d1.CONTAINER_NO
                     and  (i1.CONTAINER_NO != d1.CONTAINER_NO))
            begin
               select @errno  = 50005,
                      @errmsg = 'Children still exist in "CONTAINERSMOVEMENTS". Cannot modify parent code in "CONTAINERS".'
               goto error
            end
      end


      return

/*  Errors handling  */
error:
    --raiserror @errno @errmsg
    RAISERROR (@errmsg, @errno, -1)
    rollback  transaction
end
go



create trigger TI_CONTAINERSMOVEMENTS on CONTAINERSMOVEMENTS for insert as
begin
    declare
       @numrows  int,
       @numnull  int,
       @errno    int,
       @errmsg   varchar(255)

    select  @numrows = @@rowcount
    if @numrows = 0
       return

    /*  Parent "CONTAINERS" must exist when inserting a child in "CONTAINERSMOVEMENTS"  */
    if update(CONTAINER_NO)
    begin
       if (select count(*)
           from   CONTAINERS t1, inserted t2
           where  t1.CONTAINER_NO = t2.CONTAINER_NO) != @numrows
          begin
             select @errno  = 50002,
                    @errmsg = 'Parent does not exist in "CONTAINERS". Cannot create child in "CONTAINERSMOVEMENTS".'
             goto error
          end
    end

    return

/*  Errors handling  */
error:
    --raiserror @errno @errmsg
    RAISERROR (@errmsg, @errno, -1)
    rollback  transaction
end
go


create trigger TU_CONTAINERSMOVEMENTS on CONTAINERSMOVEMENTS for update as
begin
   declare
      @numrows  int,
      @numnull  int,
      @errno    int,
      @errmsg   varchar(255)

      select  @numrows = @@rowcount
      if @numrows = 0
         return

      /*  Parent "CONTAINERS" must exist when updating a child in "CONTAINERSMOVEMENTS"  */
      if update(CONTAINER_NO)
      begin
         if (select count(*)
             from   CONTAINERS t1, inserted t2
             where  t1.CONTAINER_NO = t2.CONTAINER_NO) != @numrows
            begin
               select @errno  = 50003,
                      @errmsg = 'CONTAINERS" does not exist. Cannot modify child in "CONTAINERSMOVEMENTS".'
               goto error
            end
      end

      return

/*  Errors handling  */
error:
    --raiserror @errno @errmsg
    RAISERROR (@errmsg, @errno, -1)
    rollback  transaction
end
go

-- update arrival statuys when allocating
IF OBJECT_ID('trg_BerthAlloc', 'TR') IS NOT NULL DROP TRIGGER trg_BerthAlloc;
GO
CREATE TRIGGER trg_BerthAlloc
ON BERTHALLOCATION
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

