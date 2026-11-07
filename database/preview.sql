BEGIN;

CREATE TABLE hosts (
    hosts_id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL,
    first_added TEXT NOT NULL,
    last_updated TEXT NOT NULL
);

CREATE TABLE run (
    run_id INTEGER PRIMARY KEY AUTOINCREMENT,
    hosts_id INTEGER NOT NULL,
    datetime TEXT NOT NULL
);
CREATE INDEX run_hosts_id_index ON run (hosts_id);
CREATE INDEX run_datetime_index ON run (datetime);

CREATE TABLE data (
    data_id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL,
    run_id INTEGER NOT NULL,
    total INTEGER NOT NULL,
    "group" TEXT NOT NULL,
    name TEXT NOT NULL,
    value TEXT NOT NULL
);
CREATE INDEX data_group_index ON data ("group");
CREATE INDEX data_run_id_group_index ON data (run_id, "group");
CREATE INDEX data_uuid_index ON data (uuid);

CREATE TABLE devinfo (
    uuid TEXT NOT NULL,
    count INTEGER NOT NULL,
    os TEXT NOT NULL,
    sysDescr TEXT,
    sysObjectID TEXT
);
CREATE INDEX devices_uuid_index ON devinfo (uuid);

INSERT INTO hosts (uuid, first_added, last_updated)
VALUES
    ('preview-campus', datetime('now', '-30 days'), datetime('now')),
    ('preview-datacenter', datetime('now', '-30 days'), datetime('now')),
    ('preview-branch', datetime('now', '-30 days'), datetime('now')),
    ('preview-lab', datetime('now', '-30 days'), datetime('now'));

WITH RECURSIVE days (age) AS (
    VALUES (29)
    UNION ALL
    SELECT age - 1 FROM days WHERE age > 0
)
INSERT INTO run (hosts_id, datetime)
SELECT hosts.hosts_id, datetime('now', '-' || days.age || ' days', '-' || hosts.hosts_id || ' minutes')
FROM days CROSS JOIN hosts
ORDER BY days.age DESC, hosts.hosts_id;

WITH metrics (category, label, value, base, variation) AS (
    VALUES
        ('applications', 'application', 'nginx', 8, 5),
        ('applications', 'application', 'mysql', 5, 3),
        ('applications', 'application', 'redis', 2, 4),
        ('sensors', 'sensor', 'temperature', 55, 12),
        ('sensors', 'sensor', 'voltage', 21, 8),
        ('sensors', 'sensor', 'fanspeed', 11, 4),
        ('snmp_version', 'version', 'v2c', 40, 12),
        ('snmp_version', 'version', 'v3', 25, 8),
        ('php_version', 'version', '8.3.12', 1, 1),
        ('php_version', 'version', '8.4.3-preview', 1, 1),
        ('php_version', 'version', '8.5.1', 1, 1),
        ('type', 'type', 'network', 38, 12),
        ('type', 'type', 'server', 15, 9),
        ('type', 'type', 'storage', 6, 6),
        ('os', 'os', 'ios', 30, 12),
        ('os', 'os', 'linux', 17, 9),
        ('os', 'os', 'junos', 8, 5),
        ('os', 'os', 'generic', 4, 7),
        ('port_type', 'type', '6', 720, 48),
        ('port_type', 'type', '161', 68, 19),
        ('port_type', 'type', '53', 45, 10),
        ('port_ifspeed', 'speed', '100000000', 70, 12),
        ('port_ifspeed', 'speed', '1000000000', 460, 25),
        ('port_ifspeed', 'speed', '10000000000', 110, 20),
        ('dbschema', 'version', '2025_12', 1, 1),
        ('dbschema', 'version', '2026_01', 1, 1),
        ('alert_rules', 'rule', 'Device down', 9, 5),
        ('alert_rules', 'rule', 'Interface errors', 6, 4),
        ('alert_rules', 'rule', 'High temperature', 3, 4)
)
INSERT INTO data (uuid, run_id, total, "group", name, value)
SELECT hosts.uuid, run.run_id,
       metrics.base + (run.run_id * 7 + hosts.hosts_id * 3) % metrics.variation,
       metrics.category, metrics.label, metrics.value
FROM run JOIN hosts USING (hosts_id) CROSS JOIN metrics;

INSERT INTO devinfo (uuid, count, os, sysDescr, sysObjectID)
VALUES
    ('preview-campus', 19, 'ios', 'Cisco Catalyst campus switch', 'enterprises.9.1.1745'),
    ('preview-datacenter', 12, 'linux', 'Linux server', '.1.3.6.1.4.1.8072.3.2.10'),
    ('preview-branch', 6, 'junos', 'Juniper branch router', '.1.3.6.1.4.1.2636.1.1.1.2'),
    ('preview-lab', 4, 'generic', 'Lab appliance', '.1.3.6.1.4.1.99999.1');

COMMIT;
