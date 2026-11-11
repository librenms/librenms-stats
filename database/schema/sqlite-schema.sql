CREATE TABLE hosts(
  hosts_id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT NOT NULL,
  first_added TEXT NOT NULL,
  last_updated TEXT NOT NULL
);
CREATE TABLE run(
  run_id INTEGER PRIMARY KEY AUTOINCREMENT,
  hosts_id INTEGER NOT NULL,
  datetime TEXT NOT NULL
);
CREATE INDEX run_hosts_id_index ON run(hosts_id);
CREATE INDEX run_datetime_index ON run(datetime);
CREATE TABLE data(
  data_id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT NOT NULL,
  run_id INTEGER NOT NULL,
  total INTEGER NOT NULL,
  "group" TEXT NOT NULL,
  name TEXT NOT NULL,
  value TEXT NOT NULL
);
CREATE INDEX data_group_index ON data("group");
CREATE INDEX data_run_id_group_index ON data(run_id, "group");
CREATE INDEX data_uuid_index ON data(uuid);
CREATE TABLE devinfo(
  uuid TEXT NOT NULL,
  count INTEGER NOT NULL,
  os TEXT NOT NULL,
  sysDescr TEXT,
  sysObjectID TEXT
);
CREATE INDEX devices_uuid_index ON devinfo(uuid);

