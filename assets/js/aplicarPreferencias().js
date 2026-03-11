function aplicarPreferencias() {
    // Dark Mode
    if (userPreferences.darkMode === 'enabled') {
        document.body.classList.add('dark-mode');
        toggleDark.checked = true;
    } else {
        document.body.classList.remove('dark-mode');
        toggleDark.checked = false;
    }

    // Fonte
    document.body.classList.remove('font-small', 'font-large');
    if (userPreferences.fontSize === 'small') {
        document.body.classList.add('font-small');
        fontSizeSelect.value = 'small';
    } else if (userPreferences.fontSize === 'large') {
        document.body.classList.add('font-large');
        fontSizeSelect.value = 'large';
    } else {
        fontSizeSelect.value = 'default';
    }

    // Tema
    document.body.classList.remove('theme-blue', 'theme-green', 'theme-red');
    if (userPreferences.colorTheme !== 'default') {
        document.body.classList.add(`theme-${userPreferences.colorTheme}`);
    }
    colorThemeSelect.value = userPreferences.colorTheme;
}

function salvarPreferencia(tipo, valor) {
    $.post('salvar_preferencia.php', { tipo: tipo, valor: valor })
        .done(function(response) {
            // Atualiza variável local para refletir a preferência
            userPreferences[tipo] = valor;
        })
        .fail(function() {
            alert('Erro ao salvar preferências.');
        });
}

toggleDark.addEventListener('change', function () {
    if (this.checked) {
        document.body.classList.add('dark-mode');
        salvarPreferencia('darkMode', 'enabled');
    } else {
        document.body.classList.remove('dark-mode');
        salvarPreferencia('darkMode', 'disabled');
    }
});

fontSizeSelect.addEventListener('change', function () {
    document.body.classList.remove('font-small', 'font-large');
    if (this.value === 'small') {
        document.body.classList.add('font-small');
        salvarPreferencia('fontSize', 'small');
    } else if (this.value === 'large') {
        document.body.classList.add('font-large');
        salvarPreferencia('fontSize', 'large');
    } else {
        salvarPreferencia('fontSize', 'default');
    }
});

colorThemeSelect.addEventListener('change', function () {
    document.body.classList.remove('theme-blue', 'theme-green', 'theme-red');
    if (this.value !== 'default') {
        document.body.classList.add(`theme-${this.value}`);
    }
    salvarPreferencia('colorTheme', this.value);
});

