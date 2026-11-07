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
        ('port_ifspeed', 'ifSpeed', '100', 70, 12),
        ('port_ifspeed', 'ifSpeed', '1000', 460, 25),
        ('port_ifspeed', 'ifSpeed', '10000', 110, 20),
        ('alert_rules', 'severity', 'critical', 9, 5),
        ('alert_rules', 'severity', 'warning', 6, 4),
        ('alert_rules', 'severity', 'ok', 3, 4),
        ('wireless', 'sensor_class', 'clients', 25, 6),
        ('wireless', 'sensor_class', 'signal', 30, 6),
        ('wireless', 'sensor_class', 'frequency', 18, 5),
        ('bgppeer_state', 'bgpPeerState', 'established', 8, 3),
        ('bgppeer_state', 'bgpPeerState', 'idle', 1, 1),
        ('bgppeer_status', 'bgpPeerAdminStatus', 'start', 8, 3),
        ('bgppeer_status', 'bgpPeerAdminStatus', 'stop', 1, 1),
        ('xdp', 'protocol', 'lldp', 110, 10),
        ('xdp', 'protocol', 'cdp', 60, 8),
        ('ospf', 'ospfVersionNumber', '2', 3, 2),
        ('ospfv3', 'ospfv3VersionNumber', '3', 1, 1),
        ('ospf_links', 'ospfIfType', 'broadcast', 12, 3),
        ('ospf_links', 'ospfIfType', 'pointToPoint', 8, 3),
        ('ospfv3_links', 'ospfv3IfType', 'pointToPoint', 6, 3),
        ('bills', 'bill_type', 'cdr', 2, 1),
        ('sla', 'rtt_type', 'icmpEcho', 10, 4),
        ('vminfo', 'vm_type', 'vmware', 25, 7),
        ('vmware', '', '', 25, 7),
        ('api_tokens', '', '', 2, 3)
)
INSERT INTO data (uuid, run_id, total, "group", name, value)
SELECT hosts.uuid, run.run_id,
       CASE
           WHEN metrics.category IN ('bgppeer_state', 'bgppeer_status') AND hosts.hosts_id = 4 THEN 0
           WHEN metrics.category IN ('ospf', 'ospfv3') AND hosts.hosts_id NOT IN (1, 3) THEN 0
           WHEN metrics.category = 'bills' AND hosts.hosts_id != 2 THEN 0
           WHEN metrics.category = 'sla' AND hosts.hosts_id != 3 THEN 0
           WHEN metrics.category IN ('vminfo', 'vmware', 'api_tokens') AND hosts.hosts_id NOT IN (1, 2) THEN 0
           ELSE metrics.base + (run.run_id * 7 + hosts.hosts_id * 3) % metrics.variation
       END,
       metrics.category, metrics.label, metrics.value
FROM run JOIN hosts USING (hosts_id) CROSS JOIN metrics;


WITH versions (category, first, second, third, fourth) AS (
    VALUES
        ('librenms_release', '26.11.0', '26.11.0', '26.10.1', '26.9.0'),
        ('php_version', '8.3.12', '8.4.3', '8.4.3', '8.5.1'),
        ('python_version', '3.12.8', '3.13.2', '3.13.2', '3.14.0'),
        ('database_version', '10.11.6-MariaDB', '11.4.2-MariaDB', '8.0.40-MySQL', '8.4.3-MySQL'),
        ('rrdtool_version', '1.8.0', '1.9.0', '1.9.0', '1.9.0'),
        ('netsnmp_version', '5.9.3', '5.9.4', '5.9.4', '5.9.4'),
        ('dbschema', '380', '390', '390', '395')
)
INSERT INTO data (uuid, run_id, total, "group", name, value)
SELECT hosts.uuid, run.run_id, 1, versions.category,
       CASE WHEN versions.category = 'librenms_release' THEN 'release' ELSE 'version' END,
       CASE hosts.hosts_id WHEN 1 THEN versions.first WHEN 2 THEN versions.second WHEN 3 THEN versions.third ELSE versions.fourth END
FROM run JOIN hosts USING (hosts_id) CROSS JOIN versions;

INSERT INTO devinfo (uuid, count, os, sysDescr, sysObjectID)
VALUES
    ('preview-campus', 19, 'ios', 'Cisco Catalyst campus switch', 'enterprises.9.1.1745'),
    ('preview-datacenter', 12, 'linux', 'Linux server', '.1.3.6.1.4.1.8072.3.2.10'),
    ('preview-branch', 6, 'junos', 'Juniper branch router', '.1.3.6.1.4.1.2636.1.1.1.2'),
    ('preview-lab', 4, 'generic', 'Lab appliance', '.1.3.6.1.4.1.99999.1');

COMMIT;
