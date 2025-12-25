const roomCodeInput = document.getElementById("room_code");
const postData = {
    room_code: roomCodeInput.value
};

async function heartbeat() {
    try {
        await fetch("api/room-heartbeat.php", {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            cache: 'no-cache',
            body: new URLSearchParams(postData)
        });
        console.log("doki doki");
    } catch (err) {
        console.log("心跳停止 " + err);   
    }
    
}

setInterval(heartbeat, 10000);