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
});