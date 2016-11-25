-- PostgreSQL table for rcguard

CREATE TABLE rcguard (
	ip varchar(40) NOT NULL PRIMARY KEY,
	first timestamp with time zone NOT NULL,
	last timestamp with time zone NOT NULL,
	hits integer NOT NULL
);

CREATE INDEX rcguard_last_idx ON rcguard(last);
CREATE INDEX rcguard_hits_idx ON rcguard(hits);
