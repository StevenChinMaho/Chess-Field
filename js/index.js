const startGameBtn = document.getElementById('start-game-btn');
const playerName = document.getElementById('player-name');
const roomCode = document.getElementById('room_code');

function inputVerify () {
    if (playerName.value.trim().length > 0 && roomCode.value.trim().length > 0) {
        startGameBtn.disabled = false;
    } else {
        startGameBtn.disabled = true;
    }
}

inputVerify();

playerName.addEventListener('input', inputVerify );
roomCode.addEventListener('input', inputVerify);

const errorMessage = document.getElementById('error-msg');

if (errorMessage.value === 'invalid_input') alert('非法輸入');
if (errorMessage.value === 'invalid_state') alert('非法狀態');
if (errorMessage.value === 'room_setup_in_progress') alert('房間正在設定中，請稍後再試');
if (errorMessage.value === 'room_full') alert('房間已滿');
if (errorMessage.value === 'game_started') alert('遊戲已開始');