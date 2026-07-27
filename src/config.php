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
    ],

    // DEVICES
    'devices' => [
        [
            'id' => 112,
            'name' => 'Macbook Pro M4Pro (24/500 GB)',
            'note' => 'All teh Storage and RAM use all teh time – this cost me 15Years of savings',
            'category' => 'laptop',
            'fetch' => <<<'EOF'
                               
                 -/+:.          fabianternis@Mac.lan
                :++++.          OS: 64bit macOS  
               /+++/.           Kernel: arm64 Darwin 24.5.0
       .:-::- .+/:-``.::-       Uptime: 8d 11h 28m
    .:/++++++/::::/++++++/:`    Packages: 218
  .:///////////////////////:`   Shell: zsh 5.9
  ////////////////////////`     Resolution: 3024x1964, 1920x1080 
 -+++++++++++++++++++++++`      DE: Aqua
 /++++++++++++++++++++++/       WM: Quartz Compositor
 /sssssssssssssssssssssss.      WM Theme: Blue (Dark)
 :ssssssssssssssssssssssss-     Font: SFMonoRegular
  osssssssssssssssssssssssso/`  Disk: 413G / 494G (97%)
  `syyyyyyyyyyyyyyyyyyyyyyyy+`  CPU: Apple M4 Pro
   `ossssssssssssssssssssss/    GPU: Apple M4 Pro 
     :ooooooooooooooooooo+.     RAM: 2079MiB / 24576MiB
      `:+oo+/:-..-:/+o+/-      
                          
EOF,
        ],

        [
            'id' => 121,
            'name' => 'HP (16/500 GB)',
            'note' => 'Mainly used for hosting immich and gitea',
            'category' => 'mini-pc',
            'fetch' => <<<'EOF'
            
                          ./+o+-       fabian@mini1
                  yyyyy- -yyyyyy+      OS: Ubuntu 24.04 noble
               ://+//////-yyyyyyo      Kernel: x86_64 Linux 6.8.0-136-generic
           .++ .:/++++++/-.+sss/`      Uptime: 12d 3h 26m
         .:++o:  /++++++++/:--:/-      Packages: 1416
        o:+o+:++.`..```.-/oo+++++/     Shell: bash 5.2.21
       .:+o:+o/.          `+sssoo+/    Disk: 234G / 458G (54%)
  .++/+:+oo+o:`             /sssooo.   CPU: Intel Core i3-6300T @ 4x 3.3GHz [45.0°C]
 /+++//+:`oo+o               /::--:.   RAM: 3004MiB / 15874MiB
 \+/+o+++`o++o               ++////.  
  .++.o+++oo+:`             /dddhhh.  
       .+.o+oo:.          `oddhhhh+   
        \+.++o+o``-````.:ohdhhhhh+    
         `:o+++ `ohhhhhhhhyo++os:     
           .o:`.syhhhhhhh/.oo++o`     
               /osyyyyyyo++ooo+++/    
                   ````` +oo+++o\:    
                          `oo++.  
EOF,
        ],
        [
            'id' => 122,
            'name' => 'HP (8/500 GB)',
            'note' => 'Running Nextcloud, Jellyfin and more.',
            'category' => 'mini-pc',
            'fetch' => <<<'EOF'
            
                          ./+o+-       fabian@mini2
                  yyyyy- -yyyyyy+      OS: Ubuntu 24.04 noble
               ://+//////-yyyyyyo      Kernel: x86_64 Linux 6.8.0-136-generic
           .++ .:/++++++/-.+sss/`      Uptime: 12d 3h 26m
         .:++o:  /++++++++/:--:/-      Packages: 1494
        o:+o+:++.`..```.-/oo+++++/     Shell: bash 5.2.21
       .:+o:+o/.          `+sssoo+/    Disk: 122G / 469G (27%)
  .++/+:+oo+o:`             /sssooo.   CPU: Intel Core i3-6300T @ 4x 3.3GHz [49.0°C]
 /+++//+:`oo+o               /::--:.   RAM: 2122MiB / 7822MiB
 \+/+o+++`o++o               ++////.  
  .++.o+++oo+:`             /dddhhh.  
       .+.o+oo:.          `oddhhhh+   
        \+.++o+o``-````.:ohdhhhhh+    
         `:o+++ `ohhhhhhhhyo++os:     
           .o:`.syhhhhhhh/.oo++o`     
               /osyyyyyyo++ooo+++/    
                   ````` +oo+++o\:    
                          `oo++.      

EOF,
        ],

        [
            'id' => 123,
            'name' => 'HP (8/100 GB)',
            'note' => 'DHCP + Pi-Hole + WireGuard(host+client) ...',
            'category' => 'mini-pc',
            'fetch' => <<<'EOF'
            
                          ./+o+-       fabian@mini3
                  yyyyy- -yyyyyy+      OS: Ubuntu 24.04 noble
               ://+//////-yyyyyyo      Kernel: x86_64 Linux 6.8.0-136-generic
           .++ .:/++++++/-.+sss/`      Uptime: 12d 3h 27m
         .:++o:  /++++++++/:--:/-      Packages: 1473
        o:+o+:++.`..```.-/oo+++++/     Shell: bash 5.2.21
       .:+o:+o/.          `+sssoo+/    Disk: 16G / 110G (15%)
  .++/+:+oo+o:`             /sssooo.   CPU: Intel Core i5-6500T @ 4x 3.1GHz [43.0°C]
 /+++//+:`oo+o               /::--:.   RAM: 1431MiB / 7836MiB
 \+/+o+++`o++o               ++////.  
  .++.o+++oo+:`             /dddhhh.  
       .+.o+oo:.          `oddhhhh+   
        \+.++o+o``-````.:ohdhhhhh+    
         `:o+++ `ohhhhhhhhyo++os:     
           .o:`.syhhhhhhh/.oo++o`     
               /osyyyyyyo++ooo+++/    
                   ````` +oo+++o\:    
                          `oo++.      

EOF,
        ],

        [
            'id' => 04,
            'name' => 'EPYC KVM (dedicated IPv4)',
            'note' => 'My main Webhosting KVM',
            'category' => 'kvm-server',
            'fetch' => <<<'EOF'

                          ./+o+-       root@kvm04.eyl.xpsys.de
                  yyyyy- -yyyyyy+      OS: Ubuntu 22.04 jammy
               ://+//////-yyyyyyo      Kernel: x86_64 Linux 5.15.0-185-generic
           .++ .:/++++++/-.+sss/`      Uptime: 16d 12h 9m
         .:++o:  /++++++++/:--:/-      Packages: 1016
        o:+o+:++.`..```.-/oo+++++/     Shell: bash 5.1.16
       .:+o:+o/.          `+sssoo+/    Disk: 17G / 68G (25%)
  .++/+:+oo+o:`             /sssooo.   CPU: AMD EPYC 7543 32-Core @ 2x 2.8GHz
 /+++//+:`oo+o               /::--:.   RAM: 1019MiB / 2974MiB
 \+/+o+++`o++o               ++////.  
  .++.o+++oo+:`             /dddhhh.  
       .+.o+oo:.          `oddhhhh+   
        \+.++o+o``-````.:ohdhhhhh+    
         `:o+++ `ohhhhhhhhyo++os:     
           .o:`.syhhhhhhh/.oo++o`     
               /osyyyyyyo++ooo+++/    
                   ````` +oo+++o\:    
                          `oo++.          
EOF,
        ],

        [
            'id' => 06,
            'name' => 'EPYC KVM (dedicated IPv4)',
            'note' => 'Testing Server (I usually run Ubuntu)',
            'category' => 'kvm-server',
            'fetch' => <<<'EOF'

         _,met$$$$$gg.           root@kvm06.eyl.xpsys.de
      ,g$$$$$$$$$$$$$$$P.        OS: Debian 13 trixie
    ,g$$P""       """Y$$.".      Kernel: x86_64 Linux 6.12.95+deb13-cloud-amd64
   ,$$P'              `$$$.      Uptime: 16d 13h 0m
  ',$$P       ,ggs.     `$$b:    Packages: 446
  `d$$'     ,$P"'   .    $$$     Shell: bash 5.2.37
   $$P      d$'     ,    $$P     Disk: 9.3G / 54G (19%)
   $$:      $$.   -    ,d$$'     CPU: AMD EPYC 7543 32-Core @ 2x 2.8GHz
   $$\;      Y$b._   _,d$P'      RAM: 1871MiB / 7960MiB
   Y$$.    `.`"Y$$$$P"'         
   `$$b      "-.__              
    `Y$$                        
     `Y$$.                      
       `$$b.                    
         `Y$$b.                 
            `"Y$b._             
                `""""           
                      
EOF,
        ],

        [
            'id' => 07,
            'name' => 'Ryzen KVM (dedicated IPv4)',
            'note' => 'Currently just running WireGuard',
            'category' => 'kvm-server',
            'fetch' => <<<'EOF'

         _,met$$$$$gg.           root@kvm07.eyl.xpsys.de
      ,g$$$$$$$$$$$$$$$P.        OS: Debian 13 trixie
    ,g$$P""       """Y$$.".      Kernel: x86_64 Linux 6.12.95+deb13-cloud-amd64
   ,$$P'              `$$$.      Uptime: 16d 12h 18m
  ',$$P       ,ggs.     `$$b:    Packages: 424
  `d$$'     ,$P"'   .    $$$     Shell: bash 5.2.37
   $$P      d$'     ,    $$P     Disk: 2.9G / 52G (6%)
   $$:      $$.   -    ,d$$'     CPU: AMD Ryzen 9 5950X 16-Core @ 2x 3.394GHz
   $$\;      Y$b._   _,d$P'      RAM: 438MiB / 3928MiB
   Y$$.    `.`"Y$$$$P"'         
   `$$b      "-.__              
    `Y$$                        
     `Y$$.                      
       `$$b.                    
         `Y$$b.                 
            `"Y$b._             
                `""""           
                        
EOF,
        ],

        [
            'id' => 10,
            'name' => 'EPYC KVM (dedicated IPv4)',
            'note' => 'hosting just ONE Laravel-Application',
            'category' => 'kvm-server',
            'fetch' => <<<'EOF'

         _,met$$$$$gg.           root@kvm10.eyl.xpsys.de
      ,g$$$$$$$$$$$$$$$P.        OS: Debian 13 trixie
    ,g$$P""       """Y$$.".      Kernel: x86_64 Linux 6.12.74+deb13+1-cloud-amd64
   ,$$P'              `$$$.      Uptime: 3h 29m
  ',$$P       ,ggs.     `$$b:    Packages: 1094
  `d$$'     ,$P"'   .    $$$     Shell: bash 5.2.37
   $$P      d$'     ,    $$P     Disk: 3.2G / 11G (32%)
   $$:      $$.   -    ,d$$'     CPU: Intel Xeon E5-2697 v4 @ 2.297GHz
   $$\;      Y$b._   _,d$P'      RAM: 728MiB / 967MiB
   Y$$.    `.`"Y$$$$P"'         
   `$$b      "-.__              
    `Y$$                        
     `Y$$.                      
       `$$b.                    
         `Y$$b.                 
            `"Y$b._             
                `""""       
EOF,
        ],

    ],
];

// ToDO: Make "colors" on device-"fetches" function