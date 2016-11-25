DROP TABLE IF EXISTS pop3fetcher_accounts;

CREATE SEQUENCE pop3fetcher_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE  pop3fetcher_accounts (
  pop3fetcher_id BIGINT DEFAULT nextval('pop3fetcher_seq'::text) PRIMARY KEY,
  pop3fetcher_email varchar(128) NOT NULL,
  pop3fetcher_username varchar(128) NOT NULL,
  pop3fetcher_password varchar(128) NOT NULL,
  pop3fetcher_serveraddress varchar(128) NOT NULL,
  pop3fetcher_serverport varchar(128) NOT NULL,
  pop3fetcher_ssl varchar(10) DEFAULT '0',
  pop3fetcher_leaveacopyonserver BOOLEAN DEFAULT false,
  user_id BIGINT NOT NULL DEFAULT '0',
  last_check BIGINT NOT NULL DEFAULT '0',
  last_uidl varchar(70) DEFAULT NULL,
  update_lock BOOLEAN NOT NULL DEFAULT false,
  pop3fetcher_provider varchar(128) DEFAULT NULL,
  default_folder varchar(128) DEFAULT NULL
);
