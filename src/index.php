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
                <img src="/img/BASF_2026.jpg" class="me" alt="Fabian Ternis at BASF SE in Ludwigshafen during the Jugend Forscht State Competition / Landeswettbewerb Rheinland-Pfalz">
                <span class="copyright-note">Image by <a href="https://basf.com" target="_blank">BASF SE</a></span>
            </div>
        </div>
    </section>
    
    <section id="other">
        I am also trying not to get sued by cult-management.com for owning ('twins-on-ice.de' && 'mirrortwins.de' && 'twinsonice.eu') as well as ('emiliamacula.de' && 'letiziamacula.de')
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
    Latest Stuff:
    - joined HackClub in July 2026
    </section>
    
    <section id="homelab">
        <h2>What [software] I host on my HomeLab (I know, nobody asked):</h2>
        <ul class="tech-list">
            <li>
                <div class="name">WireGuard</div>
                <div class="description">Both internally and externally</div>
            </li>
            <li>
                <div class="name">Pi-Hole</div>
                <div class="description">Just about the average hole</div>
            </li>
            <li>
                <div class="name">Immich</div>
                <div class="description">No docker-images but pictures instead</div>
            </li>
            <li>
                <div class="name">NextCloud</div>
                <div class="description">The cloud of the future</div>
            </li>
            <li>
                <div class="name">Gitea</div>
                <div class="description">Great Drink</div>
            </li>
            <li>
                <div class="name">Docker</div>
                <div class="description">Just some Containers arriving</div>
            </li>
            <li>
                <div class="name">Roundcube</div>
                <div class="description">Didn't know that was possible</div>
            </li>
            <li>
                <div class="name">n8n</div>
                <div class="description">Whatever that is</div>
            </li>
            <li>
                <div class="name">Jellyfin</div>
                <div class="description">In the water!?</div>
            </li>
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
</main>