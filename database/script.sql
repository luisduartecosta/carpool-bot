create table Caroneiros(
	id bigserial UNIQUE PRIMARY KEY,
	chat_id varchar(255) NOT NULL,
	user_id varchar(255) NOT NULL,
	username varchar(128),
	spots varchar(128),
	location varchar(128),
	timestamp bigint,
	route bit not null,
    expiration bigint not null
);

create table requests(
	id bigserial UNIQUE PRIMARY KEY,
	chat_id varchar(255) NOT NULL,
	user_id varchar(255) NOT NULL,
	username varchar(128),
	location varchar(128),
	timestamp bigint,
	route bit not null,
    expiration bigint not null
);
