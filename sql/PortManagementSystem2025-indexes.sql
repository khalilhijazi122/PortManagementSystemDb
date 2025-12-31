/*==============================================================*/
/* DBMS name:      Microsoft SQL Server 2016                    */
/* Created on:     12/21/2025 1:32:05 PM                        */
/*==============================================================*/


/*==============================================================*/
/* Index: ARRIVE_FK                                             */
/*==============================================================*/

use [PortManagementSystemDb]
go


create nonclustered index ARRIVE_FK on ARRIVALS (SHIP_IMO ASC)
go

/*==============================================================*/
/* Index: ALLOCATEBERTH_FK                                      */
/*==============================================================*/




create nonclustered index ALLOCATEBERTH_FK on BERTHALLOCATION (BERTH_CODE ASC)
go

/*==============================================================*/
/* Index: ALLOCBERARRIVAL_FK                                    */
/*==============================================================*/




create nonclustered index ALLOCBERARRIVAL_FK on BERTHALLOCATION (ARRIVAL_REF ASC)
go

/*==============================================================*/
/* Index: CONTAINS_FK                                           */
/*==============================================================*/




create nonclustered index CONTAINS_FK on CONTAINERS (ARRIVAL_REF ASC)
go

/*==============================================================*/
/* Index: MOVE_FK                                               */
/*==============================================================*/




create nonclustered index MOVE_FK on CONTAINERSMOVEMENTS (CONTAINER_NO ASC)
go

