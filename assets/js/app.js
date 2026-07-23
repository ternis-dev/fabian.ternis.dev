document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    let theme = localStorage.getItem('theme') ?? 'system';
    const themeInput = document.getElementById('theme-select');
    // const apiData = document.getElementById(apiDataElementId ?? 'apiData'); // The Back-end creates the element-id (may be unique)
    // const hackatime_total = 0; // apiData Stuff

    const available_themes = ['system', 'dark', 'light', 'catpucchino', 'dracula', 'winter', 'forest', 'neon'];

    themeInput.innerHTML = available_themes.map(theme_option => {
        const isSelected = theme_option === theme ? ' selected' : '';
        return `<option value="${theme_option}"${isSelected}>${theme_option}</option>`;
    }).join('');

    updateTheme();

    function updateTheme() {
        let theme = themeInput.value;
        localStorage.setItem('theme', theme);

        if(theme != 'system') {
            body.dataset.theme = theme;
        } else {
            // do stuff (ToDo)
        }
    }

    themeInput.addEventListener('change', updateTheme)

    const liveTimeContainer = document.getElementById('live-time-container');
    const liveTimeDisplay = document.getElementById('live-time-display');
    const liveTimeEmoji = document.getElementById('live-time-emoji');
    let liveTimeDisplayExists = true;
    function updateLiveTime() {
        // if(exists(liveTimeDisplay)) {
        if(liveTimeDisplay) {
            const time = new Date();

            // liveTimeDisplay.textContent = time.toLocaleTimeString();
            liveTimeDisplay.textContent = time.toLocaleTimeString(undefined, { 
                timeZone: 'Europe/Berlin' 
            });
            liveTimeDisplayExists = true;
        } else {
            if(liveTimeDisplayExists) {
                console.error('no TimeDisplay could be found!');
                liveTimeDisplayExists = false;
            }
        }
    }


    updateLiveTime();
    setInterval(updateLiveTime, 1000);
});