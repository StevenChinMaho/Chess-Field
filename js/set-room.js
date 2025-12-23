const timeOptions = [
    0.25, 0.5, 0.75, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 
    11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30, 35, 40, 45, 60, 90
];

const incOptions = [
    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 
    11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30
];

let currentSettings = {
    side: 'random',
    timeMinutes: 10,
    increment: 0,
    assist: true
};

document.addEventListener('DOMContentLoaded', () => {
    const timeRange = document.getElementById('time-range');
    const timeDisplay = document.getElementById('time-display');
    const incRange = document.getElementById('inc-range');
    const incDisplay = document.getElementById('inc-display');
    const assistWarning = document.getElementById('assist-warning');
    const startBtn = document.getElementById('start-btn');

    timeRange.max = timeOptions.length - 1;
    incRange.max = incOptions.length - 1;

    const defaultTimeIndex = timeOptions.indexOf(10);
    timeRange.value = defaultTimeIndex;
    
    function formatTimeDisplay(val) {
        if (val === 0.25) return "1/4 分鐘 (15秒)";
        if (val === 0.5) return "1/2 分鐘 (30秒)";
        if (val === 0.75) return "3/4 分鐘 (45秒)";
        return val + " 分鐘";
    }

    timeRange.addEventListener('input', (e) => {
        const index = parseInt(e.target.value);
        const value = timeOptions[index];
        currentSettings.timeMinutes = value;
        timeDisplay.textContent = formatTimeDisplay(value);
    });

    incRange.addEventListener('input', (e) => {
        const index = parseInt(e.target.value);
        const value = incOptions[index];
        currentSettings.increment = value;
        incDisplay.textContent = value + " 秒";
    });

    function setupButtonGroup(groupId, settingKey, callback = null) {
        const group = document.getElementById(groupId);
        const buttons = group.querySelectorAll('.btn');

        buttons.forEach(btn => {

            if (btn.dataset.value === String(currentSettings[settingKey])) {
                btn.classList.add('selected');
            }

            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                
                let val = btn.dataset.value;
                if(val === 'true') val = true;
                if(val === 'false') val = false;
                
                currentSettings[settingKey] = val;

                if (callback) callback(val);
            });
        });
    }

    setupButtonGroup('side-group', 'side');

    setupButtonGroup('assist-group', 'assist', (isEnabled) => {
        if (!isEnabled) {
            assistWarning.classList.add('show');
        } else {
            assistWarning.classList.remove('show');
        }
    });

    startBtn.addEventListener('click', startGame);
});

function startGame() {
    console.log("最終設定:", currentSettings);
    
    // alert(`房間建立成功！\n` + 
    //       `-----------------\n` +
    //       `陣營: ${c.side}\n` +
    //       `時間: ${currentSettings.timeMinutes} 分 + ${currentSettings.increment} 秒\n` +
    //       `輔助: ${currentSettings.assist ? '開啟' : '關閉'}`);
    const roomCode = document.getElementById("room_code").value;
    const urlObj = new URL("game.php", window.location.href);
    urlObj.searchParams.set("room_code", roomCode);

    const form = document.createElement("form");
    form.method = "POST";
    form.action = urlObj.toString();

    for (const key in currentSettings) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = currentSettings[key];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}