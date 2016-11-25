-- SQLite table for rcguard

CREATE TABLE rcguard (
  ip varchar(40) NOT NULL PRIMARY KEY,
  first datetime NOT NULL,
  last datetime NOT NULL,
  hits integer NOT NULL
);

CREATE INDEX last_index ON rcguard(last);
CREATE INDEX hits_index ON rcguard(hits);
