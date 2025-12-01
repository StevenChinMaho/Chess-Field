const startGameBtn = document.getElementById('start-game-btn');
const playerName = document.getElementById('player-name');
const roomCode = document.getElementById('room-code');

function inputVerify () {
    if (playerName.value.trim().length > 0 && roomCode.value.trim().length > 0) {
        startGameBtn.disabled = false;
    } else {
        startGameBtn.disabled = true;
    }
}

playerName.addEventListener('input', inputVerify );
roomCode.addEventListener('input', inputVerify);

