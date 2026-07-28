/**
 * Stories Interactive 3D Carousel & Lightbox Viewer
 * Powered by StoryGrab API for fabian.ternis.dev
 */

document.addEventListener('DOMContentLoaded', () => {
    const storiesSection = document.getElementById('stories');
    if (!storiesSection) return;

    const container = storiesSection.querySelector('.stories-container');
    const stories = Array.from(storiesSection.querySelectorAll('.story-container'));
    const prevBtn = storiesSection.querySelector('.stories-prev');
    const nextBtn = storiesSection.querySelector('.stories-next');
    const dots = Array.from(storiesSection.querySelectorAll('.story-dot'));

    if (!stories.length || !container) return;

    let activeIndex = Math.floor(stories.length / 2); // Start in middle if multiple, or 0
    if (stories.length <= 2) activeIndex = 0;

    let startX = 0;
    let isDragging = false;
    let currentTranslateX = 0;

    // Modal elements
    let modal = document.getElementById('story-modal');
    if (!modal) {
        modal = createStoryModal();
    }

    const modalMediaContainer = modal.querySelector('.story-modal-media');
    const modalProgressBar = modal.querySelector('.story-modal-progress-fill');
    const modalCloseBtn = modal.querySelector('.story-modal-close');
    const modalPrevBtn = modal.querySelector('.story-modal-prev');
    const modalNextBtn = modal.querySelector('.story-modal-next');
    const modalDate = modal.querySelector('.story-modal-date');

    let modalActiveIndex = -1;
    let modalTimer = null;
    let modalProgressAnim = null;
    let modalStartTime = 0;
    let modalDuration = 5000; // 5s per story by default
    let isPaused = false;

    // -------------------------------------------------------------
    // 3D Carousel Positioning & Layout Update
    // -------------------------------------------------------------
    function updateCarousel() {
        const containerWidth = container.offsetWidth || 800;
        // Spacing factor depending on screen width
        const isMobile = window.innerWidth <= 640;
        const spacing = isMobile ? Math.min(containerWidth * 0.45, 180) : Math.min(containerWidth * 0.28, 220);

        stories.forEach((story, i) => {
            const offset = i - activeIndex;
            const absOffset = Math.abs(offset);

            // Calculate 3D transforms
            const translateX = offset * spacing;
            const scale = Math.max(0.6, 1 - absOffset * 0.18);
            const rotateY = offset < 0 ? Math.min(35, absOffset * 18) : (offset > 0 ? -Math.min(35, absOffset * 18) : 0);
            const opacity = absOffset > 3 ? 0 : Math.max(0.15, 1 - absOffset * 0.28);
            const zIndex = 100 - absOffset * 10;
            const pointerEvents = absOffset > 3 ? 'none' : 'auto';

            story.style.setProperty('--story-x', `${translateX}px`);
            story.style.setProperty('--story-scale', scale);
            story.style.setProperty('--story-rotate-y', `${rotateY}deg`);
            story.style.setProperty('--story-opacity', opacity);
            story.style.setProperty('--story-z-index', zIndex);
            story.style.pointerEvents = pointerEvents;

            // Video handling in carousel cards
            const video = story.querySelector('video');
            if (offset === 0) {
                story.classList.add('active');
                if (video) {
                    video.play().catch(() => {});
                }
            } else {
                story.classList.remove('active');
                if (video) {
                    video.pause();
                    video.currentTime = 0;
                }
            }
        });

        // Update Dots
        dots.forEach((dot, i) => {
            if (i === activeIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });

        // Update nav buttons disabled state if loop is disabled
        if (prevBtn) prevBtn.disabled = stories.length <= 1;
        if (nextBtn) nextBtn.disabled = stories.length <= 1;
    }

    function goToStory(index) {
        if (index < 0) {
            activeIndex = stories.length - 1;
        } else if (index >= stories.length) {
            activeIndex = 0;
        } else {
            activeIndex = index;
        }
        updateCarousel();
    }

    function prevStory() {
        goToStory(activeIndex - 1);
    }

    function nextStory() {
        goToStory(activeIndex + 1);
    }

    // -------------------------------------------------------------
    // Controls & Event Listeners
    // -------------------------------------------------------------
    if (prevBtn) prevBtn.addEventListener('click', prevStory);
    if (nextBtn) nextBtn.addEventListener('click', nextStory);

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => goToStory(i));
    });

    stories.forEach((story, i) => {
        story.addEventListener('click', (e) => {
            if (i === activeIndex) {
                openModal(i);
            } else {
                goToStory(i);
            }
        });
    });

    // Keyboard Navigation
    storiesSection.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevStory();
        } else if (e.key === 'ArrowRight') {
            nextStory();
        }
    });

    // Touch & Pointer Drag / Swipe for Carousel
    container.addEventListener('pointerdown', (e) => {
        if (e.target.closest('.story-modal')) return;
        isDragging = true;
        startX = e.clientX;
        container.style.cursor = 'grabbing';
    });

    window.addEventListener('pointermove', (e) => {
        if (!isDragging) return;
        const diffX = e.clientX - startX;
        if (Math.abs(diffX) > 40) {
            if (diffX > 0) {
                prevStory();
            } else {
                nextStory();
            }
            isDragging = false;
            container.style.cursor = '';
        }
    });

    window.addEventListener('pointerup', () => {
        isDragging = false;
        container.style.cursor = '';
    });

    window.addEventListener('pointercancel', () => {
        isDragging = false;
        container.style.cursor = '';
    });

    // Resize handler
    window.addEventListener('resize', updateCarousel);

    // Initial render
    updateCarousel();

    // -------------------------------------------------------------
    // Full-Screen Story Viewer Lightbox Modal
    // -------------------------------------------------------------
    function createStoryModal() {
        const modalDiv = document.createElement('div');
        modalDiv.id = 'story-modal';
        modalDiv.className = 'story-modal';
        modalDiv.innerHTML = `
            <div class="story-modal-backdrop"></div>
            <div class="story-modal-content">
                <div class="story-modal-header">
                    <div class="story-modal-progress-bar">
                        <div class="story-modal-progress-fill"></div>
                    </div>
                    <div class="story-modal-info">
                        <span class="story-modal-author">ternisfabian</span>
                        <span class="story-modal-date"></span>
                        <button class="story-modal-close" aria-label="Close story">&times;</button>
                    </div>
                </div>
                <div class="story-modal-media"></div>
                <button class="story-modal-nav story-modal-prev" aria-label="Previous story">&#10094;</button>
                <button class="story-modal-nav story-modal-next" aria-label="Next story">&#10095;</button>
            </div>
        `;
        document.body.appendChild(modalDiv);
        return modalDiv;
    }

    function openModal(index) {
        modalActiveIndex = index;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        loadModalStory(modalActiveIndex);
    }

    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        stopModalTimer();
        modalMediaContainer.innerHTML = '';
    }

    function loadModalStory(index) {
        stopModalTimer();
        if (index < 0 || index >= stories.length) {
            closeModal();
            return;
        }

        modalActiveIndex = index;
        const story = stories[index];
        const type = story.dataset.type || 'image';
        const src = story.dataset.src;
        const poster = story.dataset.poster;
        const date = story.dataset.date || '';

        modalDate.textContent = date ? `• ${date}` : '';
        modalMediaContainer.innerHTML = '';

        if (type === 'video') {
            const video = document.createElement('video');
            video.src = src;
            if (poster) video.poster = poster;
            video.autoplay = true;
            video.playsInline = true;
            video.muted = false; // allow audio in lightbox
            video.className = 'story-modal-element';

            video.addEventListener('loadedmetadata', () => {
                modalDuration = (video.duration && !isNaN(video.duration)) ? video.duration * 1000 : 5000;
                startModalTimer();
            });

            video.addEventListener('ended', () => {
                nextModalStory();
            });

            modalMediaContainer.appendChild(video);
            video.play().catch(() => {
                // Fallback muted if autoplay block
                video.muted = true;
                video.play().catch(() => {});
                startModalTimer();
            });
        } else {
            const img = document.createElement('img');
            img.src = src;
            img.className = 'story-modal-element';
            modalMediaContainer.appendChild(img);
            modalDuration = 5000;
            startModalTimer();
        }

        // Sync carousel active index
        goToStory(index);
    }

    function startModalTimer() {
        stopModalTimer();
        modalStartTime = Date.now();
        modalProgressBar.style.width = '0%';

        function step() {
            if (isPaused) return;
            const elapsed = Date.now() - modalStartTime;
            const progress = Math.min(100, (elapsed / modalDuration) * 100);
            modalProgressBar.style.width = `${progress}%`;

            if (progress < 100) {
                modalProgressAnim = requestAnimationFrame(step);
            } else {
                nextModalStory();
            }
        }

        modalProgressAnim = requestAnimationFrame(step);
    }

    function stopModalTimer() {
        if (modalProgressAnim) {
            cancelAnimationFrame(modalProgressAnim);
            modalProgressAnim = null;
        }
    }

    function nextModalStory() {
        if (modalActiveIndex + 1 < stories.length) {
            loadModalStory(modalActiveIndex + 1);
        } else {
            closeModal();
        }
    }

    function prevModalStory() {
        if (modalActiveIndex > 0) {
            loadModalStory(modalActiveIndex - 1);
        } else {
            loadModalStory(0);
        }
    }

    // Modal Events
    modalCloseBtn.addEventListener('click', closeModal);
    modalPrevBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        prevModalStory();
    });
    modalNextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        nextModalStory();
    });

    const backdrop = modal.querySelector('.story-modal-backdrop');
    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    // Press & hold to pause story in modal
    const modalContent = modal.querySelector('.story-modal-content');
    modalContent.addEventListener('pointerdown', (e) => {
        if (e.target.closest('.story-modal-nav') || e.target.closest('.story-modal-close')) return;
        isPaused = true;
        stopModalTimer();
        const media = modalMediaContainer.querySelector('video');
        if (media) media.pause();
    });

    modalContent.addEventListener('pointerup', () => {
        if (isPaused) {
            isPaused = false;
            const media = modalMediaContainer.querySelector('video');
            if (media) media.play().catch(() => {});
            startModalTimer();
        }
    });

    // Keyboard events in modal
    window.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('open')) return;
        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'ArrowRight') {
            nextModalStory();
        } else if (e.key === 'ArrowLeft') {
            prevModalStory();
        }
    });
});
