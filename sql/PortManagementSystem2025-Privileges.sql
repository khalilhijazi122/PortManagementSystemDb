use [PortManagementSystemDb]
go

CREATE USER Khalil IDENTIFIED BY 'khalilProject';


CREATE USER MohammadAli IDENTIFIED BY 'MohammadAliProject';


GRANT ALL PRIVILEGES ON PortManagementSystemDB.* TO Khalil;
GRANT ALL PRIVILEGES ON PortManagementSystemDB.* TO MohammadAli;


FLUSH PRIVILEGES;


SHOW GRANTS FOR Khalil;
SHOW GRANTS FOR MohammadAli;