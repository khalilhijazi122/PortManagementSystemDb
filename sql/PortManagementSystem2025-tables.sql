/*==============================================================*/
/* DBMS name:      Microsoft SQL Server 2016                    */
/* Created on:     12/10/2025 12:39:22 AM                       */
/*==============================================================*/
use [PortManagementSystemDb]
go

/*==============================================================*/
/* Table: SHIPS                                                 */
/*==============================================================*/
create table SHIPS (
   SHIP_IMO             varchar(20)          not null,
   SHIPNAME             varchar(20)          null,
   COMPANY              varchar(100)         null,
   constraint PK_SHIPS primary key (SHIP_IMO)
)
go

/*==============================================================*/
/* Table: ARRIVALS                                              */
/*==============================================================*/
create table ARRIVALS (
   ARRIVAL_REF           varchar(50)          not null,
   SHIP_IMO             varchar(20)          not null,
   ARRIVALDATE          datetime             null,
   DEPARTUREDATE        datetime             null,
   constraint PK_ARRIVALS primary key (ARRIVAL_REF),
   constraint FK_ARRIVALS_ARRIVE_SHIPS foreign key (SHIP_IMO)
      references SHIPS (SHIP_IMO)
)
go

/*==============================================================*/
/* Table: BERTHS                                                */
/*==============================================================*/
create table BERTHS (
   BERTH_CODE             varchar(20)          not null,
   BERTHNAME            varchar(50)          null,
   STATUS               varchar(20)          null,
   constraint PK_BERTHS primary key (BERTH_CODE )
)
go

/*==============================================================*/
/* Table: BERTHALLOCATION                                       */
/*==============================================================*/
create table BERTHALLOCATION (
   ALLOC_CODE             varchar(50)          not null,
   ARRIVAL_REF           varchar(50)          null,
   BERTH_CODE             varchar(20)          not null,
   constraint PK_BERTHALLOCATION primary key (ALLOC_CODE ),
   constraint FK_BERTHALL_ALLOCATEB_BERTHS foreign key (BERTH_CODE )
      references BERTHS (BERTH_CODE ),
   constraint FK_BERTHALL_ALLOCBERA_ARRIVALS foreign key (ARRIVAL_REF)
      references ARRIVALS (ARRIVAL_REF)
)
go

/*==============================================================*/
/* Table: CONTAINERS                                            */
/*==============================================================*/
create table CONTAINERS (
   CONTAINER_NO          varchar(50)          not null,
   ARRIVAL_REF           varchar(50)          not null,
   TYPE                 varchar(20)          null,
   STATUS               varchar(20)          null,
   constraint PK_CONTAINERS primary key (CONTAINER_NO),
   constraint FK_CONTAINE_CONTAINS_ARRIVALS foreign key (ARRIVAL_REF)
      references ARRIVALS (ARRIVAL_REF)
)
go

/*==============================================================*/
/* Table: CONTAINERSMOVEMENTS                                   */
/*==============================================================*/
create table CONTAINERSMOVEMENTS (
   MOVEMENT_TIME         date            not null,
   CONTAINER_NO          varchar(50)          not null,
   LOCATION             varchar(100)         null,
   MOVEMENTTYPE         varchar(20)          null,
   constraint PK_CONTAINERSMOVEMENTS primary key (MOVEMENT_TIME, CONTAINER_NO),
   constraint FK_CONTAINE_MOVE_CONTAINE foreign key (CONTAINER_NO)
      references CONTAINERS (CONTAINER_NO)
)
go

