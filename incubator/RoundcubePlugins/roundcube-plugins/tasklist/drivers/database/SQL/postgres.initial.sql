/**
 * Roundcube Tasklist plugin database
 *
 * @version @package_version@
 * @author Thomas Bruederli
 * @licence GNU AGPL
 * @copyright (C) 2014, Kolab Systems AG
 */

CREATE SEQUENCE tasklists_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE tasklists (
    tasklist_id integer DEFAULT nextval('tasklists_seq'::regclass) NOT NULL,
    user_id integer NOT NULL
        REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE,
    name varchar(255) NOT NULL,
    color varchar(8) NOT NULL,
    showalarms smallint NOT NULL DEFAULT 0,
    PRIMARY KEY (tasklist_id)
);

CREATE INDEX tasklists_user_id_idx ON tasklists (user_id, name);

CREATE SEQUENCE tasks_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE tasks (
    task_id integer DEFAULT nextval('tasks_seq'::regclass) NOT NULL,
    tasklist_id integer NOT NULL
        REFERENCES tasklists (tasklist_id) ON UPDATE CASCADE ON DELETE CASCADE,
    parent_id integer DEFAULT NULL,
    uid varchar(255) NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    changed timestamp without time zone DEFAULT now() NOT NULL,
    del smallint NOT NULL DEFAULT '0',
    title varchar(255) NOT NULL,
    description text,
    tags text,
    date varchar(10) DEFAULT NULL,
    time varchar(5) DEFAULT NULL,
    startdate varchar(10) DEFAULT NULL,
    starttime varchar(5) DEFAULT NULL,
    flagged smallint NOT NULL DEFAULT 0,
    complete float NOT NULL DEFAULT 0,
    status varchar(16) NOT NULL DEFAULT '',
    alarms varchar(255) DEFAULT NULL,
    recurrence varchar(255) DEFAULT NULL,
    organizer varchar(255) DEFAULT NULL,
    attendees text,
    notify timestamp without time zone DEFAULT NULL,
    PRIMARY KEY (task_id)
);

CREATE INDEX tasks_tasklisting_idx ON tasks (tasklist_id, del, date);
CREATE INDEX tasks_uid_idx ON tasks (uid);

INSERT INTO system (name, value) VALUES ('tasklist-database-version', '2014051900');
