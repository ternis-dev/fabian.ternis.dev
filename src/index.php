<main>
    <section id="hero">
        <h1>Hello, I'm Fabian Ternis</h1>
        <h2>A Student and developer from Germany.</h2>
        <div class="some-container">
            <h3>Building my HomeLab @ <a href="http://ternis.net">ternis.net</a></h3>
            <h3>Web development @ <span class="font-code">(<a href="http://xpsystems.eu" target="_blank">xpsystems.eu</a> && <a href="http://ternis-edv.de">ternis-edv.de</a>)</span></h3>
            <h3>Links via <a href="http://fabian-ternis-dev.ternis.link" target="_blank">ternis.link</a></h3>
            <h3>I just own too many domains (see: <a href="http://dnbx.de#domainlist" target="_blank">dnbx.de</a>)</h3>
            <div class="img-container">
                <img src="/BASF_2026.jpg" class="me" alt="Fabian Ternis at BASF SE in Ludwigshafen during the Jugend Forscht State Competition / Landeswettbewerb Rheinland-Pfalz">
                <span class="copyright-note">Image by <a href="https://basf.com" target="_blank">BASF&trade; SE</a></span>
            </div>
        </div>
    </section>
    
    <section id="other">
        <h4>I am also trying not to get sued by <a href="https://cult-management.com" target="_blank">cult-management.com</a> for owning <span class="font-code">('<a href="https://twins-on-ice.de" target="_blank">twins-on-ice.de</a>' && '<a href="https://mirrortwins.de' targte="_blank">mirrortwins.de</a> && '<a href="https://twinsonice.eu" target="_blank">twinsonice.eu</a>')</span> as well as <span class="font-code">('<a href="https://emiliamacula.de" target="_blank">emiliamacula.de</a>' && '<a href="https://letiziamacula.de" target="_blank">letiziamacula.de</a>')</span> and <a href="https://cult-management.de" target="_blank">cult-management.de</a> of course.</h4>
        <hr>
        Whenever I do a disclosure (in any way), I get no response (e.g., 'A Leaked Gemini-API-Key by a Google-Employee' or 'no idea how to name it')
        <hr>
        Building (mostly Vibing) storygrab.net as a side-project that is powered by Cloudflare R2(r2.dev) and was solely built to power twins-on-ice.de (alt: mirrortwins.de)
    </section>

    <section id="contact">
        <h3>Still sad about losing the domain 'mail-free.de' in 2025 (currently own mail-free.eu though)</h3>        
        You can contact me via email ('fabian.ternis.dev-{gen_token}[at]fabian.ternismail.de' || '{gen_token}[at]fabian.ternis.dev')
    </section>

    <section id="news">
    <h2>Latest Stuff ("news", not teh banner):</h2>
    <ul>
        <li>I might get shipped a <a href="http://" target="_blank" class="wiki-link knowledge-link">Floppy Disk</a> from <a href="http://" target="_blank" class="wiki-link knowledge-link">HackClub</a> for participating in "<a href="http://99.hackclub.com">Its like 1999</a>". View my submittion <a href="https://projects.fabianternis.de/99site/" target="_blank">here</a> and the source <a href="https://github.com/fabianternis/99site" target="_blank">here</a>. <span class="note">note: This was my first EVER HackClub-submission</span></li>
        <li>Started my <a href="http://" target="_blank" class="wiki-link knowledge-link">HomeLab</a> ...</li>
        <li>joined HackClub in July 2026</li>
    </ul>
    </section>

    <section id="homelab">
        <h2>What [software] I host on my HomeLab (I know, nobody asked):</h2>
        <ul class="tech-list">
            <?php foreach(config('homelab_techs') as $tech): ?>
            <li>
                <div class="name"><?= htmlspecialchars($tech['name']) ?></div>
                <div class="comment"><?= htmlspecialchars($tech['comment']) ?></div>
                <img src="<?= htmlspecialchars($tech['image'] ?? '/homelab/tech/'.strtolower($tech['name']).'.unknown.image.mime') ?>" alt="<?= htmlspecialchars($tech['image_alt'] ?? $tech['name'] . ' Logo') ?>" class="tech-logo">
                <div class="links-container">
                    <?php foreach($tech['links'] as $linkName => $linkUrl): ?>
                    <a target="_blank" data-has-arrow=true href="<?= htmlspecialchars($linkUrl) ?>"><?= htmlspecialchars($linkName) ?></a>
                    <?php endforeach; ?>
                    <?php if (isset($tech['commented_links'])): ?>
                        <?php foreach($tech['commented_links'] as $linkName => $linkUrl): ?>
                        <!-- <a target="_blank" data-has-arrow=true href="<?= htmlspecialchars($linkUrl) ?>"><?= htmlspecialchars($linkName) ?></a> -->
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
            <other>... and more ...</other>
        </ul>

    </section>

    <section id="devices">
        <h2>Device Specs (screenfetch)</h2>
        <?php foreach($devices as $device): ?>
            <div><?= htmlspecialchars($device) ?></div>
        <?php endforeach; ?>
    </section>
    
    <section>
        <h2>Some other projects I am currently not working on:</h2>
        - Web-Search.org (Just another open-source Search engine)
        - MTEX.dev (Developer Tools (SchemaBuilder (which I am currently working on) might be one of those but is currently not being built under the MTEX.dev Brand))
        ... way too many more ...
    </section>

    <section id="domains">
        <h2>Domains I <some>currently</some> own</h2>
        <?php if(isset($domains) && !empty($domains)): ?>
            <!-- <?php var_dump($domains) ?> -->

            <div class="domains-container">
                <?php foreach($domains as $domain): ?>
                    <div class="domain" id="domain_<?= $domain['name'] . '_' . $domain['tld']?>">
                        <span><span class="name"><?= $domain['name'] ?></span>.<span class="tld"><?= $domain['tld'] ?></span></span>
                    </div>
                <?php endforeach; ?>
            </div>   
        <?php else: ?>
            <div class="error">
                There SEEMS to be an error retreving the list of Domains from dnbx.de.
                To see the list anyway visit <a href="http://dnbx.de#domainlist">this</a>.
            </div>
        <?php endif; ?>
    </section>

    <!-- <marquee behavior="" direction="" class="news-ticker bottom"> -->
    <div class="news-ticker bottom">
        <div class="ticker-content">
            This is just a fun, little website about me, my problems and co.
            <span class="ticker-divider"></span>
            Consider leaving a Star on GitHub.
        </div>
    </div>
    <!-- </marquee> -->
</main>

<footer>
    <div class="footer-container">
        Fabian Ternis
        <ul class="footer-row">
            <li class="footer-item">a</li>
            <li class="footer-item">b</li>
            <li class="footer-item">c</li>
            <li class="footer-item">d</li>
        </ul>
    </div>
</footer>