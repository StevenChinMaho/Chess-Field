const startGameBtn = document.getElementById('startGameBtn');
const playerName = document.getElementById('playerName');
const roomCode = document.getElementById('roomCode');

function inputVerify () {
    if (playerName.value.trim().length > 0 && roomCode.value.trim().length > 0) {
        startGameBtn.disabled = false;
    } else {
        startGameBtn.disabled = true;
    }
}

playerName.addEventListener('input', inputVerify );
roomCode.addEventListener('input', inputVerify);

