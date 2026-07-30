<main>
    <section id="hero">
        <h1>Hello, I'm <me>Fabian Ternis</me></h1>
        <h2>A Student and developer from Germany.</h2>
        <div class="some-container">
            <h3>Building my HomeLab @ <a href="http://ternis.net">ternis.net</a></h3>
            <h3>Web development @ <span class="font-code">(<a href="http://xpsystems.eu" target="_blank">xpsystems.eu</a> && <a href="http://ternis-edv.de">ternis-edv.de</a>)</span></h3>
            <h3>Links via <a href="http://fabian-ternis-dev.ternis.link" target="_blank">ternis.link</a></h3>
            <h3>I just own too many domains (see: <a href="http://dnbx.de#domainlist" target="_blank">dnbx.de</a> and/or <a href="#domains">this</a>)</h3>
            <div class="img-container">
                <img src="/BASF_2026.jpg" class="me" alt="Fabian Ternis at BASF SE in Ludwigshafen during the Jugend Forscht State Competition / Landeswettbewerb Rheinland-Pfalz">
                <span class="copyright-note">Image by <a href="https://basf.com" target="_blank">BASF&trade;</a></span>
            </div>
        </div>

        <br><!--br-->
        <?php
            $cmt = $latest_commit;
            $strlenpoint = 15;
        ?>
        <div>My most recent commit: "<span data-content="<?= $cmt['message'] ?>"><?= (strlen($cmt['message'] ?? '') > $strlenpoint) ? substr($cmt['message'], 0, $strlenpoint) . '...' : ($cmt['message'] ?? '') ?></span>" (<?= time_ago($cmt['date'] ?? null) ?><!--, id: <?= ($cmt['short_id'] ?? 'N/A') ?>-->) on <a href="<?= ($cmt['url'] ?? '/wow') ?>"><code><?= ($cmt['repo'] ?? 'N/A') ?></code></a></div>
    </section>
    


    <section id="other">
        <h4>I am also trying not to get sued by <a href="https://cult-management.com" target="_blank">cult-management.com</a> for owning <span class="font-code">('<a href="https://twins-on-ice.de" target="_blank">twins-on-ice.de</a>' && '<a href="https://mirrortwins.de' targte="_blank">mirrortwins.de</a> && '<a href="https://twinsonice.eu" target="_blank">twinsonice.eu</a>')</span> as well as <span class="font-code">('<a href="https://emiliamacula.de" target="_blank">emiliamacula.de</a>' && '<a href="https://letiziamacula.de" target="_blank">letiziamacula.de</a>')</span> and <a href="https://cult-management.de" target="_blank">cult-management.de</a> of course. Newly I even own <a>twinsonice.shop</a> and <a href="http://twinsonice.link?from=fabian.ternis.dev&section=other">twinsonice.link</a>.</h4>
        
        <hr>
        Whenever I do a disclosure (in any way), I get no response (e.g., 'A Leaked Gemini-API-Key by a Google-Employee' or 'no idea how to name it')
        <hr>
        Building (mostly <vibing>Vibing</vibing>) storygrab.net as a side-project that is powered by Cloudflare R2(r2.dev) and was solely built to power twins-on-ice.de (alt: mirrortwins.de)
    </section>



    <section id="contact">
        <h3>Still sad about losing the domain 'mail-free.de' in 2025 (currently own mail-free.eu though)</h3>        
        <p>You can contact me via email ('fabian.ternis.dev-{gen_token}[at]fabian.ternismail.de' || '{gen_token}[at]fabian.ternis.dev')</p>
    </section>



    <section id="news">
        <h2>Latest Stuff ("news", not the banner):</h2>

        <ol>
            <li><currently>Currently</currently> have 30+ Side-Projects and about 50 unused domains. – What am i doing? (with my live and money)</li>
            <!-- Why not try a ordered-list? -->
            <li>I just registered my first <tld>.shop</tld><!-- ToDo (JS) each tld-tag should be a link to domains-section and auto-filter ofr all domains with taht tld ... --> Domain (<code>twinsonice.shop</code>)</li>
            <li>I might get shipped a <a href="http://" target="_blank" class="wiki-link knowledge-link">Floppy Disk</a> from <a href="http://" target="_blank" class="wiki-link knowledge-link">HackClub</a> for participating in "<a href="http://99.hackclub.com">Its like 1999</a>". View my submittion <a href="https://projects.fabianternis.de/99site/" target="_blank">here</a> and the source <a href="https://github.com/fabianternis/99site" target="_blank">here</a>. <span class="note">note: This was my first EVER HackClub-submission</span></li>
            <li>Started my <a href="http://" target="_blank" class="wiki-link knowledge-link">HomeLab</a> ...</li>
            <li><really>really</really> joined HackClub in July 2026</li>
            <li>Order is "reversed" btw.</li>
        </ol>
    </section>

<div class="has-newsbanner">

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
            <!-- <code class="fetch"><?= ($device['fetch']) ?></code> -->
            <pre><code class="fetch"><?= htmlspecialchars($device['fetch'], ENT_QUOTES, 'UTF-8') ?></code></pre>
        <?php endforeach; ?>
    </section>

    

    <section id="more_random">
        <h2>Some more <random>random</random> stuff</h2>

        <div>
        <div>My mail is hosted on <a href="https://ternismail.de">TernisMail</a> (but not on my <a href="https://ternis.net">HomeLab</a>)</div>
        </div>

        <div>
        <!-- <div class="commented-out">// I <hate>hate</hate> removing <code>Code</code> – (i always <code>// comment</code> it out).</div> -->
        <div class="commented-out">// I <hate>hate</hate> removing <code>Code</code> – (i always <code class="commented-out">// comment</code> it out).</div>
        <div>I <hate>hate</hate> deleting <code>Code</code> btw. (i always just <code class="commented-out">// comment</code> it out).</div>
        </div>

        <div>Just got <a href="http://web.tstatic.de">tstatic.de</a> for hosting static assets. (Most(currently all) of the assets on this site are still stored in the repo ...)</div>
    </section>



    <section>
        <h2>Some other projects I am currently not working on:</h2>

        <ul>
            <li><a href="http://Web-Search.org">Web-Search.org</a> (Just another open-source Search engine)</li>
            <li><a href="http://MTEX.dev">MTEX.dev</a> (Developer Tools (SchemaBuilder (which I am currently working on) might be one of those but is currently not being built under the MTEX.dev Brand))</li>
            ... way too many more ...
        </ul>
    </section>



    <section id="domains">
        <h2>Domains I <currently>currently</currently> own</h2>

        <?php if(isset($domains) && !empty($domains)): ?>
            <!-- <?php var_dump($domains) ?> -->

            <div class="domains-container">
                <?php foreach($domains as $domain): ?>
                    <div class="domain tld-<?= $domain['tld'] ?>" id="domain_<?= $domain['name'] . '_' . $domain['tld']?>">
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



    <section id="stories" tabindex="0" aria-label="Recent Instagram Stories Carousel">
        <h2>Stories I <some>recently</some> posted</h2>

        <h3>Brought to you by the power of <a href="https://storygrab.net/?from=1" target="_blank">StoryGrab</a> <!-- TODO: Knowledgebase ... (Storygrab maily created for twins-on-ice.de ... --></h3>
        <ai-note>Note: Most of this section's styles were generated by AI.</ai-note>
        <?php if(isset($stories) && !empty($stories)): ?>
            <div class="stories-wrapper">
                <button class="stories-nav-btn stories-prev" aria-label="Previous story">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </button>

                <div class="stories-container">
                    <?php foreach($stories as $index => $story): 
                        $mediaPath = $story['video_path'] ?? $story['image_path'] ?? ($story['media'][0]['image_path'] ?? '');
                        $isVideo = !empty($story['video_path']) || (($story['type'] ?? '') === 'video');
                        $mediaUrl = storygrab_media_url($mediaPath);
                        $posterUrl = !empty($story['image_path']) ? storygrab_media_url($story['image_path']) : '';
                        $createdTimestamp = $story['created_at'] ?? $story['creation_unixtimestamp'] ?? null;
                        $createdDate = $createdTimestamp ? (is_numeric($createdTimestamp) ? date('M j, Y', $createdTimestamp) : date('M j, Y', strtotime($createdTimestamp))) : '';
                    ?>
                        <div class="story story-container" 
                             data-index="<?= $index ?>"
                             data-type="<?= $isVideo ? 'video' : 'image' ?>"
                             data-src="<?= htmlspecialchars($mediaUrl, ENT_QUOTES) ?>"
                             data-poster="<?= htmlspecialchars($posterUrl, ENT_QUOTES) ?>"
                             data-date="<?= htmlspecialchars($createdDate, ENT_QUOTES) ?>"
                             tabindex="0"
                             role="button"
                             aria-label="Story <?= $index + 1 ?>">
                            <div class="story-card-inner">
                                <div class="story-media-box">
                                    <?php if($isVideo): ?>
                                        <video class="story-media story-video" src="<?= htmlspecialchars($mediaUrl, ENT_QUOTES) ?>" poster="<?= htmlspecialchars($posterUrl, ENT_QUOTES) ?>" muted playsinline loop></video>
                                        <div class="story-badge video-badge">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($mediaUrl, ENT_QUOTES) ?>" alt="Story <?= $index + 1 ?>" class="story-media story-image" loading="lazy">
                                    <?php endif; ?>
                                    <div class="story-overlay">
                                        <?php if($createdDate): ?>
                                            <span class="story-date"><?= htmlspecialchars($createdDate) ?></span>
                                        <?php endif; ?>
                                        <span class="story-view-prompt">Click to view</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="stories-nav-btn stories-next" aria-label="Next story">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>

            <div class="stories-pagination">
                <?php foreach($stories as $index => $story): ?>
                    <button class="story-dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>" aria-label="Go to story <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h4>Seems like there was an Error loading the stories from <a href="<?= 'https://storygrab.net/@'. ($_ENV['STORYGRAB_USERNAME'] ?? 'ternisfabian') ?>" target="_blank">My Profile</a></h4>
        <?php endif; ?>
    </section>


    <section id="redaction">
        <h2>This is, how the U.S. goverment <redacts>redacts</redacts> texts</h2>

        <tipp>Tipp: Try selecting the text below</tipp>
        <redacted>
            <h1>Hi There, you may not be able to read this – but Google's Crawler is</h1>
            <h2>I actually have no idea what to write here</h2>
            <p>So, sohoild i just simply put the good, old Lorem here?</p>

            <h2>Lorem Startum</h2>
            <p>Lorem ipsum dolor sit</p>
        </redacted>
    </section>



    <section id="buttons">
        <h2>Just <some>some</some> Buttons</h2>

        <div class="buttons-container">
            <button class="hover-moving-gradient_border">Hover me!</button>

        </div>
    </section>



    <section id="linkshorten">
        <h2>I <would>would</would> have a form to shorten a link with <a href="http://ternis.link" target="_blank">ternis.link</a> but i am still working on it.</h2>

        <h3>So, instead of <a href="http://ternis.link" target="_blank">ternis.link</a>, I will use <a href="http://twinsonice.link" target="_blank">twinsonice.link</a> (short: <a href="http://icelnk.de" target="_blank">icelnk.de</a>).</h3>
        <form id="link_shortening_form" method="post" action="#linkshorten">
            <div class="form-field">
                <label for="url_input">URL</label>
                <input type="url" name="url" id="url_input" placeholder="https://fabian.ternis.dev/wow" required>
            </div>

            <div class="form-field">
                <label for="label_input">Label (optional)</label>
                <input type="text" name="label" id="label_input" placeholder="Custom Link Title">
            </div>

            <div class="form-buttons">
                <input type="submit" value="Create with backend" id="backend_submit" name="submit_mode">
                <button type="button" id="frontend_submit">Create in frontend</button>
            </div>
        </form>

        <div id="linkshorten_results"></div>

        <?php if(isset($_POST['url'])): ?>
            <?php $new_link = $api_['icelink']->createLink($_POST['url'], $_POST['label'] ?? null); ?>
            <script>
                window.link_shortening_response = <?= json_encode($new_link) ?>;
            </script>
        <?php endif; ?>
    </section>



    <section id="spam_pervention">
        <h2>A Captcha just for <fun>fun</fun>!</h2>

        <h3>You can check the captcha (from <a href="https://cloudflare.com" target="_blank">CloudFlare</a>'s <a href="https://www.cloudflare.com/products/turnstile/" target="_blank">Turnstile</a> <a href="https://turnstile.pages.dev/" target="_blank">()</a>)</h3>
        

        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>


        <form id="turnstile-form" action="#spam_pervention" method="POST" class="turnstile-container">
            <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstile->getSiteKey()) ?>" data-callback="onTurnstileSuccess" data-error-callback="onTurnstileError" data-theme="auto"></div>
            <div class="captcha-controls">
                <button type="submit" id="verify-captcha-btn" class="btn-verify">Verify Captcha</button>
            </div>
        </form>

        <div id="turnstile-result" class="captcha-result-box <?= isset($turnstileResult) ? ($turnstileResult['success'] ? 'result-success' : 'result-error') : '' ?>">
            <?php if (isset($turnstileResult)): ?>
                <?php if ($turnstileResult['success']): ?>
                    <div class="result-message status-success">
                        <h4>Captcha Verification Passed!</h4>
                        <p>Token successfully validated by Cloudflare Turnstile siteverify API.</p>
                        <pre><code><?= htmlspecialchars(json_encode($turnstileResult, JSON_PRETTY_PRINT)) ?></code></pre>
                    </div>
                <?php else: ?>
                    <div class="result-message status-error">
                        <h4>Captcha Verification Failed!</h4>
                        <p>Cloudflare Turnstile API returned error.</p>
                        <pre><code><?= htmlspecialchars(json_encode($turnstileResult, JSON_PRETTY_PRINT)) ?></code></pre>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>



    <section id="competitions">
        <h2>Some <competitions>competitions</competitions>, i took part in.</h2>
    </section>

    <!-- <marquee behavior="" direction="" class="news-ticker bottom"> -->
    <div class="news-ticker bottom">
        <div class="ticker-content">
            This is just a fun, little website about me, my problems and co.
            <span class="ticker-divider"></span>
            Consider leaving a Star on <a href="https://github.com/ternis-dev/fabian.ternis.dev">GitHub</a>.
        </div>
    </div>
    <!-- </marquee> -->
</div>
</main>


<div class="footer-container">
    <footer>
        <div class="footer-content-container">
            Fabian Ternis

            <div class="footer-row bottom-oriented">
                <!-- <ul class="footer-row"> -->
                <ul class="footer-column">
                    <li class="footer-item">a</li>
                    <li class="footer-item">b</li>
                    <li class="footer-item">c</li>
                    <li class="footer-item">d</li>
                </ul>
                <!-- <ul class="footer-row"> -->
                <ul class="footer-column">
                    <li class="footer-item">1</li>
                    <li class="footer-item">2</li>
                    <li class="footer-item">3</li>
                    <li class="footer-item">4</li>
                    <li class="footer-item">5</li>
                    <li class="footer-item">6</li>
                </ul>
                <!-- <ul class="footer-row"> -->
                <ul class="footer-column">
                    <li class="footer-item">I</li>
                    <li class="footer-item">II</li>
                    <li class="footer-item">III</li>
                    <li class="footer-item">IV</li>
                    <li class="footer-item">V</li>
                </ul>
                <ul class="footer-column">
                    <li class="footer-item">One</li>
                    <li class="footer-item">Two</li>
                    <li class="footer-item">Three</li>
                    <li class="footer-item">Four</li>
                    <li class="footer-item">Five</li>
                    <li class="footer-item">Six</li>
                    <!-- Seven Skipped ... -->
                    <li class="footer-item">Eight</li>
                </ul>
            </div>

            <div>
                <div>Current Time: < ToDo ></div>
               <div>Time Spent: < Hacka Time ></div>
            </div>
        </div>
    </footer>
</div>