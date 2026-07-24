<?php

return [
    // 'homelab_programs' => [
    'homelab_techs' => [
        [
            'name' => 'WireGuard',
            // 'comment' => 'Both internally and externally',
            'comment' => 'What cabeling is there to guard?',
            // 'image' => '/homelab/wireguard', // ...
            // 'image_alt' => 'Wireguard logo',
            'links' => [
                'Docs' => 'https://www.wireguard.com/quickstart/',
                'Source' => 'https://git.zx2c4.com/wireguard-linux/',
            ]
        ],
        [
            'name' => 'Pi-Hole',
            // 'comment' => 'Just about the average hole',
            'comment' => 'Is it perfectly round?',
            // 'image' => '/homelab/pi-hole',
            // 'image_alt' => 'Pi-hole logo',
            'links' => [
                'Docs' => 'https://docs.pi-hole.net/',
                'Source' => 'https://github.com/pi-hole/pi-hole',
                'Open' => 'http://pi-hole.ternis.net/admin'
            ]
        ],
        [
            'name' => 'Immich',
            'comment' => 'No docker-images but pictures instead',
            // 'image' => '/homelab/immich',
            // 'image_alt' => 'Immich logo',
            'links' => [
                'Docs' => 'https://immich.app/docs/overview',
                'Source' => 'https://github.com/immich-app/immich',
                'Open' => 'http://immich.ternis.net'
            ]
        ],
        [
            'name' => 'NextCloud',
            // 'comment' => 'The cloud of the future',
            // 'comment' => 'Wasn\'t the sky clear before?',
            'comment' => 'I can\'t see it in the Sky.',
            // 'image' => '/homelab/nextcloud',
            // 'image_alt' => 'Nextcloud logo',
            'links' => [
                'Docs' => 'https://docs.nextcloud.com/',
                'Source' => 'https://github.com/nextcloud/server',
                'Open' => 'http://cloud.ternis.net'
            ]
        ],
        [
            'name' => 'Gitea',
            'comment' => 'Great Drink',
            'links' => [
                'Docs' => 'https://docs.gitea.com/',
                'Source' => 'https://github.com/go-gitea/gitea',
                'Open' => 'http://git.ternis.net'
            ]
        ],
        [
            'name' => 'Docker',
            'comment' => 'Just some Containers arriving',
            'links' => [
                'Docs' => 'https://docs.docker.com/',
                'Source' => 'https://github.com/moby/moby'
            ]
        ],
        [
            'name' => 'Roundcube',
            'comment' => 'Didn\'t know that was possible',
            'links' => [
                'Docs' => 'https://github.com/roundcube/roundcubemail/wiki',
                'Source' => 'https://github.com/roundcube/roundcubemail',
                // 'Open' => 'http://webmail.ternis.net'
            ]
        ],
        [
            'name' => 'n8n',
            // 'comment' => 'Whatever that is',
            'comment' => 'I just hear n\'s',
            'links' => [
                'Docs' => 'https://docs.n8n.io/',
                'Source' => 'https://github.com/n8n-io/n8n',
                'Open' => 'http://n8n.ternis.net'
            ]
        ],
        [
            'name' => 'Jellyfin',
            // 'comment' => 'In the water!?',
            'comment' => 'Shark or Dolphin?',
            'links' => [
                'Docs' => 'https://jellyfin.org/docs/',
                'Source' => 'https://github.com/jellyfin/jellyfin',
                'Open' => 'http://jellyfin.ternis.net'
            ]
        ]
    ]
];
