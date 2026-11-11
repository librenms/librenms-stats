<?php

return [
    'asset_version' => '7',
    'visible_categories' => ['bar' => 8, 'donut' => 5],
    'cache_ttl' => (int) env('STATS_CACHE_TTL', 86400),
    'adoption_features' => [
        'BGP' => ['bgppeer_state'],
        'OSPF' => ['ospf', 'ospfv3'],
        'Billing' => ['bills'],
        'SLA' => ['sla'],
        'Virtualization' => ['vminfo', 'vmware'],
        'API tokens' => ['api_tokens'],
        'IPv6 monitoring' => ['ipv6_addresses'],
        'Wireless monitoring' => ['wireless'],
        'IPsec monitoring' => ['ipsec'],
        'VRF monitoring' => ['vrfs'],
        'VLAN monitoring' => ['vlans'],
        'Distributed polling' => ['pollers'],
    ],
    'adoption_minimums' => [
        'Distributed polling' => 2,
    ],
];
