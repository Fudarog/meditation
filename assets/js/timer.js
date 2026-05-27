let timer = null;
let startTime = 0;
let pausedTime = 0;
let isPaused = false;
let currentType = '';

// Элементы формы
const select = document.getElementById('practiceSelect');
const infoBox = document.getElementById('meditationInfo');
const practiceForm = document.getElementById('practiceForm');
const timerBlock = document.getElementById('timer');
const timeDisplay = document.getElementById('timeDisplay');
const soundPlayer = document.getElementById('soundPlayer');
const practiceNotes = document.getElementById('practiceNotes');
const meditationAudio = document.getElementById('meditationAudio');

// Кнопки
const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resumeBtn = document.getElementById('resumeBtn');
const resetBtn = document.getElementById('resetBtn');
const saveBtn = document.getElementById('saveBtn');

// Форматирование времени
function formatTime(totalSeconds) {
    const mins = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const secs = (totalSeconds % 60).toString().padStart(2, '0');
    return `${mins}:${secs}`;
}

// Обновление отображения таймера
function updateDisplay() {
    const elapsed = isPaused
        ? Math.floor(pausedTime / 1000)
        : Math.floor((Date.now() - startTime) / 1000);
    timeDisplay.textContent = formatTime(elapsed);
}

// Загрузка описания практики
async function loadMeditation(type) {
    if (!type) {
        infoBox.innerHTML = 'Выбери практику, чтобы увидеть описание.';
        currentType = '';
        if (meditationAudio) {
            meditationAudio.pause();
            meditationAudio.removeAttribute('src');
            meditationAudio.load();
            meditationAudio.style.display = 'none';
        }
        return;
    }

    try {
        const response = await fetch(`../actions/get_meditation.php?type=${encodeURIComponent(type)}`);
        const data = await response.json();

        if (!response.ok || data.error) {
            infoBox.innerHTML = `<p>${data.error || 'Практика не найдена'}</p>`;
            currentType = '';
            return;
        }

        currentType = type;

        infoBox.innerHTML = `
            <h3>${data.title}</h3>
            <p><strong>Описание:</strong> ${data.description}</p>
            <p><strong>Как выполнять:</strong> ${data.howTo}</p>
            <p><strong>На что обратить внимание:</strong> ${data.focus}</p>
            <p><strong>Совет:</strong> ${data.tips}</p>
        `;

        if (meditationAudio) {
            if (data.audio_url) {
                meditationAudio.src = data.audio_url;
                meditationAudio.loop = true;
                meditationAudio.style.display = 'none';
                meditationAudio.load();
            } else {
                meditationAudio.pause();
                meditationAudio.removeAttribute('src');
                meditationAudio.load();
                meditationAudio.style.display = 'none';
            }
        }
    } catch (error) {
        infoBox.innerHTML = '<p>Не удалось загрузить описание практики.</p>';
        currentType = '';
    }
}

// Запуск таймера
function startTimer() {
    const type = select.value;

    if (!type) {
        alert('Выбери тип практики!');
        return;
    }

    currentType = type;

    // Скрываем форму, показываем таймер
    practiceForm.style.display = 'none';
    timerBlock.style.display = 'block';

    clearInterval(timer);
    pausedTime = 0;
    isPaused = false;
    startTime = Date.now();

    updateDisplay();
    timer = setInterval(updateDisplay, 1000);

    // Запускаем аудио
    if (soundPlayer) {
        soundPlayer.currentTime = 0;
        soundPlayer.play().catch(() => {});
    }

    if (meditationAudio && meditationAudio.src) {
        meditationAudio.currentTime = 0;
        meditationAudio.loop = true;
        meditationAudio.play().catch(() => {});
    }
}

// Пауза таймера
function pauseTimer() {
    if (timer) {
        clearInterval(timer);
        timer = null;
        pausedTime = Date.now() - startTime;
        isPaused = true;

        if (soundPlayer) soundPlayer.pause();
        if (meditationAudio) meditationAudio.pause();
    }
}

// Продолжение таймера
function resumeTimer() {
    if (isPaused) {
        startTime = Date.now() - pausedTime;
        timer = setInterval(updateDisplay, 1000);
        isPaused = false;

        if (soundPlayer) soundPlayer.play().catch(() => {});
        if (meditationAudio) meditationAudio.play().catch(() => {});
    }
}

// Сброс таймера
function resetTimer() {
    clearInterval(timer);
    timer = null;
    startTime = 0;
    pausedTime = 0;
    isPaused = false;
    currentType = '';

    timeDisplay.textContent = '00:00';

    if (soundPlayer) {
        soundPlayer.pause();
        soundPlayer.currentTime = 0;
    }

    if (meditationAudio) {
        meditationAudio.pause();
        meditationAudio.currentTime = 0;
    }

    practiceForm.style.display = 'block';
    timerBlock.style.display = 'none';
}

// Сохранение сессии
function saveSession() {
    const type = select.value;
    const notes = practiceNotes.value.trim();

    if (!type) {
        alert('Выбери тип практики!');
        return;
    }

    const durationSeconds = isPaused
        ? Math.floor(pausedTime / 1000)
        : Math.floor((Date.now() - startTime) / 1000);

    if (durationSeconds <= 0) {
        alert('Сессия слишком короткая для сохранения.');
        return;
    }

    // Отправляем данные на сервер
    fetch('../actions/save_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            type: type,
            duration: durationSeconds,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✅ Сессия сохранена! ${formatTime(durationSeconds)} — ${type}`);
            resetTimer();
            practiceNotes.value = '';
        } else {
            alert('❌ Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    })
    .catch(err => {
        console.error('Ошибка сохранения:', err);
        alert('Ошибка сети или сервера');
    });
}

// Обработчики событий
select.addEventListener('change', () => {
    loadMeditation(select.value);
});

startBtn.addEventListener('click', startTimer);
pauseBtn.addEventListener('click', pauseTimer);
resumeBtn.addEventListener('click', resumeTimer);
resetBtn.addEventListener('click', resetTimer);
saveBtn.addEventListener('click', saveSession);

updateDisplay();