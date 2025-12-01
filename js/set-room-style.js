// 定義特定的數值陣列
// 分鐘數：包含分數
const timeOptions = [
    0.25, 0.5, 0.75, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 
    11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30, 35, 40, 45, 60, 90
];

// 加秒數
const incOptions = [
    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 
    11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30
];

// 狀態物件：儲存使用者的選擇
let currentSettings = {
    side: 'random', // 預設隨機
    timeMinutes: 10, // 預設 10 分鐘
    increment: 0,    // 預設 0 秒
    assist: true     // 預設開啟
};

// DOM 元素載入後執行
document.addEventListener('DOMContentLoaded', () => {
    // 取得 DOM 元素
    const timeRange = document.getElementById('time-range');
    const timeDisplay = document.getElementById('time-display');
    const incRange = document.getElementById('inc-range');
    const incDisplay = document.getElementById('inc-display');
    const assistWarning = document.getElementById('assist-warning');
    const startBtn = document.getElementById('start-btn');

    // --- 1. 時間滑桿初始化 ---
    
    // 設定滑桿的最大值 (因為是對應陣列索引)
    timeRange.max = timeOptions.length - 1;
    incRange.max = incOptions.length - 1;

    // 設定預設位置
    const defaultTimeIndex = timeOptions.indexOf(10); // 找到10分鐘在陣列的位置
    timeRange.value = defaultTimeIndex;
    
    // 格式化顯示文字的輔助函式
    function formatTimeDisplay(val) {
        if (val === 0.25) return "1/4 分鐘 (15秒)";
        if (val === 0.5) return "1/2 分鐘 (30秒)";
        if (val === 0.75) return "3/4 分鐘 (45秒)";
        return val + " 分鐘";
    }

    // 監聽時間滑桿變動
    timeRange.addEventListener('input', (e) => {
        const index = parseInt(e.target.value);
        const value = timeOptions[index];
        currentSettings.timeMinutes = value;
        timeDisplay.textContent = formatTimeDisplay(value);
    });

    // 監聽加秒滑桿變動
    incRange.addEventListener('input', (e) => {
        const index = parseInt(e.target.value);
        const value = incOptions[index];
        currentSettings.increment = value;
        incDisplay.textContent = value + " 秒";
    });

    // --- 2. 按鈕群組邏輯 (通用) ---
    function setupButtonGroup(groupId, settingKey, callback = null) {
        const group = document.getElementById(groupId);
        const buttons = group.querySelectorAll('.btn-option');

        buttons.forEach(btn => {
            // 初始化 UI 選中狀態
            // 如果按鈕的 data-value 等於 currentSettings 中的預設值，就加上 selected
            if (btn.dataset.value === String(currentSettings[settingKey])) {
                btn.classList.add('selected');
            }

            btn.addEventListener('click', () => {
                // 1. 移除同組其他按鈕的 selected
                buttons.forEach(b => b.classList.remove('selected'));
                // 2. 加上當前按鈕的 selected
                btn.classList.add('selected');
                
                // 3. 取得值並轉換型別
                let val = btn.dataset.value;
                if(val === 'true') val = true;
                if(val === 'false') val = false;
                
                // 4. 更新全域設定
                currentSettings[settingKey] = val;

                // 5. 如果有額外邏輯 (callback)，則執行
                if (callback) callback(val);
            });
        });
    }

    // 初始化先後手按鈕
    setupButtonGroup('side-group', 'side');

    // 初始化輔助按鈕 (包含警告文字顯示邏輯)
    setupButtonGroup('assist-group', 'assist', (isEnabled) => {
        if (!isEnabled) {
            assistWarning.classList.add('show');
        } else {
            assistWarning.classList.remove('show');
        }
    });

    // --- 3. 開始遊戲事件 ---
    startBtn.addEventListener('click', startGame);
});

// 送出資料函式
function startGame() {
    console.log("最終設定:", currentSettings);
    
    // 這裡只是示範，實際會將資料傳送到後端
    alert(`房間建立成功！\n` + 
          `-----------------\n` +
          `陣營: ${currentSettings.side}\n` +
          `時間: ${currentSettings.timeMinutes} 分 + ${currentSettings.increment} 秒\n` +
          `輔助: ${currentSettings.assist ? '開啟' : '關閉'}`);
    
    // 範例：轉址到遊戲頁面
    // window.location.href = `/game.html?side=${currentSettings.side}&time=${currentSettings.timeMinutes}&inc=${currentSettings.increment}`;
}